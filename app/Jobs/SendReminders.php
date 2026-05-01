<?php

namespace App\Jobs;

use App\Models\Cycle;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(NotificationService $notifier): void
    {
        $reminderDays = config('tontine.notifications.reminder_days_before');

        foreach ($reminderDays as $days) {
            $targetDate = Carbon::today()->addDays($days);

            Cycle::with(['tontine.activeMembers'])
                ->where('due_date', $targetDate)
                ->where('status', '!=', 'paid')
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
    }
}
