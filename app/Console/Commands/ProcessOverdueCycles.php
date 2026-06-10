<?php

namespace App\Console\Commands;

use App\Models\Cycle;
use App\Models\Tontine;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CycleService;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class ProcessOverdueCycles extends Command
{
    protected $signature = 'tontine:process-overdue';

    protected $description = 'Marque les cycles en retard, notifie les membres, et clôture automatiquement les épargnes échües';

    public function handle(): int
    {
        $notifier = app(NotificationService::class);
        $cycleService = app(CycleService::class);

        // ── 1. Cycles en retard ─────────────────────────────────────────────────────
        $overdue = Cycle::with('tontine.activeMembers')
            ->whereIn('status', ['pending', 'partial'])
            ->where('due_date', '<', now())
            ->get();

        $overdueCount = 0;
        foreach ($overdue as $cycle) {
            if ($cycle->isOverdue()) {
                $cycle->update(['status' => 'overdue']);
                $overdueCount++;

                $paidMemberIds = Transaction::where('cycle_id', $cycle->id)
                    ->where('status', 'success')
                    ->pluck('user_id')
                    ->toArray();

                foreach ($cycle->tontine->activeMembers as $member) {
                    if (! in_array($member->id, $paidMemberIds)) {
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

        // ── 2. Clôture automatique des épargnes forcées arrivées à échéance ────────────
        $forcedSavingsDue = Tontine::where('type', 'forced_saving')
            ->where('status', 'active')
            ->whereNotNull('end_date')
            ->where('end_date', '<', now())
            ->get();

        $closedCount = 0;
        foreach ($forcedSavingsDue as $tontine) {
            try {
                $withdrawals = $cycleService->closeForcedSaving($tontine);
                foreach ($withdrawals as $w) {
                    $member = User::find($w['user_id']);
                    if ($member) {
                        $notifier->notifySavingsWithdrawal($member, $tontine->name, $w['amount']);
                    }
                }
                $closedCount++;
                $this->info("Tontine « {$tontine->name} » clôturée automatiquement ({$tontine->end_date->format('d/m/Y')}).");
            } catch (\Throwable $e) {
                $this->error("Erreur clôture {$tontine->id} : {$e->getMessage()}");
            }
        }

        $this->info("{$overdueCount} cycle(s) marqué(s) en retard.");
        $this->info("{$closedCount} épargne(s) forcée(s) clôturée(s) automatiquement.");

        return Command::SUCCESS;
    }
}
