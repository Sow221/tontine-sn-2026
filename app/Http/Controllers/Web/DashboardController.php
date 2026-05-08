<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Tontine;
use App\Services\CreditScoringService;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(private CreditScoringService $scorer) {}

    public function index()
    {
        $user = Auth::user();

        $activeTontines = $user->memberships()
            ->wherePivot('status', 'active')
            ->with('currentCycle')
            ->get();

        $nextPayment = $activeTontines
            ->map(fn($t) => $t->currentCycle)
            ->filter()
            ->sortBy('due_date')
            ->first();

        $creditScore = $user->creditScore;

        if (!$creditScore) {
            dispatch(function () use ($user) {
                app(\App\Services\CreditScoringService::class)->calculate($user);
            })->afterResponse();

            $creditScore = new \App\Models\CreditScore(['score' => 0, 'badge' => 'none']);
        }

        $recentTransactions = $user->transactions()
            ->with('cycle.tontine')
            ->latest()
            ->take(5)
            ->get();

        return view('dashboard.index', compact(
            'user', 'activeTontines', 'nextPayment', 'creditScore', 'recentTransactions'
        ));
    }
}
