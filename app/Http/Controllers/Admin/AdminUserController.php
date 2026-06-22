<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AdminUserController extends Controller
{
    public function __construct(private NotificationService $notifications) {}

    public function index(Request $request)
    {
        try {
            $users = User::query()
                ->when($request->search, fn ($q) => $q->where(function ($q2) use ($request) {
                    $safe = str_replace(['%', '_'], ['\\%', '\\_'], $request->search);
                    $q2->where('name', 'like', "%{$safe}%")
                        ->orWhere('email', 'like', "%{$safe}%")
                        ->orWhere('phone_number', 'like', "%{$safe}%");
                }))
                ->when($request->role, fn ($q) => $q->where('role', $request->role))
                ->when($request->status, fn ($q) => $q->where('is_active', $request->status === 'active'))
                ->when($request->kyc, fn ($q) => match ($request->kyc) {
                    'verified' => $q->where('kyc_verified', true),
                    'pending' => $q->kycPending(),
                    'none' => $q->whereNull('kyc_document'),
                    default => $q,
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
                ->withCount(['cycles', 'members as active_members_count' => fn ($q) => $q->where('tontine_members.status', 'active')])
                ->get();

            $transactions = $user->transactions()
                ->with('cycle.tontine')
                ->latest()
                ->take(20)
                ->get();

            $stats = [
                'total_paid' => $user->transactions()->where('status', 'success')->sum('amount'),
                'total_cycles' => $user->transactions()->where('status', 'success')->count(),
                'late_payments' => $user->transactions()->where('status', 'success')
                    ->whereHas('cycle', fn ($q) => $q->where('status', 'overdue'))->count(),
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
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="utilisateurs-tontinesn-'.now()->format('Y-m-d').'.csv"',
            ];

            return response()->stream(function () {
                $file = fopen('php://output', 'w');
                fwrite($file, "\xEF\xBB\xBF");
                fputcsv($file, ['ID', 'Nom', 'Email', 'Téléphone', 'Rôle', 'Statut', 'KYC', 'Score crédit', 'Code parrainage', 'Filleuls', 'Inscription'], ';');

                User::with('creditScore')->withCount('referrals')->latest()->chunk(500, function ($users) use ($file) {
                    foreach ($users as $u) {
                        fputcsv($file, [
                            $u->id,
                            $u->name ?? '—',
                            $u->email,
                            $u->phone_number ?? '—',
                            match ($u->role) {
                                'super_admin' => 'Super Admin', 'admin' => 'Admin', default => 'Membre'
                            },
                            $u->is_active ? 'Actif' : 'Inactif',
                            $u->kyc_verified ? 'Vérifié' : match ($u->kyc_status) {
                                'rejected' => 'Refusé', 'pending' => 'En attente', default => ($u->kyc_document ? 'En attente' : 'Non soumis')
                            },
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
        if (! auth()->user()->isAdmin()) {
            return back()->withErrors(['error' => 'Seul un administrateur peut désactiver un utilisateur.']);
        }

        try {
            if ($user->id === auth()->id()) {
                return back()->withErrors(['error' => 'Vous ne pouvez pas désactiver votre propre compte.']);
            }
            if ($user->isAdmin()) {
                return back()->withErrors(['error' => 'Impossible de désactiver un administrateur.']);
            }

            $user->update(['is_active' => ! $user->is_active]);

            if (! $user->is_active) {
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
        if (! auth()->user()->isAdmin()) {
            return back()->withErrors(['error' => 'Seul un administrateur peut modifier les rôles.']);
        }

        $request->validate(['role' => ['required', 'in:member,admin']]);

        try {
            if ($user->id === auth()->id()) {
                return back()->withErrors(['error' => 'Vous ne pouvez pas modifier votre propre rôle.']);
            }
            if ($request->role === 'admin' && ! $user->hasVerifiedEmail()) {
                return back()->withErrors(['error' => 'L\'utilisateur doit avoir un email vérifié pour obtenir un rôle administrateur.']);
            }

            $user->forceFill(['role' => $request->role])->save();
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
        abort_if(str_contains($user->kyc_document, '..'), 404);

        if (! Storage::disk('local')->exists($user->kyc_document)) {
            abort(404);
        }

        return response()->file(Storage::disk('local')->path($user->kyc_document));
    }

    public function kycReview(User $user)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        if (! $user->kyc_document) {
            return redirect()->route('admin.users.show', $user)
                ->withErrors(['error' => 'Aucun document KYC en attente pour cet utilisateur.']);
        }
        if ($user->kyc_verified) {
            return redirect()->route('admin.users.show', $user)
                ->withErrors(['error' => 'Ce compte est déjà vérifié.']);
        }

        $ocrText = null;
        $ocrMatched = null;

        if ($user->kyc_document && Storage::disk('local')->exists($user->kyc_document)) {
            try {
                $filePath = Storage::disk('local')->path($user->kyc_document);
                $response = Http::timeout(15)
                    ->attach('image', file_get_contents($filePath), 'document.'.pathinfo($filePath, PATHINFO_EXTENSION))
                    ->post('https://api.ocr.space/parse/image', [
                        'language' => 'fre',
                        'isOverlayRequired' => 'false',
                        'apikey' => config('services.ocr_space.key', 'helloworld'),
                        'OCREngine' => '2',
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if (($data['OCRExitCode'] ?? 0) === 1 && ! empty($data['ParsedResults'])) {
                        $ocrText = $data['ParsedResults'][0]['ParsedText'] ?? '';

                        $userName = strtolower(preg_replace('/\s+/', ' ', trim($user->name ?? '')));
                        $ocrLower = strtolower($ocrText);
                        $nameParts = explode(' ', $userName);
                        $matchCount = 0;
                        foreach ($nameParts as $part) {
                            if (strlen($part) > 2 && str_contains($ocrLower, $part)) {
                                $matchCount++;
                            }
                        }
                        $ocrMatched = $matchCount > 0 && ($matchCount / max(count($nameParts), 1)) >= 0.5;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('OCR.space indisponible', ['error' => $e->getMessage()]);
            }
        }

        return view('admin.kyc-review', compact('user', 'ocrText', 'ocrMatched'));
    }

    public function approveKyc(User $user)
    {
        if (! auth()->user()->isAdmin()) {
            return back()->withErrors(['error' => 'Action non autorisée.']);
        }

        try {
            if ($user->kyc_document) {
                Storage::disk('local')->delete($user->kyc_document);
            }
            $user->update([
                'kyc_verified' => true,
                'kyc_status' => 'approved',
                'kyc_rejected_reason' => null,
                'kyc_document' => null,
                'kyc_document_hash' => null,
            ]);
            $this->notifications->send($user, 'kyc_approved', 'Votre identité a été vérifiée avec succès.');
            Cache::forget('admin.stats');

            $next = User::kycPending()->where('id', '!=', $user->id)->oldest()->first();

            $endRedirect = redirect()->route('admin.users', ['kyc' => 'pending'])
                ->with('success', 'KYC approuvé. Aucun autre dossier en attente.');

            return $next
                ? redirect()->route('admin.users.kyc.review', $next)->with('success', 'KYC approuvé. Prochain dossier chargé.')
                : $endRedirect;
        } catch (\Throwable $e) {
            Log::error('Erreur approbation KYC', ['user' => $user->id, 'error' => $e->getMessage()]);

            return back()->withErrors(['error' => "Erreur lors de l'approbation KYC."]);
        }
    }

    public function rejectKyc(Request $request, User $user)
    {
        if (! auth()->user()->isAdmin()) {
            return back()->withErrors(['error' => 'Action non autorisée.']);
        }

        $request->validate(['reason' => ['nullable', 'string', 'max:255']]);

        try {
            if ($user->kyc_document) {
                Storage::disk('local')->delete($user->kyc_document);
            }
            $reason = $request->input('reason', 'Document non valide ou illisible.');
            $user->update([
                'kyc_verified' => false,
                'kyc_status' => 'rejected',
                'kyc_rejected_reason' => $reason,
                'kyc_document' => null,
                'kyc_document_hash' => null,
            ]);
            $this->notifications->send($user, 'kyc_rejected', "Votre document KYC a été refusé. Motif : {$reason}");
            Cache::forget('admin.stats');

            $next = User::kycPending()->where('id', '!=', $user->id)->oldest()->first();

            $endRedirect = redirect()->route('admin.users', ['kyc' => 'pending'])
                ->with('success', 'KYC refusé. Aucun autre dossier en attente.');

            return $next
                ? redirect()->route('admin.users.kyc.review', $next)->with('success', 'KYC refusé. Prochain dossier chargé.')
                : $endRedirect;
        } catch (\Throwable $e) {
            Log::error('Erreur refus KYC', ['user' => $user->id, 'error' => $e->getMessage()]);

            return back()->withErrors(['error' => 'Erreur lors du refus KYC.']);
        }
    }
}
