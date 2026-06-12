<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Badge;
use App\Models\Cycle;
use App\Models\Tontine;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class GamificationService
{
    public function checkAndAwardBadges(User $user): Collection
    {
        $existing = $user->badges()->pluck('slug');
        $earned = collect();

        foreach ($this->qualifyingBadges($user) as $badge) {
            if ($existing->contains($badge->slug)) {
                continue;
            }

            // Ignorer le doublon en cas de race condition (double visite simultanée)
            DB::table('user_badges')->insertOrIgnore([
                'user_id' => $user->id,
                'badge_id' => $badge->id,
                'earned_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $earned->push($badge);
        }

        return $earned;
    }

    public function updatePaymentStreak(User $user, Cycle $cycle, bool $onTime): void
    {
        if ($onTime) {
            $user->increment('payment_streak');
            if ($user->payment_streak > $user->max_streak) {
                $user->updateQuietly(['max_streak' => $user->payment_streak]);
            }
        } else {
            $user->update(['payment_streak' => 0]);
        }
    }

    public function getLeaderboard(?Tontine $tontine = null, int $limit = 10): Collection
    {
        $query = User::query()
            ->select('users.id', 'users.name', 'users.avatar')
            ->selectRaw('(SELECT COUNT(*) FROM user_badges WHERE user_badges.user_id = users.id) as badge_count')
            ->selectRaw('COALESCE((SELECT score FROM credit_scores WHERE credit_scores.user_id = users.id ORDER BY calculated_at DESC LIMIT 1), 0) as credit_score')
            ->selectRaw('users.max_streak')
            ->where('is_active', true)
            ->where('role', 'member');

        if ($tontine) {
            $query->whereHas('memberships', fn ($q) => $q->where('tontine_id', $tontine->id)->where('tontine_members.status', 'active'));
        } else {
            $query->whereHas('memberships', fn ($q) => $q->where('tontine_members.status', 'active'));
        }

        return $query
            ->orderByDesc('credit_score')
            ->orderByDesc('max_streak')
            ->orderByDesc('badge_count')
            ->limit($limit)
            ->get();
    }

    public function getLeaderboardForUser(User $user, int $limit = 5): Collection
    {
        $tontineIds = $user->memberships()->pluck('tontines.id');

        if ($tontineIds->isEmpty()) {
            return collect();
        }

        $cacheKey = 'leaderboard.user.'.$user->id;

        return Cache::remember($cacheKey, 300, function () use ($tontineIds, $limit) {
            return User::query()
                ->select('users.id', 'users.name', 'users.avatar')
                ->selectRaw('(SELECT COUNT(*) FROM user_badges WHERE user_badges.user_id = users.id) as badge_count')
                ->selectRaw('COALESCE((SELECT score FROM credit_scores WHERE credit_scores.user_id = users.id ORDER BY calculated_at DESC LIMIT 1), 0) as credit_score')
                ->selectRaw('users.max_streak')
                ->where('is_active', true)
                ->where('role', 'member')
                ->whereHas('memberships', fn ($q) => $q->whereIn('tontine_id', $tontineIds)->where('tontine_members.status', 'active'))
                ->orderByDesc('credit_score')
                ->orderByDesc('max_streak')
                ->orderByDesc('badge_count')
                ->limit($limit)
                ->get();
        });
    }

    public function getUserStats(User $user): array
    {
        // Charge les badges une seule fois pour éviter la double requête
        $badges = $user->badges()->get();
        $badgeCountByTier = $badges->groupBy('tier')->map->count();

        return [
            'badges' => $badges,
            'total_badges' => $badges->count(),
            'bronze_count' => $badgeCountByTier->get('bronze', 0),
            'silver_count' => $badgeCountByTier->get('silver', 0),
            'gold_count' => $badgeCountByTier->get('gold', 0),
            'payment_streak' => $user->payment_streak,
            'max_streak' => $user->max_streak,
        ];
    }

    private function qualifyingBadges(User $user): Collection
    {
        $stats = $this->computeStats($user);

        // Cache des badges par utilisateur (1h) pour éviter des requêtes répétées
        $badges = Cache::remember('badges.all', 3600, fn () => Badge::all());

        return $badges->filter(function (Badge $badge) use ($stats): bool {
            $value = $stats[$badge->criteria_type] ?? 0;

            return $value >= $badge->criteria_value;
        })->values();
    }

    private function computeStats(User $user): array
    {
        $successfulTxs = $user->transactions()->where('transactions.status', 'success');

        return [
            'first_payment' => (int) $successfulTxs->exists(),
            'payment_streak' => $user->payment_streak,
            'beneficiary_count' => (int) Cycle::where('beneficiary_id', $user->id)->count(),
            'tontines_created' => (int) $user->tontines()->count(),
            'on_time_months' => $this->getOnTimeMonths($user),
            'tontine_completed' => (int) $user->memberships()
                ->wherePivot('status', 'active')
                ->where('tontines.status', 'completed')
                ->count(),
            'invited_members' => $this->getInvitedCount($user),
            'referrals_count' => (int) $user->referrals()->count(),
            'referral_payments_count' => $this->getReferralPaymentsCount($user),
        ];
    }

    private function getOnTimeMonths(User $user): int
    {
        $hasRecentPayments = $user->transactions()
            ->where('transactions.status', 'success')
            ->where('transactions.paid_at', '>=', now()->subMonths(3))
            ->exists();

        if (! $hasRecentPayments) {
            return 0;
        }

        $latePaymentExists = $user->transactions()
            ->where('transactions.status', 'success')
            ->join('cycles', 'cycles.id', '=', 'transactions.cycle_id')
            ->where('transactions.paid_at', '>=', now()->subMonths(3))
            ->whereRaw('DATE(transactions.paid_at) > DATE(cycles.due_date)')
            ->exists();

        return $latePaymentExists ? 0 : 3;
    }

    private function getInvitedCount(User $user): int
    {
        return DB::table('tontine_members')
            ->join('tontines', 'tontines.id', '=', 'tontine_members.tontine_id')
            ->where('tontines.created_by', $user->id)
            ->where('tontine_members.status', 'active')
            ->where('tontine_members.user_id', '!=', $user->id)
            ->whereNull('tontines.deleted_at')
            ->count();
    }

    private function getReferralPaymentsCount(User $user): int
    {
        return DB::table('users as referrals')
            ->join('transactions', 'transactions.user_id', '=', 'referrals.id')
            ->where('referrals.referred_by', $user->id)
            ->where('transactions.status', 'success')
            ->select('referrals.id')
            ->groupBy('referrals.id')
            ->havingRaw('COUNT(transactions.id) >= 3')
            ->count();
    }
}
