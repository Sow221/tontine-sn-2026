<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tontine;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users'        => User::count(),
            'active_tontines'    => Tontine::where('status', 'active')->count(),
            'total_transactions' => Transaction::where('status', 'success')->sum('amount'),
            'pending_kyc'        => User::where('kyc_verified', false)->count(),
        ];

        $recentTontines = Tontine::with('creator')->latest()->take(10)->get();
        $suspiciousTx   = Transaction::where('amount', '>', config('tontine.transaction.daily_limit'))
                                     ->with('user', 'cycle.tontine')
                                     ->latest()
                                     ->take(10)
                                     ->get();

        return view('admin.dashboard', compact('stats', 'recentTontines', 'suspiciousTx'));
    }

    public function users(Request $request)
    {
        $users = User::query()
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")
                                                  ->orWhere('phone_number', 'like', "%{$request->search}%"))
            ->when($request->role, fn($q) => $q->where('role', $request->role))
            ->latest()
            ->paginate(20);

        return view('admin.users', compact('users'));
    }

    public function toggleUser(User $user)
    {
        $user->update(['is_active' => !$user->is_active]);
        return back()->with('success', 'Statut utilisateur mis à jour.');
    }

    public function logs(Request $request)
    {
        $logs = \DB::table('activity_logs')
            ->join('users', 'users.id', '=', 'activity_logs.user_id')
            ->select('activity_logs.*', 'users.name', 'users.phone_number')
            ->latest('activity_logs.created_at')
            ->paginate(50);

        return view('admin.logs', compact('logs'));
    }
}
