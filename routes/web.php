<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\TontineController;
use App\Http\Controllers\Web\PostController;
use App\Http\Controllers\Web\PaymentController;
use App\Http\Controllers\Web\CycleController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\HistoriqueController;
use App\Http\Controllers\Web\NotificationController;
use App\Http\Controllers\Web\FaqController;
use App\Http\Controllers\Web\ChatController;
use App\Http\Controllers\Web\ThemeController;
use App\Http\Controllers\Web\ApiDocsController;
use App\Http\Controllers\Admin\AdminDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/api/docs', [ApiDocsController::class, 'index'])->name('api.docs');
Route::get('/api/spec', [ApiDocsController::class, 'spec'])->name('api.spec');
Route::get('/invite/{code}', [TontineController::class, 'showInvite'])->name('invite');
Route::get('/', fn() => view('landing'))->name('home');
Route::get('/offline', fn() => view('offline.index'))->name('offline');
Route::get('/cgu', fn() => view('legal.cgu'))->name('cgu');
Route::get('/mentions-legales', fn() => view('legal.mentions'))->name('mentions');
Route::get('/confidentialite', fn() => view('legal.privacy'))->name('privacy');
Route::get('/tontines/join', [TontineController::class, 'showJoinForm'])->name('tontines.join.form');
Route::get('/posts',              [PostController::class, 'index'])->name('posts.index');
Route::get('/posts/{post}',       [PostController::class, 'show'])->name('posts.show');
Route::get('/posts/{post}/og',    [PostController::class, 'ogImage'])->name('posts.og');
Route::get('/faq',                [FaqController::class, 'index'])->name('faq.index');
Route::get('/tontines/og/{code}', [TontineController::class, 'ogInviteImage'])->name('tontines.og');
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
    Route::post('/forgot-password',       [AuthController::class, 'sendResetLink'])->name('password.email')->middleware('throttle:3,1');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password',        [AuthController::class, 'resetPassword'])->name('password.update')->middleware('throttle:3,1');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout')->middleware('auth');

// ── Webhooks ───────────────────────────────────────────────────────────────
Route::get('/payment/failed', [PaymentController::class, 'failed'])->name('payment.failed');

// ── Espace membre ──────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:member'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Tontines
    Route::resource('tontines', TontineController::class);
    Route::post('/tontines/join',               [TontineController::class, 'join'])->name('tontines.join');
    Route::post('/tontines/{tontine}/activate', [TontineController::class, 'activate'])->name('tontines.activate');
    Route::delete('/tontines/{tontine}/leave',  [TontineController::class, 'leave'])->name('tontines.leave');
    Route::post('/tontines/{tontine}/members/{user}/approve', [TontineController::class, 'approveMember'])->name('tontines.members.approve');
    Route::delete('/tontines/{tontine}/members/{user}/reject', [TontineController::class, 'rejectMember'])->name('tontines.members.reject');
    Route::post('/tontines/{tontine}/cash/{transaction}/confirm', [TontineController::class, 'confirmCashPayment'])->name('tontines.cash.confirm');
    Route::post('/tontines/{tontine}/beneficiary', [TontineController::class, 'setBeneficiary'])->name('tontines.beneficiary');
    Route::post('/withdrawals/{withdrawal}/confirm', [TontineController::class, 'confirmWithdrawal'])->name('withdrawals.confirm');

    // Paiements
    Route::get('/cycles/{cycle}/pay',            [PaymentController::class, 'showForm'])->name('cycles.pay');
    Route::post('/cycles/{cycle}/pay',           [PaymentController::class, 'initiate'])->name('cycles.pay.initiate');
    Route::get('/payment/pending/{transaction}', [PaymentController::class, 'pending'])->name('payment.pending');
    Route::get('/payment/status/{transaction}',  [PaymentController::class, 'status'])->name('payment.status');
    Route::post('/cycles/{cycle}/draw',          [CycleController::class, 'draw'])->name('cycles.draw');
    Route::post('/cycles/{cycle}/bid',           [CycleController::class, 'bid'])->name('cycles.bid');
    Route::post('/cycles/{cycle}/close-saving',  [CycleController::class, 'closeForcedSaving'])->name('cycles.close-saving');
    Route::post('/cycles/{cycle}/veto',          [CycleController::class, 'veto'])->name('cycles.veto');
    Route::get('/transactions/{transaction}/recu',     [PaymentController::class, 'receipt'])->name('transactions.receipt');
    Route::post('/transactions/{transaction}/reverse', [PaymentController::class, 'reverse'])->name('transactions.reverse')->middleware('throttle:3,1');
    Route::get('/payment/success/cash/{transaction}', [PaymentController::class, 'successCash'])->name('payment.success.cash');

    // Profil
    Route::get('/profil',                    [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profil',                    [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profil/password',           [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::delete('/compte',                 [ProfileController::class, 'deleteAccount'])->name('account.delete');
    Route::get('/profil/notifications',      [ProfileController::class, 'notificationSettings'])->name('profile.notifications');
    Route::put('/profil/notifications',      [ProfileController::class, 'updateNotificationSettings'])->name('profile.notifications.update');
    Route::post('/profil/kyc',               [ProfileController::class, 'uploadKyc'])->name('profile.kyc');
    Route::get('/profil/export',              [ProfileController::class, 'exportData'])->name('profile.export');
    Route::get('/members/{user}',            [ProfileController::class, 'publicProfile'])->name('members.show');

    // Historique
    Route::get('/historique',             [HistoriqueController::class, 'index'])->name('historique.index');
    Route::get('/historique/export',      [HistoriqueController::class, 'export'])->name('historique.export');
    Route::get('/historique/export/pdf',  [HistoriqueController::class, 'exportPdf'])->name('historique.export.pdf');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');

    // Chat
    Route::get('/chat',               [ChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/{tontine}',     [ChatController::class, 'show'])->name('chat.show');
    Route::post('/chat/{tontine}',    [ChatController::class, 'send'])->name('chat.send')->middleware('throttle:20,1');
});

// ── Admin ──────────────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:admin,super_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/',                                    [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/users',                               [AdminDashboardController::class, 'users'])->name('users');
    Route::get('/users/export',                        [AdminDashboardController::class, 'exportUsers'])->name('users.export');
    Route::get('/users/{user}',                        [AdminDashboardController::class, 'userDetail'])->name('users.show');
    Route::post('/users/{user}/toggle',                [AdminDashboardController::class, 'toggleUser'])->name('users.toggle');
    Route::post('/users/{user}/role',                  [AdminDashboardController::class, 'updateRole'])->name('users.role');
    Route::post('/users/{user}/kyc/approve',           [AdminDashboardController::class, 'approveKyc'])->name('users.kyc.approve');
    Route::post('/users/{user}/kyc/reject',            [AdminDashboardController::class, 'rejectKyc'])->name('users.kyc.reject');
    Route::get('/users/{user}/kyc/review',             [AdminDashboardController::class, 'kycReview'])->name('users.kyc.review');
    Route::get('/users/{user}/kyc/document',           [AdminDashboardController::class, 'kycDocument'])->name('users.kyc.document');
    Route::get('/tontines',                            [AdminDashboardController::class, 'tontines'])->name('tontines');
    Route::get('/tontines/{tontine}',                  [AdminDashboardController::class, 'tontineDetail'])->name('tontines.show');
    Route::post('/tontines/{tontine}/suspend',         [AdminDashboardController::class, 'suspendTontine'])->name('tontines.suspend');
    Route::post('/tontines/{tontine}/reactivate',      [AdminDashboardController::class, 'reactivateTontine'])->name('tontines.reactivate');
    Route::get('/transactions',                        [AdminDashboardController::class, 'transactions'])->name('transactions');
    Route::get('/transactions/export',                 [AdminDashboardController::class, 'exportTransactions'])->name('transactions.export');
    Route::post('/transactions/{transaction}/force-confirm', [AdminDashboardController::class, 'forceConfirmTransaction'])->name('transactions.force-confirm');
    Route::get('/logs',                                [AdminDashboardController::class, 'logs'])->name('logs');
    Route::get('/logs/export',                         [AdminDashboardController::class, 'exportLogs'])->name('logs.export');
    Route::get('/notifications',                       [AdminDashboardController::class, 'notifications'])->name('notifications');
    Route::get('/stats',                               [AdminDashboardController::class, 'stats'])->name('stats');
    Route::get('/api-docs',                            [ApiDocsController::class, 'index'])->name('api.docs');
    Route::get('/posts',                               [AdminDashboardController::class, 'posts'])->name('posts');
    Route::post('/posts',                              [AdminDashboardController::class, 'storePost'])->name('posts.store');
    Route::post('/posts/{post}/publish',               [AdminDashboardController::class, 'publishPost'])->name('posts.publish');
    Route::delete('/posts/{post}',                     [AdminDashboardController::class, 'destroyPost'])->name('posts.destroy');
});

// Route thème accessible à tous les authentifiés (admin inclus)
Route::middleware('auth')->post('/theme/toggle', [ThemeController::class, 'toggle'])->name('theme.toggle');