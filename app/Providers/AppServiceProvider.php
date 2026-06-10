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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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
        Route::aliasMiddleware('role', RoleMiddleware::class);

        Gate::policy(Tontine::class, TontinePolicy::class);

        View::composer('layouts.app', function ($view) {
            $unreadCount = 0;
            if (Auth::check()) {
                $cacheKey = 'unread_notifications_'.Auth::id();
                $unreadCount = Cache::remember($cacheKey, 30, function () {
                    return NotificationLog::where('user_id', Auth::id())->unread()->count();
                });
            }
            $view->with('unreadNotificationsCount', $unreadCount);
            // Expose le nonce CSP généré par SecurityHeaders middleware
            $view->with('cspNonce', request()->attributes->get('csp_nonce', ''));
        });
    }
}
