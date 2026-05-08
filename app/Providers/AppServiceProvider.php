<?php

namespace App\Providers;

use App\Http\Middleware\ActivityLogger;
use App\Http\Middleware\RoleMiddleware;
use App\Models\Tontine;
use App\Policies\TontinePolicy;
use App\Services\AuthService;
use App\Services\CreditScoringService;
use App\Services\MobileMoneyService;
use App\Services\NotificationService;
use App\Services\TontineService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AuthService::class);
        $this->app->singleton(TontineService::class);
        $this->app->singleton(CreditScoringService::class);
        $this->app->singleton(MobileMoneyService::class);
        $this->app->singleton(NotificationService::class);
    }

    public function boot(): void
    {
        Route::aliasMiddleware('role', RoleMiddleware::class);
        Route::aliasMiddleware('activity', ActivityLogger::class);

        Gate::policy(Tontine::class, TontinePolicy::class);
    }
}
