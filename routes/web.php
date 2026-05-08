<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\TontineController;
use App\Http\Controllers\Web\PaymentController;
use App\Http\Controllers\Web\CycleController;
use App\Http\Controllers\Admin\AdminDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes publiques
|--------------------------------------------------------------------------
*/

Route::get('/', fn() => view('landing'))->name('home');

// Authentification (Magic Link)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('auth.login');
    Route::post('/login/magic', [AuthController::class, 'sendMagicLink'])->name('auth.send-magic-link')
         ->middleware('throttle:5,1');
    Route::get('/login/sent', [AuthController::class, 'magicLinkSent'])->name('auth.magic.sent');
});

// Vérification accessible même si déjà connecté
Route::get('/login/verify', [AuthController::class, 'verifyMagicLink'])->name('auth.magic.verify');

Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout')->middleware('auth');

/*
|--------------------------------------------------------------------------
| Webhooks (sans auth, avec vérification signature)
|--------------------------------------------------------------------------
*/

Route::post('/webhooks/paytech', [PaymentController::class, 'paytechWebhook'])->name('webhooks.paytech');
Route::get('/payment/failed', [PaymentController::class, 'failed'])->name('payment.failed');

/*
|--------------------------------------------------------------------------
| Espace membre (authentifié)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('tontines', TontineController::class);
    Route::post('/tontines/join', [TontineController::class, 'join'])->name('tontines.join');
    Route::post('/tontines/{tontine}/activate', [TontineController::class, 'activate'])->name('tontines.activate');

    Route::get('/cycles/{cycle}/pay', [PaymentController::class, 'showForm'])->name('cycles.pay');
    Route::post('/cycles/{cycle}/pay', [PaymentController::class, 'initiate'])->name('cycles.pay.initiate');
    Route::get('/payment/pending/{transaction}', [PaymentController::class, 'pending'])->name('payment.pending');
    Route::get('/payment/status/{transaction}', [PaymentController::class, 'status'])->name('payment.status');
    Route::post('/cycles/{cycle}/draw', [CycleController::class, 'draw'])->name('cycles.draw');

});

/*
|--------------------------------------------------------------------------
| Espace Admin
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin,super_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/users', [AdminDashboardController::class, 'users'])->name('users');
    Route::post('/users/{user}/toggle', [AdminDashboardController::class, 'toggleUser'])->name('users.toggle');
    Route::get('/logs', [AdminDashboardController::class, 'logs'])->name('logs');
});
