<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Jobs\RecalculateCreditScore;
use App\Models\CreditScore;
use App\Models\Cycle;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CreditScoringService;
use App\Services\GamificationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function __construct(
        private CreditScoringService $scorer,
        private GamificationService $gamification,
    ) {}

    public function index()
    {
        $user = Auth::user();

        try {
            if ($user->isAdmin()) {
                return redirect()->route('admin.dashboard');
            }

            $activeTontines = $this->loadActiveTontines($user);
            $paidCycleIds = $this->paidCycleIds($activeTontines, $user);
            $pendingCycleIds = $this->pendingCycleIds($activeTontines, $user);

            $activeTontines = $this->mapPaymentStatuses($activeTontines, $paidCycleIds, $pendingCycleIds);

            $upcomingPayments = $this->upcomingPayments($activeTontines, $user);
            $overduePayments = $upcomingPayments->filter(fn ($c) => $c->isOverdue());

            $pendingMemberships = $user->memberships()->wherePivot('status', 'pending')->get();

            $creditScore = $user->creditScore;
            $scoreCalculating = false;

            if (! $creditScore) {
                $scoreCalculating = true;
                RecalculateCreditScore::dispatch($user->id)->afterResponse();

                $creditScore = new CreditScore(['score' => 0, 'badge' => 'none']);
            }

            $recentTransactions = $user->transactions()->with('cycle.tontine')->latest()->take(5)->get();

            $beneficiaireCycles = $this->beneficiaryCycles($user);

            // Badges : vérification toutes les heures, notification via session flash
            $newBadges = collect();
            $badgeKey = 'badges_checked_'.$user->id;
            if (now()->timestamp - (int) session($badgeKey, 0) > 3600) {
                $earned = $this->gamification->checkAndAwardBadges($user);
                if ($earned->isNotEmpty()) {
                    session()->flash('new_badges', $earned->map(fn ($b) => ['name' => $b->name, 'icon' => $b->icon])->toArray());
                }
                session()->put($badgeKey, now()->timestamp);
            }
            if (session()->has('new_badges')) {
                $newBadges = collect(session('new_badges'))->map(fn ($b) => (object) $b);
            }
            $gamification = $this->gamification->getUserStats($user);
            $leaderboard = Cache::remember("leaderboard_{$user->id}", 900, fn () => $this->gamification->getLeaderboardForUser($user, 5));
            $chartData = Cache::remember("chart_data_{$user->id}", 3600, fn () => $this->chartData($user));

            return view('dashboard.index', compact(
                'user', 'activeTontines', 'upcomingPayments', 'overduePayments',
                'pendingMemberships', 'creditScore', 'recentTransactions',
                'beneficiaireCycles', 'newBadges', 'gamification', 'leaderboard',
                'chartData', 'scoreCalculating',
            ));
        } catch (\Throwable $e) {
            Log::error('Erreur dashboard', ['user_id' => Auth::id(), 'error' => $e->getMessage(), 'class' => get_class($e)]);
            $user = $user ?? Auth::user() ?? new User;
            $creditScore = new CreditScore(['score' => 0, 'badge' => 'none']);

            return view('dashboard.index', compact('user') + [
                'activeTontines' => collect(), 'upcomingPayments' => collect(),
                'overduePayments' => collect(), 'pendingMemberships' => collect(),
                'creditScore' => $creditScore, 'recentTransactions' => collect(),
                'beneficiaireCycles' => collect(), 'newBadges' => collect(),
                'gamification' => ['total_badges' => 0, 'bronze_count' => 0, 'silver_count' => 0, 'gold_count' => 0, 'payment_streak' => 0, 'max_streak' => 0, 'badges' => collect()],
                'leaderboard' => collect(),
                'chartData' => ['months' => collect(), 'payments' => collect()],
                'scoreCalculating' => false,
            ]);
        }
    }

    private function loadActiveTontines($user): Collection
    {
        return $user->memberships()
            ->wherePivot('status', 'active')
            ->with('currentCycle')
            ->withCount(['cycles', 'members as active_members_count' => fn ($q) => $q->where('tontine_members.status', 'active')])
            ->get();
    }

    private function paidCycleIds(Collection $activeTontines, $user): array
    {
        $ids = $activeTontines->map(fn ($t) => $t->currentCycle?->id)->filter()->values();

        return Transaction::success()->whereIn('cycle_id', $ids)
            ->forUser($user->id)
            ->pluck('cycle_id')
            ->toArray();
    }

    private function pendingCycleIds(Collection $activeTontines, $user): array
    {
        $ids = $activeTontines->map(fn ($t) => $t->currentCycle?->id)->filter()->values();

        return Transaction::pending()->whereIn('cycle_id', $ids)
            ->forUser($user->id)
            ->pluck('cycle_id')
            ->toArray();
    }

    private function mapPaymentStatuses(Collection $tontines, array $paidIds, array $pendingIds): Collection
    {
        return $tontines->map(function ($t) use ($paidIds, $pendingIds) {
            $cycleId = $t->currentCycle?->id;
            $t->has_paid_current = $cycleId && in_array($cycleId, $paidIds, true);
            $t->payment_pending = $cycleId && in_array($cycleId, $pendingIds, true);
            $t->my_position = $t->pivot->position ?? null;
            $t->pot_total = $t->amount * $t->active_members_count;

            return $t;
        });
    }

    private function upcomingPayments(Collection $activeTontines, $user): Collection
    {
        // Batch la vérification des paiements pour éviter le N+1
        $cycles = $activeTontines->map(fn ($t) => $t->currentCycle)->filter();
        $cycleIds = $cycles->pluck('id')->values();

        $alreadyPaidIds = Transaction::success()
            ->whereIn('cycle_id', $cycleIds)
            ->forUser($user->id)
            ->pluck('cycle_id')
            ->flip();

        return $cycles
            ->reject(fn ($c) => $alreadyPaidIds->has($c->id))
            ->sortBy('due_date')
            ->values();
    }

    private function beneficiaryCycles($user): Collection
    {
        return Cycle::where('beneficiary_id', $user->id)
            ->whereHas('tontine', fn ($q) => $q->whereHas('members', fn ($q2) => $q2->where('users.id', $user->id)))
            ->where('status', '!=', 'paid')
            ->whereNotNull('drawn_at')
            ->with(['tontine' => fn ($q) => $q->withCount(['members as active_members_count' => fn ($q2) => $q2->where('tontine_members.status', 'active')])])
            ->get();
    }

    private function chartData($user): array
    {
        // DATE_FORMAT est MySQL-specific — utilisation de strftime pour SQLite en test, DATE_FORMAT en prod
        $driver = config('database.default');
        $dateFmt = $driver === 'sqlite'
            ? "strftime('%Y-%m', paid_at) as month"
            : "DATE_FORMAT(paid_at, '%Y-%m') as month";

        $chartRaw = Transaction::success()->forUser($user->id)
            ->where('paid_at', '>=', now()->subMonths(12)->startOfMonth())
            ->selectRaw("{$dateFmt}, SUM(amount) as total")
            ->groupBy('month')
            ->pluck('total', 'month');

        $chartMonths = collect();
        $chartPayments = collect();

        for ($i = 11; $i >= 0; $i--) {
            $key = now()->subMonths($i)->format('Y-m');
            $chartMonths->push(now()->subMonths($i)->isoFormat('MMM YY'));
            $chartPayments->push((int) ($chartRaw[$key] ?? 0));
        }

        return ['months' => $chartMonths, 'payments' => $chartPayments];
    }
}
