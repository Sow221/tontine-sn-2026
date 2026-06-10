<?php

use App\Jobs\SendReminders;
use App\Models\Cycle;
use App\Models\MagicLink;
use App\Models\Tontine;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CycleService;
use App\Services\NotificationService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Recalcul hebdomadaire des scores crédit de tous les membres actifs
Schedule::command('tontine:recalculate-scores')->weeklyOn(0, '03:00');

// Rappels de paiement J-3 et J-1
Schedule::job(new SendReminders)->dailyAt('08:00');

// Nettoyage des magic links expirés
Schedule::call(fn () => MagicLink::where('expires_at', '<', now())->delete())->daily();

// Marquer les cycles en retard
Schedule::command('tontine:process-overdue')->dailyAt('06:00');

// Clôture automatique des tontines forced_saving dont la date de fin est dépassée
Schedule::call(function () {
    $cycleService = app(CycleService::class);
    $notifier = app(NotificationService::class);

    Tontine::where('type', 'forced_saving')
        ->where('status', 'active')
        ->where('end_date', '<', now()->startOfDay())
        ->each(function ($tontine) use ($cycleService, $notifier) {
            $withdrawals = $cycleService->closeForcedSaving($tontine);
            foreach ($withdrawals as $w) {
                $member = User::find($w['user_id']);
                if ($member) {
                    $notifier->notifySavingsWithdrawal($member, $tontine->name, $w['amount']);
                }
            }
        });
})->dailyAt('07:00');

// Nettoyer les transactions pending qui n'ont jamais abouti (plus de 2h)
Schedule::call(function () {
    $stale = Transaction::where('status', 'pending')
        ->where('method', '!=', 'cash')
        ->where('created_at', '<', now()->subHours(2))
        ->get();

    foreach ($stale as $tx) {
        $tx->update([
            'status' => 'failed',
            'failure_reason' => 'Trop de temps écoulé depuis l\'initiation',
        ]);
    }
})->hourly();

// Relances pour cycles en retard (J+1, J+3, J+7 après échéance)
Schedule::call(function () {
    $overdueDays = config('tontine.notifications.overdue_days_after', [1, 3, 7]);
    $notifier = app(NotificationService::class);

    foreach ($overdueDays as $days) {
        $targetDate = now()->subDays($days)->startOfDay();

        Cycle::with(['tontine.activeMembers'])
            ->where('status', 'overdue')
            ->whereDate('due_date', $targetDate)
            ->each(function (Cycle $cycle) use ($days, $notifier) {
                foreach ($cycle->tontine->activeMembers as $member) {
                    $notifier->notifyPaymentReminder(
                        $member,
                        $cycle->tontine->name,
                        $cycle->tontine->amount,
                        $days,
                        true
                    );
                }
            });
    }
})->dailyAt('09:00');
