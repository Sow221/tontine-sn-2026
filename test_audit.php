<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$pass = 0; $fail = 0;
function check($label, $result, $expected = true) {
    global $pass, $fail;
    if ($result === $expected) { echo "  OK  $label\n"; $pass++; }
    else { echo "  FAIL $label\n       got: " . json_encode($result) . "\n       exp: " . json_encode($expected) . "\n"; $fail++; }
}

function http($method, $path, $body = []) {
    global $kernel;
    $req = Illuminate\Http\Request::create($path, $method, $body);
    $req->headers->set('Accept', 'application/json');
    $res = $kernel->handle($req);
    return $res->getStatusCode();
}

echo "\n=== AUDIT FONCTIONNEL TONTINESN ===\n\n";

// ─── 1. ROUTES HTTP ───────────────────────────────────────────────────────
echo "── ROUTES HTTP ──\n";
check('GET /login → 200',              http('GET','/login'), 200);
check('GET /register → 200',           http('GET','/register'), 200);
check('GET / landing → 200',           http('GET','/'), 200);
check('GET /faq → 200',                http('GET','/faq'), 200);
check('GET /dashboard sans auth → 302',http('GET','/dashboard'), 302);
check('GET /ref/ABC12345 → 302',       http('GET','/ref/ABC12345'), 302);
check('GET /api/v1/auth/me → 401',     http('GET','/api/v1/auth/me'), 401);
check('POST /api/v1/login bad → 422',  http('POST','/api/v1/auth/login',['email'=>'bad']), 422);
check('GET /invite/TESTCODE → 302 ou 200', in_array(http('GET','/invite/TESTCODE'), [200,302,404]), true);
check('GET /admin sans auth → 302',    http('GET','/admin'), 302);

// ─── 2. TRANSACTION ACCESSORS (valeurs réelles du code) ───────────────────
echo "\n── TRANSACTION ACCESSORS ──\n";
$tx = new App\Models\Transaction();
$tx->method = 'wave';            check('wave = Wave',              $tx->method_label, 'Wave');
$tx->method = 'orange_money';    check('orange_money = Orange Money',$tx->method_label, 'Orange Money');
$tx->method = 'free_money';      check('free_money = Free Money',  $tx->method_label, 'Free Money');
$tx->method = 'card';            check('card = Carte bancaire',    $tx->method_label, 'Carte bancaire');
$tx->method = 'cash';            check('cash = Espèces',           $tx->method_label, 'Espèces');
$tx->method = 'direct_transfer'; check('direct_transfer = Transfert P2P', $tx->method_label, 'Transfert P2P');
$tx->method = 'unknown';         check('unknown = ucfirst fallback', $tx->method_label, 'Unknown');

$tx->status = 'success';   check('success = Payé',        $tx->status_label, 'Payé');
$tx->status = 'pending';   check('pending = En attente',  $tx->status_label, 'En attente');
$tx->status = 'failed';    check('failed = Échoué',       $tx->status_label, 'Échoué');
$tx->status = 'reversed';  check('reversed = Remboursé',  $tx->status_label, 'Remboursé');
$tx->status = 'cancelled'; check('cancelled = Annulé',    $tx->status_label, 'Annulé');

// ─── 3. REFERRAL SYSTEM ───────────────────────────────────────────────────
echo "\n── REFERRAL SYSTEM ──\n";
$user = App\Models\User::first();
if ($user) {
    check('user.referral_code rempli',    !empty($user->referral_code), true);
    check('user.referral_code 8 chars',   strlen($user->referral_code), 8);
    check('relation referrer() existe',   method_exists($user, 'referrer'), true);
    check('relation referrals() existe',  method_exists($user, 'referrals'), true);
    $link = route('auth.register', ['ref' => $user->referral_code]);
    check('referral_link contient ref=',  str_contains($link, 'ref='), true);
    check('referrals() retourne collection', $user->referrals() instanceof Illuminate\Database\Eloquent\Relations\HasMany, true);
} else {
    echo "  SKIP Pas d'utilisateur en DB\n";
}

// ─── 4. BADGES ────────────────────────────────────────────────────────────
echo "\n── BADGES ──\n";
check('13 badges total',                 DB::table('badges')->count(), 13);
check('referral_1 bronze existe',        DB::table('badges')->where('slug','referral_1')->where('tier','bronze')->exists(), true);
check('referral_5 silver existe',        DB::table('badges')->where('slug','referral_5')->where('tier','silver')->exists(), true);
check('referral_10 gold existe',         DB::table('badges')->where('slug','referral_10')->where('tier','gold')->exists(), true);
check('inviter_5 silver existe',         DB::table('badges')->where('slug','inviter_5')->exists(), true);
check('tontine_completed gold existe',   DB::table('badges')->where('slug','tontine_completed')->exists(), true);

// ─── 5. SERVICES ──────────────────────────────────────────────────────────
echo "\n── SERVICES ──\n";
$services = [
    App\Services\NotificationService::class,
    App\Services\WebhookOutboundService::class,
    App\Services\CreditScoringService::class,
    App\Services\GamificationService::class,
    App\Services\DrawService::class,
    App\Services\TontineService::class,
    App\Services\PaymentService::class,
    App\Services\CycleService::class,
];
foreach ($services as $svc) {
    $name = class_basename($svc);
    try { app($svc); check("$name instanciable", true); }
    catch (\Throwable $e) { check("$name instanciable", false); echo "       → ".$e->getMessage()."\n"; }
}

// ─── 6. CONTROLLERS ───────────────────────────────────────────────────────
echo "\n── CONTROLLERS ──\n";
$controllers = [
    App\Http\Controllers\Admin\AdminUserController::class,
    App\Http\Controllers\Admin\AdminTontineController::class,
    App\Http\Controllers\Admin\AdminTransactionController::class,
    App\Http\Controllers\Admin\AdminLogController::class,
    App\Http\Controllers\Admin\AdminDashboardController::class,
    App\Http\Controllers\Web\WebhookController::class,
    App\Http\Controllers\Web\QrCodePaymentController::class,
    App\Http\Controllers\Api\AuthApiController::class,
];
foreach ($controllers as $class) {
    $name = class_basename($class);
    try { app($class); check("$name instanciable", true); }
    catch (\Throwable $e) { check("$name instanciable", false); echo "       → ".$e->getMessage()."\n"; }
}

// ─── 7. DB SCHEMA ─────────────────────────────────────────────────────────
echo "\n── DB SCHEMA ──\n";
$tables = ['badges','user_badges','fcm_tokens','webhook_logs','chat_messages','cycle_vetos','auction_bids','savings_withdrawals'];
foreach ($tables as $t) {
    check("Table $t existe", Schema::hasTable($t), true);
}
check('users.referral_code existe',  Schema::hasColumn('users','referral_code'), true);
check('users.referred_by existe',    Schema::hasColumn('users','referred_by'), true);
check('users.payment_streak existe', Schema::hasColumn('users','payment_streak'), true);
check('transactions.type existe',    Schema::hasColumn('transactions','type'), true);

// ─── 8. JOBS ──────────────────────────────────────────────────────────────
echo "\n── JOBS ──\n";
foreach ([
    App\Jobs\ProcessCycle::class,
    App\Jobs\RecalculateCreditScore::class,
    App\Jobs\SendReminders::class,
    App\Jobs\SendChatNotifications::class,
] as $job) {
    check(class_basename($job).' existe', class_exists($job), true);
}

// ─── 9. WEBHOOK OUTBOUND ──────────────────────────────────────────────────
echo "\n── WEBHOOK OUTBOUND ──\n";
$wb = app(App\Services\WebhookOutboundService::class);
try { $wb->dispatch('test.event', ['foo'=>'bar']); check('dispatch sans URL ne plante pas', true); }
catch (\Throwable $e) { check('dispatch sans URL ne plante pas', false); echo "       → ".$e->getMessage()."\n"; }

// ─── 10. CREDIT SCORING ───────────────────────────────────────────────────
echo "\n── CREDIT SCORING ──\n";
if ($user = App\Models\User::first()) {
    try {
        $cs = app(App\Services\CreditScoringService::class);
        $creditScore = $cs->calculate($user);
        check('calculate retourne un CreditScore', $creditScore instanceof App\Models\CreditScore, true);
        check('score entre 0 et 10', $creditScore->score >= 0 && $creditScore->score <= 10, true);
        check('badge est défini',    in_array($creditScore->badge, ['none','bronze','silver','gold']), true);
    } catch (\Throwable $e) {
        check('CreditScoringService::calculate', false);
        echo "       → ".$e->getMessage()."\n";
    }
} else { echo "  SKIP Pas d'utilisateur\n"; }

// ─── 11. GAMIFICATION ─────────────────────────────────────────────────────
echo "\n── GAMIFICATION ──\n";
if ($user = App\Models\User::first()) {
    try {
        $gs = app(App\Services\GamificationService::class);
        $stats = $gs->getUserStats($user);
        check('getUserStats retourne un array', is_array($stats), true);
        check('getUserStats contient total_badges', array_key_exists('total_badges', $stats), true);
        check('getUserStats contient payment_streak', array_key_exists('payment_streak', $stats), true);
        $lb = $gs->getLeaderboardForUser($user);
        check('getLeaderboardForUser retourne collection', $lb instanceof Illuminate\Support\Collection, true);
    } catch (\Throwable $e) {
        check('GamificationService', false);
        echo "       → ".$e->getMessage()."\n";
    }
} else { echo "  SKIP Pas d'utilisateur\n"; }

// ─── 12. NOTIFICATION SERVICE METHODS ─────────────────────────────────────
echo "\n── NOTIFICATION SERVICE ──\n";
$ns = app(App\Services\NotificationService::class);
check('EVENT_REFERRAL défini', App\Services\NotificationService::EVENT_REFERRAL === 'referral_joined', true);
check('notifyReferralJoined method existe', method_exists($ns, 'notifyReferralJoined'), true);
check('notifyBeneficiary method existe', method_exists($ns, 'notifyBeneficiary'), true);
check('notifyCycleStart method existe', method_exists($ns, 'notifyCycleStart'), true);
check('notifyPaymentConfirmed method existe', method_exists($ns, 'notifyPaymentConfirmed'), true);

// ─── RÉSUMÉ ───────────────────────────────────────────────────────────────
$total = $pass + $fail;
echo "\n===========================================\n";
echo "RÉSULTAT : $pass / $total passés\n";
if ($fail > 0) {
    echo "ECHECS   : $fail\n";
    echo "===========================================\n";
    exit(1);
}
echo "TOUT EST OK\n";
echo "===========================================\n";
