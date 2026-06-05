<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use App\Models\Tontine;
use App\Models\Transaction;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\PaymentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminDashboardController extends Controller
{
    public function __construct(
        private NotificationService $notifications,
        private PaymentService $paymentService,
    ) {}

    public function index()
    {
        try {
            $stats = Cache::remember('admin.stats', 120, function () {
                return [
                    'total_users'        => User::count(),
                    'active_users'       => User::where('is_active', true)->count(),
                    'active_tontines'    => Tontine::where('status', 'active')->count(),
                    'pending_tontines'   => Tontine::where('status', 'pending')->count(),
                    'total_transactions' => Transaction::where('status', 'success')->sum('amount'),
                    'pending_kyc'        => User::kycPending()->count(),
                    'pending_tx'         => Transaction::where('status', 'pending')->count(),
                ];
            });

            $chartRaw = Transaction::where('status', 'success')
                ->where('created_at', '>=', now()->subMonths(6)->startOfMonth())
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, SUM(amount) as total")
                ->groupBy('month')
                ->orderBy('month')
                ->pluck('total', 'month');

            $chartMonths  = collect();
            $chartAmounts = collect();
            for ($i = 5; $i >= 0; $i--) {
                $key = now()->subMonths($i)->format('Y-m');
                $chartMonths->push(now()->subMonths($i)->isoFormat('MMM YY'));
                $chartAmounts->push((int) ($chartRaw[$key] ?? 0));
            }

            $recentTontines = Cache::remember('admin.recent_tontines', 120, fn() =>
                Tontine::with('creator')->latest()->take(8)->get()
            );

            $suspiciousTx = Transaction::where('amount', '>', config('tontine.transaction.daily_limit'))
                ->with('user', 'cycle.tontine')
                ->latest()
                ->take(10)
                ->get();

            $pendingKycUsers = User::kycPending()->latest()->take(5)->get();

            return view('admin.dashboard', compact(
                'stats', 'recentTontines', 'suspiciousTx', 'pendingKycUsers',
                'chartMonths', 'chartAmounts'
            ));
        } catch (\Throwable $e) {
            Log::error('Erreur admin dashboard', ['error' => $e->getMessage(), 'class' => get_class($e)]);
            return back()->withErrors(['error' => 'Erreur lors du chargement du dashboard.']);
        }
    }

    public function users(Request $request)
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
                ->when($request->kyc,    fn($q) => match($request->kyc) {
                    'verified' => $q->where('kyc_verified', true),
                    'pending'  => $q->kycPending(),
                    'none'     => $q->whereNull('kyc_document'),
                    default    => $q,
                })
                ->latest()
                ->paginate(25);

            return view('admin.users', compact('users'));
        } catch (\Throwable $e) {
            Log::error('Erreur liste utilisateurs', ['error' => $e->getMessage(), 'class' => get_class($e)]);
            return back()->withErrors(['error' => 'Erreur lors du chargement des utilisateurs.']);
        }
    }

    public function userDetail(User $user)
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

    public function exportUsers()
    {
        try {
            $filename = 'utilisateurs-tontinesn-' . now()->format('Y-m-d') . '.csv';
            $headers  = [
                'Content-Type'        => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            return response()->stream(function () {
                $file = fopen('php://output', 'w');
                fputs($file, "\xEF\xBB\xBF");
                fputcsv($file, ['ID', 'Nom', 'Email', 'Téléphone', 'Rôle', 'Statut', 'KYC', 'Score crédit', 'Inscription'], ';');

                User::with('creditScore')->latest()->chunk(500, function ($users) use ($file) {
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

    public function toggleUser(User $user)
    {
        try {
            if ($user->id === auth()->id()) {
                return back()->withErrors(['error' => 'Vous ne pouvez pas désactiver votre propre compte.']);
            }

            $user->update(['is_active' => !$user->is_active]);

            if (!$user->is_active) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
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
            $user->update(['kyc_verified' => true, 'kyc_document' => null]);
            $this->notifications->send($user, 'kyc_approved', 'Votre identité a été vérifiée avec succès.');
            Cache::forget('admin.stats');
            return back()->with('success', 'KYC approuvé.');
        } catch (\Exception $e) {
            Log::error('Erreur approbation KYC', ['user' => $user->id, 'error' => $e->getMessage()]);
            return back()->withErrors(['error' => "Erreur lors de l'approbation KYC."]);
        }
    }

    public function rejectKyc(User $user)
    {
        try {
            if ($user->kyc_document) {
                \Illuminate\Support\Facades\Storage::disk('local')->delete($user->kyc_document);
            }
            $user->update(['kyc_verified' => false, 'kyc_document' => null, 'kyc_document_hash' => null]);
            $this->notifications->send($user, 'kyc_rejected', 'Votre document KYC a été refusé. Veuillez soumettre un document valide.');
            Cache::forget('admin.stats');
            return back()->with('success', 'KYC refusé.');
        } catch (\Exception $e) {
            Log::error('Erreur refus KYC', ['user' => $user->id, 'error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Erreur lors du refus KYC.']);
        }
    }

    public function tontineDetail(Tontine $tontine)
    {
        try {
            $tontine->load([
                'creator',
                'cycles' => fn($q) => $q->orderBy('cycle_number'),
                'cycles.beneficiary',
            ]);
            $tontine->loadCount([
                'members as active_members_count'  => fn($q) => $q->where('tontine_members.status', 'active'),
                'members as pending_members_count' => fn($q) => $q->where('tontine_members.status', 'pending'),
            ]);

            $members = $tontine->members()
                ->withPivot('status', 'position', 'joined_at')
                ->get();

            $totalCollected = $tontine->cycles->sum('total_collected');
            $cyclesPaid     = $tontine->cycles->where('status', 'paid')->count();

            return view('admin.tontine-detail', compact('tontine', 'members', 'totalCollected', 'cyclesPaid'));
        } catch (\Throwable $e) {
            Log::error('Erreur détail tontine admin', ['tontine' => $tontine->id, 'error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Erreur lors du chargement.']);
        }
    }

    public function tontines(Request $request)
    {
        try {
            $tontines = Tontine::with('creator')
                ->withCount(['members as active_members_count' => fn($q) => $q->where('tontine_members.status', 'active')])
                ->when($request->search, fn($q) => $q->where(function ($q2) use ($request) {
                    $q2->where('name', 'like', "%{$request->search}%")
                       ->orWhere('code', 'like', "%{$request->search}%");
                }))
                ->when($request->status, fn($q) => $q->where('status', $request->status))
                ->when($request->type,   fn($q) => $q->where('type', $request->type))
                ->latest()
                ->paginate(20);

            return view('admin.tontines', compact('tontines'));
        } catch (\Throwable $e) {
            Log::error('Erreur liste tontines admin', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Erreur lors du chargement des tontines.']);
        }
    }

    public function suspendTontine(Tontine $tontine)
    {
        try {
            if ($tontine->status === 'suspended') {
                return back()->withErrors(['error' => 'Cette tontine est déjà suspendue.']);
            }

            $tontine->update(['status' => 'suspended']);
            $this->notifications->send($tontine->creator, 'general', "Votre tontine « {$tontine->name} » a été suspendue par un administrateur.");

            Cache::forget('admin.recent_tontines');
            Cache::forget('admin.stats');

            return back()->with('success', "Tontine « {$tontine->name} » suspendue.");
        } catch (\Throwable $e) {
            Log::error('Erreur suspension tontine', ['tontine' => $tontine->id, 'error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Erreur lors de la suspension.']);
        }
    }

    public function reactivateTontine(Tontine $tontine)
    {
        try {
            if ($tontine->status !== 'suspended') {
                return back()->withErrors(['error' => 'Seule une tontine suspendue peut être réactivée.']);
            }

            $tontine->update(['status' => 'active']);
            $this->notifications->send($tontine->creator, 'general', "Votre tontine « {$tontine->name} » a été réactivée par un administrateur.");

            Cache::forget('admin.recent_tontines');
            Cache::forget('admin.stats');

            return back()->with('success', "Tontine « {$tontine->name} » réactivée.");
        } catch (\Throwable $e) {
            Log::error('Erreur réactivation tontine', ['tontine' => $tontine->id, 'error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Erreur lors de la réactivation.']);
        }
    }

    public function transactions(Request $request)
    {
        try {
            $transactions = Transaction::with('user', 'cycle.tontine')
                ->when($request->status,     fn($q) => $q->where('status', $request->status))
                ->when($request->method,     fn($q) => $q->where('method', $request->method))
                ->when($request->suspicious, fn($q) => $q->where('amount', '>', config('tontine.transaction.daily_limit')))
                ->latest()
                ->paginate(25);

            return view('admin.transactions', compact('transactions'));
        } catch (\Throwable $e) {
            Log::error('Erreur liste transactions admin', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Erreur lors du chargement des transactions.']);
        }
    }

    public function forceConfirmTransaction(Transaction $transaction)
    {
        try {
            if ($transaction->status === 'success') {
                return back()->withErrors(['error' => 'Transaction déjà confirmée.']);
            }

            if (in_array($transaction->status, ['reversed', 'failed'])) {
                return back()->withErrors(['error' => 'Impossible de confirmer une transaction annulée ou échouée.']);
            }

            $this->paymentService->confirmPayment($transaction);

            return back()->with('success', "Transaction #{$transaction->id} confirmée manuellement.");
        } catch (\Throwable $e) {
            Log::error('Erreur confirmation forcée transaction', ['tx' => $transaction->id, 'error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Erreur lors de la confirmation.']);
        }
    }

    public function exportTransactions()
    {
        try {
            $filename = 'transactions-tontinesn-' . now()->format('Y-m-d') . '.csv';
            $headers  = [
                'Content-Type'        => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            return response()->stream(function () {
                $file = fopen('php://output', 'w');
                fputs($file, "\xEF\xBB\xBF");
                fputcsv($file, ['ID', 'Utilisateur', 'Email', 'Tontine', 'Cycle', 'Montant (FCFA)', 'Méthode', 'Statut', 'Date'], ';');

                Transaction::with('user', 'cycle.tontine')->latest()->chunk(500, function ($txs) use ($file) {
                    foreach ($txs as $tx) {
                        fputcsv($file, [
                            $tx->id,
                            $tx->user->name ?? '—',
                            $tx->user->email ?? '—',
                            $tx->cycle->tontine->name ?? '—',
                            $tx->cycle->cycle_number ?? '—',
                            $tx->amount,
                            match($tx->method) {
                                'wave'         => 'Wave',
                                'orange_money' => 'Orange Money',
                                'free_money'   => 'Free Money',
                                'card'         => 'Carte',
                                'cash'         => 'Espèces',
                                default        => ucfirst($tx->method),
                            },
                            match($tx->status) {
                                'success'  => 'Payé',
                                'pending'  => 'En attente',
                                'failed'   => 'Échoué',
                                'reversed' => 'Annulé',
                                default    => ucfirst($tx->status),
                            },
                            $tx->paid_at?->format('d/m/Y H:i') ?? $tx->created_at->format('d/m/Y H:i'),
                        ], ';');
                    }
                });

                fclose($file);
            }, 200, $headers);
        } catch (\Throwable $e) {
            Log::error('Erreur export CSV transactions', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => "Erreur lors de l'export."]);
        }
    }

    public function exportLogs()
    {
        try {
            $filename = 'logs-activite-tontinesn-' . now()->format('Y-m-d') . '.csv';
            $headers  = [
                'Content-Type'        => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            return response()->stream(function () {
                $file = fopen('php://output', 'w');
                fputs($file, "\xEF\xBB\xBF");
                fputcsv($file, ['ID', 'Utilisateur', 'Email', 'Action', 'IP', 'Date'], ';');

                DB::table('activity_logs')
                    ->join('users', 'users.id', '=', 'activity_logs.user_id')
                    ->select('activity_logs.*', 'users.name', 'users.email')
                    ->latest('activity_logs.created_at')
                    ->chunk(500, function ($logs) use ($file) {
                        foreach ($logs as $log) {
                            fputcsv($file, [
                                $log->id,
                                $log->name ?? '—',
                                $log->email ?? '—',
                                $log->action,
                                $log->ip_address ?? '—',
                                Carbon::parse($log->created_at)->format('d/m/Y H:i:s'),
                            ], ';');
                        }
                    });

                fclose($file);
            }, 200, $headers);
        } catch (\Throwable $e) {
            Log::error('Erreur export logs', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => "Erreur lors de l'export."]);
        }
    }

    public function logs(Request $request)
    {
        try {
            $logs = DB::table('activity_logs')
                ->join('users', 'users.id', '=', 'activity_logs.user_id')
                ->select('activity_logs.*', 'users.name', 'users.email')
                ->when($request->search, fn($q) => $q->where(function ($q2) use ($request) {
                    $q2->where('users.name', 'like', "%{$request->search}%")
                       ->orWhere('activity_logs.action', 'like', "%{$request->search}%");
                }))
                ->when($request->date_from, fn($q) => $q->whereDate('activity_logs.created_at', '>=', $request->date_from))
                ->when($request->date_to,   fn($q) => $q->whereDate('activity_logs.created_at', '<=', $request->date_to))
                ->latest('activity_logs.created_at')
                ->paginate(50);

            return view('admin.logs', compact('logs'));
        } catch (\Exception $e) {
            Log::error('Erreur chargement logs', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Erreur lors du chargement des journaux.']);
        }
    }

    public function notifications(Request $request)
    {
        try {
            $notifications = NotificationLog::with('user')
                ->when($request->channel, fn($q) => $q->where('channel', $request->channel))
                ->when($request->status,  fn($q) => $q->where('status', $request->status))
                ->when($request->event,   fn($q) => $q->where('event', $request->event))
                ->latest()
                ->paginate(30)->withQueryString();

            return view('admin.notifications', compact('notifications'));
        } catch (\Throwable $e) {
            Log::error('Erreur logs notifications admin', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Erreur lors du chargement.']);
        }
    }

    public function posts()
    {
        $posts = \App\Models\Post::with('author')->latest()->paginate(20);
        return view('admin.posts', compact('posts'));
    }

    public function storePost(Request $request)
    {
        $request->validate([
            'title'   => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
        ]);

        \App\Models\Post::create([
            'user_id'      => auth()->id(),
            'title'        => $request->title,
            'slug'         => \Illuminate\Support\Str::slug($request->title) . '-' . time(),
            'excerpt'      => $request->excerpt,
            'content'      => $request->content,
            'published_at' => $request->boolean('publish_now') ? now() : null,
        ]);

        return back()->with('success', 'Article créé.');
    }

    public function publishPost(\App\Models\Post $post)
    {
        $post->update(['published_at' => $post->published_at ? null : now()]);
        return back()->with('success', $post->published_at ? 'Article publié.' : 'Article dépublié.');
    }

    public function destroyPost(\App\Models\Post $post)
    {
        $post->delete();
        return back()->with('success', 'Article supprimé.');
    }

    public function stats()
    {
        try {
            $registrations = User::where('created_at', '>=', now()->subMonths(6)->startOfMonth())
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as total")
                ->groupBy('month')
                ->orderBy('month')
                ->pluck('total', 'month');

            $txByMonth = Transaction::where('status', 'success')
                ->where('created_at', '>=', now()->subMonths(6)->startOfMonth())
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, SUM(amount) as total, COUNT(*) as count")
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->keyBy('month');

            $months  = collect();
            $regData = collect();
            $txData  = collect();
            $txCount = collect();

            for ($i = 5; $i >= 0; $i--) {
                $key = now()->subMonths($i)->format('Y-m');
                $months->push(now()->subMonths($i)->isoFormat('MMM YY'));
                $regData->push((int) ($registrations[$key] ?? 0));
                $txData->push((int) ($txByMonth[$key]?->total ?? 0));
                $txCount->push((int) ($txByMonth[$key]?->count ?? 0));
            }

            $tontinesByType = Tontine::selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type');

            $topMembers = User::where('role', 'member')
                ->where('is_active', true)
                ->withCount(['transactions as success_tx_count' => fn($q) => $q->where('status', 'success')])
                ->orderByDesc('success_tx_count')
                ->take(5)
                ->get();

            return view('admin.stats', compact(
                'months', 'regData', 'txData', 'txCount',
                'tontinesByType', 'topMembers'
            ));
        } catch (\Throwable $e) {
            Log::error('Erreur stats admin', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Erreur lors du chargement des statistiques.']);
        }
    }
}
