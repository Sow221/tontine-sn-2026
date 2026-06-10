<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Tontine;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdminDashboardController extends Controller
{
    public function index()
    {
        try {
            $stats = Cache::remember('admin.stats', 120, function () {
                return [
                    'total_users' => User::count(),
                    'active_users' => User::where('is_active', true)->count(),
                    'active_tontines' => Tontine::where('status', 'active')->count(),
                    'pending_tontines' => Tontine::where('status', 'pending')->count(),
                    'total_transactions' => Transaction::where('status', 'success')->sum('amount'),
                    'pending_kyc' => User::kycPending()->count(),
                    'pending_tx' => Transaction::where('status', 'pending')->count(),
                ];
            });

            $chartRaw = Transaction::where('status', 'success')
                ->where('created_at', '>=', now()->subMonths(6)->startOfMonth())
                ->selectRaw(config('database.default') === 'sqlite'
                    ? "strftime('%Y-%m', created_at) as month, SUM(amount) as total"
                    : "DATE_FORMAT(created_at, '%Y-%m') as month, SUM(amount) as total")
                ->groupBy('month')
                ->orderBy('month')
                ->pluck('total', 'month');

            $chartMonths = collect();
            $chartAmounts = collect();
            for ($i = 5; $i >= 0; $i--) {
                $key = now()->subMonths($i)->format('Y-m');
                $chartMonths->push(now()->subMonths($i)->isoFormat('MMM YY'));
                $chartAmounts->push((int) ($chartRaw[$key] ?? 0));
            }

            $recentTontines = Cache::remember('admin.recent_tontines', 120, fn () => Tontine::with('creator')->latest()->take(8)->get()
            );

            $blockedTontines = Tontine::where('status', 'active')
                ->whereHas('cycles', fn ($q) => $q
                    ->where('status', 'overdue')
                    ->where('due_date', '<', now()->subDays(7))
                )
                ->with('creator')
                ->take(5)
                ->get();

            $suspiciousTx = Transaction::where('amount', '>', config('tontine.transaction.daily_limit'))
                ->with('user', 'cycle.tontine')
                ->latest()
                ->take(10)
                ->get();

            $pendingKycUsers = User::kycPending()->latest()->take(5)->get();

            $todayTransactions = Transaction::whereDate('created_at', today())->count();
            $todayUsers        = User::whereDate('created_at', today())->count();
            $todayKyc          = User::whereNotNull('kyc_document')->whereDate('updated_at', today())->count();

            return view('admin.dashboard', compact(
                'stats', 'recentTontines', 'suspiciousTx', 'pendingKycUsers',
                'chartMonths', 'chartAmounts', 'blockedTontines',
                'todayTransactions', 'todayUsers', 'todayKyc'
            ));
        } catch (\Throwable $e) {
            Log::error('Erreur admin dashboard', ['error' => $e->getMessage(), 'class' => get_class($e)]);

            return back()->withErrors(['error' => 'Erreur lors du chargement du dashboard.']);
        }
    }

    public function referrals()
    {
        try {
            $topReferrers = User::withCount('referrals')
                ->having('referrals_count', '>', 0)
                ->orderByDesc('referrals_count')
                ->take(20)
                ->get();

            $totalReferrals = User::whereNotNull('referred_by')->count();
            $convertedReferrals = User::whereNotNull('referred_by')
                ->whereHas('transactions', fn ($q) => $q->where('status', 'success'))
                ->count();

            $conversionRate = $totalReferrals > 0
                ? round($convertedReferrals / $totalReferrals * 100, 1)
                : 0;

            return view('admin.referrals', compact(
                'topReferrers', 'totalReferrals', 'convertedReferrals', 'conversionRate'
            ));
        } catch (\Throwable $e) {
            Log::error('Erreur stats parrainage', ['error' => $e->getMessage()]);

            return back()->withErrors(['error' => 'Erreur lors du chargement.']);
        }
    }

    public function stats()
    {
        try {
            $registrations = User::where('created_at', '>=', now()->subMonths(6)->startOfMonth())
                ->selectRaw(config('database.default') === 'sqlite'
                    ? "strftime('%Y-%m', created_at) as month, COUNT(*) as total"
                    : "DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as total")
                ->groupBy('month')
                ->orderBy('month')
                ->pluck('total', 'month');

            $txByMonth = Transaction::where('status', 'success')
                ->where('created_at', '>=', now()->subMonths(6)->startOfMonth())
                ->selectRaw(config('database.default') === 'sqlite'
                    ? "strftime('%Y-%m', created_at) as month, SUM(amount) as total, COUNT(*) as count"
                    : "DATE_FORMAT(created_at, '%Y-%m') as month, SUM(amount) as total, COUNT(*) as count")
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->keyBy('month');

            $months = collect();
            $regData = collect();
            $txData = collect();
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
                ->withCount(['transactions as success_tx_count' => fn ($q) => $q->where('status', 'success')])
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

    public function posts()
    {
        $posts = Post::with('author')->latest()->paginate(20);

        return view('admin.posts', compact('posts'));
    }

    public function storePost(Request $request)
    {
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
        ]);

        Post::create([
            'user_id' => auth()->id(),
            'title' => $request->title,
            'slug' => Str::slug($request->title).'-'.time(),
            'excerpt' => $request->excerpt,
            'content' => $request->content,
            'published_at' => $request->boolean('publish_now') ? now() : null,
        ]);

        return back()->with('success', 'Article créé.');
    }

    public function publishPost(Post $post)
    {
        $wasPublished = (bool) $post->published_at;
        $post->update(['published_at' => $wasPublished ? null : now()]);

        return back()->with('success', $wasPublished ? 'Article dépublié.' : 'Article publié.');
    }

    public function destroyPost(Post $post)
    {
        $post->delete();

        return back()->with('success', 'Article supprimé.');
    }
}
