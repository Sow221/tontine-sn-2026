<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminUserController extends Controller
{
    public function __construct(private NotificationService $notifications) {}

    public function index(Request $request)
    {
        try {
            $users = User::query()
                ->when($request->search, fn($q) => $q->where(function ($q2) use ($request) {
                    $q2->where('name', 'like', "%{$request->search}%")
                       ->orWhere('email', 'like', "%{$request->search}%")
                       ->orWhere('phone_number', 'like', "%{$request->search}%");
                }))
                ->when($request->role,   fn($q) => $q->where('role', $request->role))
                ->when($request->status, fn($q) => $q->where('is_active', $request->status === 'active'))
                ->when($request->kyc, fn($q) => match($request->kyc) {
                    'verified' => $q->where('kyc_verified', true),
                    'pending'  => $q->kycPending(),
                    'none'     => $q->whereNull('kyc_document'),
                    default    => $q,
                })
                ->latest()
                ->paginate(25);

            return view('admin.users', compact('users'));
        } catch (\Throwable $e) {
            Log::error('Erreur liste utilisateurs', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Erreur lors du chargement des utilisateurs.']);
        }
    }

    public function show(User $user)
    {
        try {
            $user->load('creditScore', 'badges');

            $tontines = $user->memberships()
                ->withPivot('status', 'position', 'joined_at')
                ->withCount(['cycles', 'members as active_members_count' => fn($q) => $q->where('tontine_members.status', 'active')])
                ->get();

            $transactions = $user->transactions()
                ->with('cycle.tontine')
                ->latest()
                ->take(20)
                ->get();

            $stats = [
                'total_paid'       => $user->transactions()->where('status', 'success')->sum('amount'),
                'total_cycles'     => $user->transactions()->where('status', 'success')->count(),
                'late_payments'    => $user->transactions()->where('status', 'success')
                    ->whereHas('cycle', fn($q) => $q->where('status', 'overdue'))->count(),
                'tontines_created' => $user->tontines()->count(),
            ];

            return view('admin.user-detail', compact('user', 'tontines', 'transactions', 'stats'));
        } catch (\Throwable $e) {
            Log::error('Erreur détail utilisateur admin', ['user' => $user->id, 'error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Erreur lors du chargement du profil.']);
        }
    }

    public function export()
    {
        try {
            $headers = [
                'Content-Type'        => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="utilisateurs-tontinesn-' . now()->format('Y-m-d') . '.csv"',
            ];

            return response()->stream(function () {
                $file = fopen('php://output', 'w');
                fputs($file, "\xEF\xBB\xBF");
                fputcsv($file, ['ID', 'Nom', 'Email', 'Téléphone', 'Rôle', 'Statut', 'KYC', 'Score crédit', 'Code parrainage', 'Filleuls', 'Inscription'], ';');

                User::with('creditScore')->withCount('referrals')->latest()->chunk(500, function ($users) use ($file) {
                    foreach ($users as $u) {
                        fputcsv($file, [
                            $u->id,
                            $u->name ?? '—',
                            $u->email,
                            $u->phone_number ?? '—',
                            match($u->role) { 'super_admin' => 'Super Admin', 'admin' => 'Admin', default => 'Membre' },
                            $u->is_active ? 'Actif' : 'Inactif',
                            $u->kyc_verified ? 'Vérifié' : ($u->kyc_document ? 'En attente' : 'Non soumis'),
                            $u->creditScore?->score ?? 0,
                            $u->referral_code ?? '—',
                            $u->referrals_count ?? 0,
                            $u->created_at->format('d/m/Y'),
                        ], ';');
                    }
                });

                fclose($file);
            }, 200, $headers);
        } catch (\Throwable $e) {
            Log::error('Erreur export CSV utilisateurs', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => "Erreur lors de l'export."]);
        }
    }

    public function toggle(User $user)
    {
        try {
            if ($user->id === auth()->id()) {
                return back()->withErrors(['error' => 'Vous ne pouvez pas désactiver votre propre compte.']);
            }
            if ($user->isSuperAdmin()) {
                return back()->withErrors(['error' => 'Impossible de désactiver un super administrateur.']);
            }

            $user->update(['is_active' => !$user->is_active]);

            if (!$user->is_active) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
                // Révoquer les tokens API en même temps
                $user->tokens()->delete();
            }

            Cache::forget('admin.stats');
            return back()->with('success', 'Statut utilisateur mis à jour.');
        } catch (\Throwable $e) {
            Log::error('Erreur toggle utilisateur', ['user' => $user->id, 'error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Erreur lors de la mise à jour du statut.']);
        }
    }

    public function updateRole(Request $request, User $user)
    {
        $request->validate(['role' => ['required', 'in:member,admin,super_admin']]);

        try {
            if ($user->id === auth()->id()) {
                return back()->withErrors(['error' => 'Vous ne pouvez pas modifier votre propre rôle.']);
            }
            if ($request->role === 'super_admin' && auth()->user()->role !== 'super_admin') {
                return back()->withErrors(['error' => 'Seul un super administrateur peut créer un autre super administrateur.']);
            }

            $user->update(['role' => $request->role]);
            Cache::forget('admin.stats');

            return back()->with('success', "Rôle de {$user->name} mis à jour en « {$request->role} ».");
        } catch (\Throwable $e) {
            Log::error('Erreur mise à jour rôle', ['user' => $user->id, 'error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Erreur lors de la mise à jour du rôle.']);
        }
    }

    public function kycDocument(User $user)
    {
        abort_unless($user->kyc_document, 404);
        $path = storage_path('app/' . $user->kyc_document);
        abort_unless(file_exists($path), 404);
        return response()->file($path);
    }

    public function kycReview(User $user)
    {
        abort_unless($user->kyc_document && !$user->kyc_verified, 404);

        $ocrText    = null;
        $ocrMatched = null;
        $filePath   = storage_path('app/' . $user->kyc_document);

        if (file_exists($filePath) && class_exists('\thiagoalessio\TesseractOCR\TesseractOCR')) {
            try {
                $ocr     = new \thiagoalessio\TesseractOCR\TesseractOCR($filePath);
                $ocrText = $ocr->lang('fra')->run();

                $userName   = strtolower(preg_replace('/\s+/', ' ', trim($user->name ?? '')));
                $ocrLower   = strtolower($ocrText);
                $nameParts  = explode(' ', $userName);
                $matchCount = 0;
                foreach ($nameParts as $part) {
                    if (strlen($part) > 2 && str_contains($ocrLower, $part)) {
                        $matchCount++;
                    }
                }
                $ocrMatched = $matchCount > 0 && ($matchCount / max(count($nameParts), 1)) >= 0.5;
            } catch (\Throwable $e) {
                Log::warning('Tesseract OCR indisponible', ['error' => $e->getMessage()]);
            }
        }

        return view('admin.kyc-review', compact('user', 'ocrText', 'ocrMatched'));
    }

    public function approveKyc(User $user)
    {
        try {
            if ($user->kyc_document) {
                \Illuminate\Support\Facades\Storage::disk('local')->delete($user->kyc_document);
            }
            $user->update([
                'kyc_verified'        => true,
                'kyc_status'          => 'approved',
                'kyc_rejected_reason' => null,
                'kyc_document'        => null,
            ]);
            $this->notifications->send($user, 'kyc_approved', 'Votre identité a été vérifiée avec succès.');
            Cache::forget('admin.stats');
            return back()->with('success', 'KYC approuvé.');
        } catch (\Throwable $e) {
            Log::error('Erreur approbation KYC', ['user' => $user->id, 'error' => $e->getMessage()]);
            return back()->withErrors(['error' => "Erreur lors de l'approbation KYC."]);
        }
    }

    public function rejectKyc(Request $request, User $user)
    {
        $request->validate(['reason' => ['nullable', 'string', 'max:255']]);

        try {
            if ($user->kyc_document) {
                \Illuminate\Support\Facades\Storage::disk('local')->delete($user->kyc_document);
            }
            $reason = $request->input('reason', 'Document non valide ou illisible.');
            $user->update([
                'kyc_verified'        => false,
                'kyc_status'          => 'rejected',
                'kyc_rejected_reason' => $reason,
                'kyc_document'        => null,
                'kyc_document_hash'   => null,
            ]);
            $this->notifications->send($user, 'kyc_rejected', "Votre document KYC a été refusé. Motif : {$reason}");
            Cache::forget('admin.stats');
            return back()->with('success', 'KYC refusé.');
        } catch (\Throwable $e) {
            Log::error('Erreur refus KYC', ['user' => $user->id, 'error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Erreur lors du refus KYC.']);
        }
    }
}
