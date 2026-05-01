<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\TontineController;
use App\Http\Controllers\Web\PaymentController;
use App\Http\Controllers\Web\CycleController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\UssdController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes publiques
|--------------------------------------------------------------------------
*/

Route::get('/', fn() => redirect()->route('auth.login'))->name('home');

// Authentification (OTP sans mot de passe)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('auth.login');
    Route::post('/login/send-otp', [AuthController::class, 'sendOtp'])->name('auth.send-otp')
         ->middleware('throttle:5,1');
    Route::get('/login/verify', [AuthController::class, 'showOtpForm'])->name('auth.otp.form');
    Route::post('/login/verify', [AuthController::class, 'verifyOtp'])->name('auth.verify-otp')
         ->middleware('throttle:10,1');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout')->middleware('auth');

/*
|--------------------------------------------------------------------------
| Webhooks (sans auth, avec vérification signature)
|--------------------------------------------------------------------------
*/

Route::post('/webhooks/wave', [PaymentController::class, 'waveWebhook'])->name('webhooks.wave');
Route::post('/webhooks/orange', [PaymentController::class, 'orangeWebhook'])->name('webhooks.orange');
Route::get('/payment/failed', [PaymentController::class, 'failed'])->name('payment.failed');

/*
|--------------------------------------------------------------------------
| USSD
|--------------------------------------------------------------------------
*/

Route::post('/ussd', [UssdController::class, 'handle'])->name('ussd.handle');

/*
|--------------------------------------------------------------------------
| Espace membre (authentifié)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Tontines
    Route::resource('tontines', TontineController::class);
    Route::post('/tontines/join', [TontineController::class, 'join'])->name('tontines.join');
    Route::post('/tontines/{tontine}/activate', [TontineController::class, 'activate'])->name('tontines.activate');

    // Cycles & Paiements
    Route::get('/cycles/{cycle}/pay', [PaymentController::class, 'showForm'])->name('cycles.pay');
    Route::post('/cycles/{cycle}/pay', [PaymentController::class, 'initiate'])->name('cycles.pay.initiate');
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
