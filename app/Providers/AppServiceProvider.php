<?php

namespace App\Providers;

use App\Http\Middleware\RoleMiddleware;
use App\Models\NotificationLog;
use App\Models\Tontine;
use App\Policies\TontinePolicy;
use App\Services\CreditScoringService;
use App\Services\CycleService;
use App\Services\DrawService;
use App\Services\GamificationService;
use App\Services\NotificationService;
use App\Services\PaymentService;
use App\Services\TontineService;
use App\Services\WebhookOutboundService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TontineService::class);
        $this->app->singleton(CreditScoringService::class);
        $this->app->singleton(NotificationService::class);
        $this->app->singleton(CycleService::class);
        $this->app->singleton(DrawService::class);
        $this->app->singleton(PaymentService::class);
        $this->app->singleton(GamificationService::class);
        $this->app->singleton(WebhookOutboundService::class);
    }

    public function boot(): void
    {
        Paginator::defaultView('pagination::bootstrap-5');
        Paginator::defaultSimpleView('pagination::simple-bootstrap-5');

        Route::aliasMiddleware('role', RoleMiddleware::class);

        Gate::policy(Tontine::class, TontinePolicy::class);

        // Share CSP nonce with ALL views (including child view push blocks,
        // which execute before the layouts.app composer fires).
        View::composer('*', function ($view) {
            $view->with('cspNonce', request()->attributes->get('csp_nonce', ''));
        });

        View::composer('layouts.app', function ($view) {
            $unreadCount = 0;
            $latestNotifications = collect();
            if (Auth::check()) {
                $cacheKey = 'unread_notifications_'.Auth::id();
                $unreadCount = Cache::remember($cacheKey, 30, function () {
                    return NotificationLog::where('user_id', Auth::id())->unread()->count();
                });
                $latestNotifications = NotificationLog::where('user_id', Auth::id())
                    ->latest()
                    ->limit(4)
                    ->get();
            }
            $view->with('unreadNotificationsCount', $unreadCount);
            $view->with('latestNotifications', $latestNotifications);

            $unreadMessages = 0;
            if (Auth::check()) {
                $msgCacheKey = 'unread_messages_'.Auth::id();
                $unreadMessages = Cache::remember($msgCacheKey, 60, function () {
                    return DB::table('tontine_members as tm')
                        ->join('chat_messages as cm', 'cm.tontine_id', '=', 'tm.tontine_id')
                        ->where('tm.user_id', Auth::id())
                        ->where('tm.status', 'active')
                        ->where('cm.user_id', '!=', Auth::id())
                        ->where(function ($q) {
                            $q->whereNull('tm.chat_last_seen_at')
                                ->orWhereRaw('cm.created_at > tm.chat_last_seen_at');
                        })
                        ->count();
                });
            }
            $view->with('unreadMessagesCount', $unreadMessages);
        });
    }
}
