<?php

use App\Jobs\SendReminders;
use App\Models\Cycle;
use App\Models\MagicLink;
use App\Models\Transaction;
use App\Services\NotificationService;
use App\Services\PaymentService;
use App\Services\PayTechService;
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

// Worker file d'attente (exécute les jobs WhatsApp en arrière-plan)
Schedule::command('queue:work --stop-when-empty --max-time=60 --sleep=3')->everyMinute()->withoutOverlapping();

// Nettoyage des magic links expirés
Schedule::call(fn () => MagicLink::where('expires_at', '<', now())->delete())->daily();

// Marquer les cycles en retard + clôture automatique des épargnes (tout en un)
Schedule::command('tontine:process-overdue')->dailyAt('06:00');

// Nettoyer les transactions pending > 2h ET re-vérifier auprès de PayTech
Schedule::call(function () {
    $stale = Transaction::where('status', 'pending')
        ->where('method', '!=', 'cash')
        ->where('created_at', '<', now()->subHours(2))
        ->get();

    $paytech = app(\App\Services\PayTechService::class);

    foreach ($stale as $tx) {
        $confirmed = false;

        // Re-vérifier auprès de PayTech si une référence externe existe
        if ($tx->external_reference) {
            try {
                $confirmed = $paytech->verifyWebhook(['token' => $tx->external_reference]);
            } catch (\Throwable) {
                $confirmed = false;
            }
        }

        if ($confirmed) {
            app(\App\Services\PaymentService::class)->confirmPayment($tx);
        } else {
            $tx->update([
                'status'         => 'failed',
                'failure_reason' => 'Délai dépassé — aucune confirmation PayTech reçue',
            ]);
        }
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
                        $days
                    );
                }
            });
    }
})->dailyAt('09:00');
