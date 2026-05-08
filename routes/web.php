<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\TontineController;
use App\Http\Controllers\Web\PaymentController;
use App\Http\Controllers\Web\CycleController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\HistoriqueController;
use App\Http\Controllers\Admin\AdminDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => view('landing'))->name('home');

// ── Authentification ───────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login',    [AuthController::class, 'showLogin'])->name('auth.login');
    Route::post('/login',   [AuthController::class, 'login'])->name('auth.login.post')->middleware('throttle:10,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('auth.register');
    Route::post('/register',[AuthController::class, 'register'])->name('auth.register.post');
    Route::get('/auth/google',          [AuthController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
    // Mot de passe oublié
    Route::get('/forgot-password',        [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password',       [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password',        [AuthController::class, 'resetPassword'])->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout')->middleware('auth');

// ── Webhooks ───────────────────────────────────────────────────────────────
Route::post('/webhooks/paytech', [PaymentController::class, 'paytechWebhook'])->name('webhooks.paytech');
Route::get('/payment/failed',    [PaymentController::class, 'failed'])->name('payment.failed');

// ── Espace membre ──────────────────────────────────────────────────────────
Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Tontines
    Route::resource('tontines', TontineController::class);
    Route::post('/tontines/join',               [TontineController::class, 'join'])->name('tontines.join');
    Route::post('/tontines/{tontine}/activate', [TontineController::class, 'activate'])->name('tontines.activate');

    // Paiements
    Route::get('/cycles/{cycle}/pay',            [PaymentController::class, 'showForm'])->name('cycles.pay');
    Route::post('/cycles/{cycle}/pay',           [PaymentController::class, 'initiate'])->name('cycles.pay.initiate');
    Route::get('/payment/pending/{transaction}', [PaymentController::class, 'pending'])->name('payment.pending');
    Route::get('/payment/status/{transaction}',  [PaymentController::class, 'status'])->name('payment.status');
    Route::post('/cycles/{cycle}/draw',          [CycleController::class, 'draw'])->name('cycles.draw');

    // Profil
    Route::get('/profil',         [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profil',         [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profil/password',[ProfileController::class, 'updatePassword'])->name('profile.password');

    // Historique
    Route::get('/historique', [HistoriqueController::class, 'index'])->name('historique.index');

});

// ── Admin ──────────────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:admin,super_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/',                     [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/users',                [AdminDashboardController::class, 'users'])->name('users');
    Route::post('/users/{user}/toggle', [AdminDashboardController::class, 'toggleUser'])->name('users.toggle');
    Route::get('/logs',                 [AdminDashboardController::class, 'logs'])->name('logs');
});
