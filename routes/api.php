<?php

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\ChatApiController;
use App\Http\Controllers\Api\CreditScoreApiController;
use App\Http\Controllers\Api\NotificationApiController;
use App\Http\Controllers\Api\PaymentApiController;
use App\Http\Controllers\Api\TontineApiController;
use App\Http\Controllers\Web\WebhookController;
use Illuminate\Support\Facades\Route;

// ── Webhook PayTech (pas d'auth, vérification interne par re-call API) ────
Route::post('/webhooks/paytech', [WebhookController::class, 'paytech'])
    ->name('webhooks.paytech')
    ->middleware('throttle:60,1');

// ── API v1 ─────────────────────────────────────────────────────────────────
Route::prefix('v1')->group(function () {

    // Auth publique
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthApiController::class, 'register'])->middleware('throttle:5,1');
        Route::post('/login', [AuthApiController::class, 'login'])->middleware('throttle:10,1');
    });

    // Routes protégées par token Sanctum
    Route::middleware(['auth:sanctum', 'check.user.active'])->group(function () {

        // Auth
        Route::post('/auth/logout', [AuthApiController::class, 'logout']);
        Route::get('/auth/me', [AuthApiController::class, 'me']);

        // Tontines
        Route::get('/tontines', [TontineApiController::class, 'index']);
        Route::post('/tontines', [TontineApiController::class, 'store']);
        Route::get('/tontines/{tontine}', [TontineApiController::class, 'show']);
        Route::post('/tontines/join', [TontineApiController::class, 'join']);
        Route::post('/tontines/{tontine}/activate', [TontineApiController::class, 'activate']);
        Route::post('/tontines/{tontine}/members/{user}/approve', [TontineApiController::class, 'approveMember']);

        // Cycles
        Route::post('/cycles/{cycle}/pay', [PaymentApiController::class, 'initiate']);
        Route::post('/cycles/{cycle}/bid', [PaymentApiController::class, 'bid']);
        Route::post('/cycles/{cycle}/draw', [PaymentApiController::class, 'draw']);

        // Transactions
        Route::get('/transactions/{transaction}', [PaymentApiController::class, 'status']);
        Route::get('/transactions', [PaymentApiController::class, 'history']);

        // Score de crédit
        Route::get('/credit-score', [CreditScoreApiController::class, 'show']);
        Route::post('/credit-score/refresh', [CreditScoreApiController::class, 'refresh']);

        // Chat
        Route::get('/tontines/{tontine}/chat', [ChatApiController::class, 'index']);
        Route::post('/tontines/{tontine}/chat', [ChatApiController::class, 'send']);
        Route::get('/tontines/{tontine}/chat/poll', [ChatApiController::class, 'poll']);
    });
});

// ── FCM Notifications (authentifiées mais hors du groupe v1) ────
Route::middleware(['auth:sanctum', 'check.user.active'])->group(function () {
    Route::post('/fcm-token', [NotificationApiController::class, 'registerFcmToken']);
});
