<?php

namespace App\Services;

use App\Models\CreditScore;
use App\Models\User;

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

        $totalContributed = $user->transactions()
            ->where('status', 'success')
            ->sum('amount');

        $totalCycles = $user->transactions()
            ->where('status', 'success')
            ->count();

        $onTime = $user->transactions()
            ->where('status', 'success')
            ->whereHas('cycle', fn($q) => $q->whereColumn('transactions.paid_at', '<=', 'cycles.due_date'))
            ->count();

        $seniorityMonths = (int) $user->created_at->diffInMonths(now());

        $scoreAmount      = min(($totalContributed / $cfg['base_amount']) * $cfg['weight_amount'], $cfg['weight_amount']);
        $scorePunctuality = $totalCycles > 0
            ? ($onTime / $totalCycles) * $cfg['weight_punctuality']
            : 0;
        $scoreSeniority   = min(($seniorityMonths / $cfg['seniority_base']) * $cfg['weight_seniority'], $cfg['weight_seniority']);

        $score = round(($scoreAmount + $scorePunctuality + $scoreSeniority) * 10, 2);
        $score = min(max($score, 0), 10);

        $badge = $this->resolveBadge($score);

        return CreditScore::create([
            'user_id'          => $user->id,
            'score'            => $score,
            'total_contributed'=> $totalContributed,
            'on_time_payments' => $onTime,
            'total_cycles'     => $totalCycles,
            'seniority_months' => $seniorityMonths,
            'badge'            => $badge,
            'calculated_at'    => now(),
        ]);
    }

    private function resolveBadge(float $score): string
    {
        $badges = config('tontine.credit_score.badges');

        if ($score >= $badges['gold'])   return 'gold';
        if ($score >= $badges['silver']) return 'silver';
        if ($score >= $badges['bronze']) return 'bronze';

        return 'none';
    }
}
