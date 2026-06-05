<?php

namespace App\Console\Commands;

use App\Models\Cycle;
use App\Models\Transaction;
use Illuminate\Console\Command;

class ProcessOverdueCycles extends Command
{
    protected $signature = 'tontine:process-overdue';
    protected $description = 'Marque les cycles en retard et envoie des notifications';

    public function handle(): int
    {
        $notifier = app(\App\Services\NotificationService::class);

        $overdue = Cycle::with('tontine.activeMembers')
            ->whereIn('status', ['pending', 'partial'])
            ->where('due_date', '<', now())
            ->get();

        $count = 0;
        foreach ($overdue as $cycle) {
            if ($cycle->isOverdue()) {
                $cycle->update(['status' => 'overdue']);
                $count++;

                // Notifier tous les membres actifs qui n'ont pas encore payé
                foreach ($cycle->tontine->activeMembers as $member) {
                    $hasPaid = \App\Models\Transaction::where('cycle_id', $cycle->id)
                        ->where('user_id', $member->id)
                        ->where('status', 'success')
                        ->exists();

                    if (!$hasPaid) {
                        $notifier->notifyPaymentReminder(
                            $member,
                            $cycle->tontine->name,
                            $cycle->tontine->amount,
                            0
                        );
                    }
                }
            }
        }

        $this->info("{$count} cycles marqués en retard.");

        return Command::SUCCESS;
    }
}
