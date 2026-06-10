<?php

use App\Http\Middleware\ActivityLogger;
use App\Http\Middleware\CheckUserActive;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->redirectGuestsTo(fn () => route('auth.login'));
        $middleware->web(append: [
            SetLocale::class,
            SecurityHeaders::class,
            CheckUserActive::class,
            ActivityLogger::class,
        ]);
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'check.user.active' => CheckUserActive::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'webhooks/paytech',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
