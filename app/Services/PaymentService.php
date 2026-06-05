<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Cycle;
use App\Models\SavingsWithdrawal;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function __construct(
        private CycleService $cycleService,
        private DrawService $drawService,
        private NotificationService $notifier,
        private GamificationService $gamification,
    ) {}

    public function hasActivePayment(Cycle $cycle, int $userId): bool
    {
        return Transaction::forCycle($cycle->id)->forUser($userId)
            ->whereIn('status', ['success', 'pending'])
            ->exists();
    }

    public function recordPayment(Cycle $cycle, int $userId, int $amount, string $method, ?string $ref = null): Transaction
    {
        return DB::transaction(function () use ($cycle, $userId, $amount, $method, $ref) {
            $finalAmount = $amount;

            if ($cycle->isOverdue() && $cycle->tontine->penalty_rate > 0) {
                $penalty     = (int) round($amount * $cycle->tontine->penalty_rate / 100);
                $finalAmount = $amount + $penalty;
            }

            $transaction = Transaction::create([
                'cycle_id'           => $cycle->id,
                'user_id'            => $userId,
                'amount'             => $finalAmount,
                'method'             => $method,
                'external_reference' => $ref,
                'status'             => 'pending',
                'paid_at'            => null,
            ]);

            // cash reste pending jusqu'à confirmation manuelle du gérant

            return $transaction;
        });
    }

    public function confirmPayment(Transaction $transaction, ?int $verifiedAmount = null): void
    {
        if ($transaction->status === 'success') return;

        if ($verifiedAmount !== null && $verifiedAmount !== $transaction->amount) {
            Log::warning('Montant vérifié différent du montant attendu', [
                'transaction_id' => $transaction->id,
                'expected'       => $transaction->amount,
                'received'       => $verifiedAmount,
            ]);
        }

        DB::transaction(function () use ($transaction) {
            $transaction->update(['status' => 'success', 'paid_at' => now()]);

            $cycle = $transaction->cycle;
            $this->cycleService->updateCycleTotal($cycle);
            $this->gamification->updatePaymentStreak($transaction->user, $cycle, !$cycle->isOverdue());

            $cycle->refresh();
            $cycleWasPaid = $cycle->status === 'paid';

            if (
                $cycleWasPaid
                && !$cycle->beneficiary_id
                && !in_array($cycle->tontine->type, ['forced_saving', 'ceremonial'])
            ) {
                $this->drawService->drawBeneficiary($cycle);

                $cycle->refresh();
                if ($cycle->beneficiary_id) {
                    $amount = $cycle->tontine->amount * $cycle->tontine->activeMembers()->count();
                    $this->notifier->notifyBeneficiary(
                        $cycle->beneficiary,
                        $cycle->tontine->name,
                        $amount
                    );
                }
            }

            if ($cycleWasPaid) {
                $tontine = $cycle->tontine;
                $nextCycle = $tontine->currentCycle;

                if ($nextCycle && $nextCycle->id !== $cycle->id) {
                    $dueDate = $nextCycle->due_date->isoFormat('D MMMM YYYY');
                    $members = $tontine->activeMembers;

                    foreach ($members as $member) {
                        $this->notifier->notifyCycleStart(
                            $member,
                            $tontine->name,
                            $dueDate
                        );
                    }
                }
            }
        });
    }

    public function confirmWithdrawal(SavingsWithdrawal $withdrawal): void
    {
        $withdrawal->update(['status' => 'paid', 'paid_at' => now()]);
    }
}
