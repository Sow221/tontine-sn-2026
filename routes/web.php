<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminLogController;
use App\Http\Controllers\Admin\AdminTontineController;
use App\Http\Controllers\Admin\AdminTransactionController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Web\ApiDocsController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\ChatController;
use App\Http\Controllers\Web\CycleController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\FaqController;
use App\Http\Controllers\Web\HistoriqueController;
use App\Http\Controllers\Web\NotificationController;
use App\Http\Controllers\Web\PaymentController;
use App\Http\Controllers\Web\PostController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\QrCodePaymentController;
use App\Http\Controllers\Web\ThemeController;
use App\Http\Controllers\Web\TontineController;
use App\Http\Controllers\Web\TwoFactorController;
use App\Http\Controllers\Web\WebhookController;

Route::get('/api/docs', [ApiDocsController::class, 'index'])->name('api.docs');
Route::get('/api/spec', [ApiDocsController::class, 'spec'])->name('api.spec');
Route::get('/ref/{code}', function (string $code) {
    session(['referral_code' => strtoupper($code)]);

    return redirect()->route('auth.register');
})->name('referral')->middleware('throttle:30,1');

Route::get('/invite/{code}', [TontineController::class, 'showInvite'])->name('invite');
Route::get('/', fn () => view('landing'))->name('home');
Route::get('/offline', fn () => view('offline.index'))->name('offline');
Route::get('/cgu', fn () => view('legal.cgu'))->name('cgu');
Route::get('/mentions-legales', fn () => view('legal.mentions'))->name('mentions');
Route::get('/confidentialite', fn () => view('legal.privacy'))->name('privacy');
Route::get('/tontines/join', [TontineController::class, 'showJoinForm'])->name('tontines.join.form');
Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
Route::get('/posts/{post}', [PostController::class, 'show'])->name('posts.show');
Route::get('/posts/{post}/og', [PostController::class, 'ogImage'])->name('posts.og');
Route::get('/faq', [FaqController::class, 'index'])->name('faq.index');
Route::get('/tontines/og/{code}', [TontineController::class, 'ogInviteImage'])->name('tontines.og');
// ── Authentification ───────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('auth.login');
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login.post')->middleware('throttle:10,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('auth.register');
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register.post')->middleware('throttle:5,1');

    Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

    // Mot de passe oublié
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email')->middleware('throttle:3,1');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update')->middleware('throttle:3,1');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout')->middleware('auth');

// ── Webhooks ───────────────────────────────────────────────────────────────
Route::get('/payment/failed', [PaymentController::class, 'failed'])->name('payment.failed');
Route::post('/webhooks/greenapi', [WebhookController::class, 'greenapi'])->name('webhooks.greenapi');

// ── Espace membre ──────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:member'])->group(function () {

    // Onboarding
    Route::post('/onboarding/complete', function () {
        auth()->user()->update(['onboarding_completed' => true]);

        return response()->noContent();
    })->name('onboarding.complete');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/tontines/explorer', [TontineController::class, 'explore'])->name('tontines.explore');
    // Tontines
    Route::resource('tontines', TontineController::class);
    Route::post('/tontines/join', [TontineController::class, 'join'])->name('tontines.join');
    Route::post('/tontines/{tontine}/activate', [TontineController::class, 'activate'])->name('tontines.activate');
    Route::delete('/tontines/{tontine}/leave', [TontineController::class, 'leave'])->name('tontines.leave');
    Route::post('/tontines/{tontine}/transfer', [TontineController::class, 'transferOwnership'])->name('tontines.transfer');
    Route::post('/tontines/{tontine}/members/{user}/approve', [TontineController::class, 'approveMember'])->name('tontines.members.approve');
    Route::delete('/tontines/{tontine}/members/{user}/reject', [TontineController::class, 'rejectMember'])->name('tontines.members.reject');
    Route::post('/tontines/{tontine}/members/{user}/remind', [TontineController::class, 'remindMember'])->name('tontines.members.remind')->middleware('throttle:10,1');
    Route::post('/tontines/{tontine}/cash/{transaction}/confirm', [TontineController::class, 'confirmCashPayment'])->name('tontines.cash.confirm');
    Route::post('/tontines/{tontine}/beneficiary', [TontineController::class, 'setBeneficiary'])->name('tontines.beneficiary');
    Route::post('/withdrawals/{withdrawal}/confirm', [TontineController::class, 'confirmWithdrawal'])->name('withdrawals.confirm');

    // Paiements
    Route::get('/cycles/{cycle}/pay', [PaymentController::class, 'showForm'])->name('cycles.pay');
    Route::post('/cycles/{cycle}/pay', [PaymentController::class, 'initiate'])->name('cycles.pay.initiate');
    Route::get('/payment/pending/{transaction}', [PaymentController::class, 'pending'])->name('payment.pending');
    Route::get('/payment/status/{transaction}', [PaymentController::class, 'status'])->name('payment.status');
    Route::post('/cycles/{cycle}/draw', [CycleController::class, 'draw'])->name('cycles.draw');
    Route::post('/cycles/{cycle}/bid', [CycleController::class, 'bid'])->name('cycles.bid');
    Route::post('/cycles/{cycle}/close-saving', [CycleController::class, 'closeForcedSaving'])->name('cycles.close-saving');
    Route::post('/cycles/{cycle}/veto', [CycleController::class, 'veto'])->name('cycles.veto');
    Route::get('/transactions/{transaction}/recu', [PaymentController::class, 'receipt'])->name('transactions.receipt');
    Route::post('/transactions/{transaction}/reverse', [PaymentController::class, 'reverse'])->name('transactions.reverse')->middleware('throttle:3,1');
    Route::get('/payment/success/cash/{transaction}', [PaymentController::class, 'successCash'])->name('payment.success.cash');

    // QR Code Payments (P2P)
    Route::get('/qr-payment', [QrCodePaymentController::class, 'show'])->name('qr-payment.show');
    Route::post('/qr-payment/generate', [QrCodePaymentController::class, 'generate'])->name('qr-payment.generate');
    Route::get('/pay/{token}', [QrCodePaymentController::class, 'scan'])->name('qr-payment.scan');
    Route::post('/pay/{token}', [QrCodePaymentController::class, 'confirm'])->name('qr-payment.confirm');

    // Profil
    Route::get('/profil', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profil/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::delete('/compte', [ProfileController::class, 'deleteAccount'])->name('account.delete');
    Route::get('/profil/notifications', [ProfileController::class, 'notificationSettings'])->name('profile.notifications');
    Route::put('/profil/notifications', [ProfileController::class, 'updateNotificationSettings'])->name('profile.notifications.update');
    Route::post('/profil/kyc', [ProfileController::class, 'uploadKyc'])->name('profile.kyc');
    Route::get('/profil/export', [ProfileController::class, 'exportData'])->name('profile.export');
    Route::get('/members/{user}', [ProfileController::class, 'publicProfile'])->name('members.show');

    // 2FA
    Route::get('/profil/2fa', [TwoFactorController::class, 'setup'])->name('2fa.setup');
    Route::post('/profil/2fa/enable', [TwoFactorController::class, 'enable'])->name('2fa.enable')->middleware('throttle:5,1');
    Route::post('/profil/2fa/disable', [TwoFactorController::class, 'disable'])->name('2fa.disable')->middleware('throttle:5,1');

    // Historique
    Route::get('/historique', [HistoriqueController::class, 'index'])->name('historique.index');
    Route::get('/historique/export', [HistoriqueController::class, 'export'])->name('historique.export');
    Route::get('/historique/export/pdf', [HistoriqueController::class, 'exportPdf'])->name('historique.export.pdf');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');

    // Chat
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/{tontine}', [ChatController::class, 'show'])->name('chat.show');
    Route::get('/chat/{tontine}/stream', [ChatController::class, 'stream'])->name('chat.stream');
    Route::get('/chat/{tontine}/poll', [ChatController::class, 'poll'])->name('chat.poll');
    Route::post('/chat/{tontine}', [ChatController::class, 'send'])->name('chat.send')->middleware('throttle:20,1');
    Route::post('/chat/{tontine}/typing', [ChatController::class, 'typing'])->name('chat.typing');
});

// ── Admin ──────────────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:admin,super_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    // Users
    Route::get('/users', [AdminUserController::class, 'index'])->name('users');
    Route::get('/users/export', [AdminUserController::class, 'export'])->name('users.export');
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
    Route::post('/users/{user}/toggle', [AdminUserController::class, 'toggle'])->name('users.toggle');
    Route::post('/users/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.role');
    Route::post('/users/{user}/kyc/approve', [AdminUserController::class, 'approveKyc'])->name('users.kyc.approve');
    Route::post('/users/{user}/kyc/reject', [AdminUserController::class, 'rejectKyc'])->name('users.kyc.reject');
    Route::get('/users/{user}/kyc/review', [AdminUserController::class, 'kycReview'])->name('users.kyc.review');
    Route::get('/users/{user}/kyc/document', [AdminUserController::class, 'kycDocument'])->name('users.kyc.document');
    // Tontines
    Route::get('/tontines', [AdminTontineController::class, 'index'])->name('tontines');
    Route::get('/tontines/{tontine}', [AdminTontineController::class, 'show'])->name('tontines.show');
    Route::post('/tontines/{tontine}/suspend', [AdminTontineController::class, 'suspend'])->name('tontines.suspend');
    Route::post('/tontines/{tontine}/reactivate', [AdminTontineController::class, 'reactivate'])->name('tontines.reactivate');
    // Transactions
    Route::get('/transactions', [AdminTransactionController::class, 'index'])->name('transactions');
    Route::get('/transactions/export', [AdminTransactionController::class, 'export'])->name('transactions.export');
    Route::post('/transactions/{transaction}/force-confirm', [AdminTransactionController::class, 'forceConfirm'])->name('transactions.force-confirm');
    // Logs & Notifications
    Route::get('/logs', [AdminLogController::class, 'index'])->name('logs');
    Route::get('/logs/export', [AdminLogController::class, 'export'])->name('logs.export');
    Route::get('/notifications', [AdminLogController::class, 'notifications'])->name('notifications');
    Route::get('/stats', [AdminDashboardController::class, 'stats'])->name('stats');
    Route::get('/referrals', [AdminDashboardController::class, 'referrals'])->name('referrals');
    Route::get('/api-docs', [ApiDocsController::class, 'index'])->name('api.docs');
    Route::get('/posts', [AdminDashboardController::class, 'posts'])->name('posts');
    Route::post('/posts', [AdminDashboardController::class, 'storePost'])->name('posts.store');
    Route::post('/posts/{post}/publish', [AdminDashboardController::class, 'publishPost'])->name('posts.publish');
    Route::delete('/posts/{post}', [AdminDashboardController::class, 'destroyPost'])->name('posts.destroy');
});

// Route thème accessible à tous les authentifiés (admin inclus)
Route::middleware('auth')->post('/theme/toggle', [ThemeController::class, 'toggle'])->name('theme.toggle');
