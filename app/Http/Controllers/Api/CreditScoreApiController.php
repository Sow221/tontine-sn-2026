<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CreditScoringService;
use Illuminate\Http\Request;

class CreditScoreApiController extends Controller
{
    public function __construct(private CreditScoringService $scorer) {}

    public function show(Request $request)
    {
        $user  = $request->user();
        $score = $user->creditScore ?? $this->scorer->calculate($user);

        return response()->json([
            'score'             => $score->score,
            'badge'             => $score->badge,
            'badge_label'       => $score->badgeLabel(),
            'total_contributed' => $score->total_contributed,
            'on_time_payments'  => $score->on_time_payments,
            'total_cycles'      => $score->total_cycles,
            'seniority_months'  => $score->seniority_months,
            'calculated_at'     => $score->calculated_at->toIso8601String(),
            'breakdown' => [
                'punctuality_rate' => $score->total_cycles > 0
                    ? round($score->on_time_payments / $score->total_cycles * 100, 1)
                    : 0,
                'amount_weight'      => round(min($score->total_contributed / 100000, 1) * 0.3 * 10, 2),
                'punctuality_weight' => round(($score->total_cycles > 0 ? $score->on_time_payments / $score->total_cycles : 0) * 0.5 * 10, 2),
                'seniority_weight'   => round(min($score->seniority_months / 12, 1) * 0.2 * 10, 2),
            ],
        ]);
    }

    public function refresh(Request $request)
    {
        $score = $this->scorer->calculate($request->user());

        return response()->json([
            'message' => 'Score recalculé.',
            'score'   => $score->score,
            'badge'   => $score->badge,
        ]);
    }
}
