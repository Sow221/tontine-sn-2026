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
use Illuminate\Support\Facades\Auth;
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
    }

    public function boot(): void
    {
        Route::aliasMiddleware('role', RoleMiddleware::class);

        Gate::policy(Tontine::class, TontinePolicy::class);

        View::composer('layouts.app', function ($view) {
            $unreadCount = Auth::check()
                ? NotificationLog::where('user_id', Auth::id())->unread()->count()
                : 0;
            $view->with('unreadNotificationsCount', $unreadCount);
        });
    }
}
