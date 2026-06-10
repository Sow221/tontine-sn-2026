<?php

namespace App\Services;

use App\Models\CreditScore;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreditScoringService
{
    /**
     * Calcule et persiste le score crédit d'un utilisateur.
     *
     * Score = (total_contribué / 100000) × 0.3
     *       + (paiements_à_temps / cycles_total) × 0.5
     *       + (ancienneté_mois / 12) × 0.2
     */
    public function calculate(User $user): CreditScore
    {
        $cfg = config('tontine.credit_score');

        $driver = config('database.default');
        $onTimeExpr = $driver === 'sqlite'
            ? 'CASE WHEN DATE(transactions.paid_at) <= DATE(cycles.due_date) THEN 1 ELSE 0 END'
            : 'CASE WHEN DATE(transactions.paid_at) <= cycles.due_date THEN 1 ELSE 0 END';

        $aggregate = $user->transactions()
            ->where('transactions.status', 'success')
            ->leftJoin('cycles', 'cycles.id', '=', 'transactions.cycle_id')
            ->selectRaw('COALESCE(SUM(transactions.amount), 0) as total_contributed')
            ->selectRaw('COUNT(transactions.id) as total_cycles')
            ->selectRaw("COALESCE(SUM({$onTimeExpr}), 0) as on_time")
            ->first();

        $totalContributed = (int) $aggregate->total_contributed;
        $totalCycles = (int) $aggregate->total_cycles;
        $onTime = (int) $aggregate->on_time;

        $seniorityMonths = (int) $user->created_at->diffInMonths(now());

        // Bonus parrainage : +0.1 par filleul actif, plafonné à 0.5
        $referralBonus = min($user->referrals()->count() * 0.1, 0.5);

        $scoreAmount = min(($totalContributed / $cfg['base_amount']) * $cfg['weight_amount'], $cfg['weight_amount']);
        $scorePunctuality = $totalCycles > 0
            ? ($onTime / $totalCycles) * $cfg['weight_punctuality']
            : 0;
        $scoreSeniority = min(($seniorityMonths / $cfg['seniority_base']) * $cfg['weight_seniority'], $cfg['weight_seniority']);

        $score = round(($scoreAmount + $scorePunctuality + $scoreSeniority + $referralBonus) * 10, 2);
        $score = min(max($score, 0), 10);

        $badge = $this->resolveBadge($score);

        return DB::transaction(function () use ($user, $score, $totalContributed, $onTime, $totalCycles, $seniorityMonths, $badge) {
            return CreditScore::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'score' => $score,
                    'total_contributed' => $totalContributed,
                    'on_time_payments' => $onTime,
                    'total_cycles' => $totalCycles,
                    'seniority_months' => $seniorityMonths,
                    'badge' => $badge,
                    'calculated_at' => now(),
                ]
            );
        });
    }

    private function resolveBadge(float $score): string
    {
        $badges = config('tontine.credit_score.badges');

        if ($score >= $badges['gold']) {
            return 'gold';
        }
        if ($score >= $badges['silver']) {
            return 'silver';
        }
        if ($score >= $badges['bronze']) {
            return 'bronze';
        }

        return 'none';
    }
}
