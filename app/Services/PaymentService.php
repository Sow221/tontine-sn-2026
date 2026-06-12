<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\RecalculateCreditScore;
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
        private PayTechService $paytech,
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
            // Vérification atomique avec verrouillage pour éviter les doublons (race condition B2)
            $exists = Transaction::lockForUpdate()
                ->forCycle($cycle->id)
                ->forUser($userId)
                ->whereIn('status', ['success', 'pending'])
                ->exists();

            if ($exists) {
                throw new \RuntimeException('Vous avez déjà un paiement en cours ou effectué pour ce cycle.');
            }

            $finalAmount = $amount;

            if ($cycle->isOverdue() && $cycle->tontine->penalty_rate > 0) {
                $penalty = (int) round($amount * $cycle->tontine->penalty_rate / 100);
                $finalAmount = $amount + $penalty;
            }

            $transaction = Transaction::create([
                'cycle_id' => $cycle->id,
                'user_id' => $userId,
                'amount' => $finalAmount,
                'method' => $method,
                'type' => 'cotisation',
                'external_reference' => $ref,
                'status' => 'pending',
                'paid_at' => null,
            ]);

            return $transaction;
        });
    }

    public function confirmPayment(Transaction $transaction, ?int $verifiedAmount = null): void
    {
        $transaction = $transaction->fresh();
        if (! $transaction || $transaction->status === 'success') {
            return;
        }

        if ($verifiedAmount !== null && $verifiedAmount !== $transaction->amount) {
            Log::warning('Montant vérifié différent du montant attendu', [
                'transaction_id' => $transaction->id,
                'expected' => $transaction->amount,
                'received' => $verifiedAmount,
            ]);
        }

        $cycleWasPaid = false;
        $beneficiaryDrawn = false;
        $beneficiaryId = null;
        $beneficiaryAmount = 0;

        DB::transaction(function () use ($transaction, &$cycleWasPaid, &$beneficiaryDrawn, &$beneficiaryId, &$beneficiaryAmount) {
            $transaction->update(['status' => 'success', 'paid_at' => now()]);

            $transaction->load('cycle.tontine', 'user');

            $this->cycleService->updateCycleTotal($transaction->cycle);

            $cycle = $transaction->cycle->fresh();
            $cycle->load('tontine');
            $cycleWasPaid = $cycle->status === 'paid';

            if (
                $cycleWasPaid
                && ! $cycle->beneficiary_id
                && ! in_array($cycle->tontine->type, ['forced_saving', 'ceremonial'])
            ) {
                $this->drawService->drawBeneficiary($cycle);
                $cycle->refresh();

                if ($cycle->beneficiary_id) {
                    $beneficiaryDrawn = true;
                    $beneficiaryId = $cycle->beneficiary_id;
                    $beneficiaryAmount = $cycle->tontine->amount * $cycle->tontine->activeMembers()->count();
                }
            }
        });

        // Opérations hors transaction : notifications, jobs async
        $transaction->loadMissing('cycle.tontine', 'user');

        if ($transaction->user) {
            RecalculateCreditScore::dispatch($transaction->user->id)->afterResponse();
            $this->gamification->updatePaymentStreak($transaction->user, $transaction->cycle, ! $transaction->cycle->isOverdue());
        }

        $cycle = $transaction->cycle->fresh();
        $cycle->load('tontine');

        if ($beneficiaryDrawn && $beneficiaryId) {
            $cycle->load('beneficiary');
            if ($cycle->beneficiary) {
                $this->notifier->notifyBeneficiary(
                    $cycle->beneficiary,
                    $cycle->tontine->name,
                    $beneficiaryAmount
                );

                // Payout automatique si le bénéficiaire a un numéro de téléphone
                // et que PayTech payout est activé (configurable)
                if (config('tontine.payout.enabled', false) && $cycle->beneficiary->phone_number) {
                    $phone  = preg_replace('/[^0-9]/', '', $cycle->beneficiary->phone_number);
                    $method = config('tontine.payout.method', 'wave');
                    $ref    = 'PAYOUT-' . $cycle->id . '-' . $beneficiaryId;

                    $result = $this->paytech->sendPayout(
                        $beneficiaryId,
                        $beneficiaryAmount,
                        $method,
                        $phone,
                        $ref
                    );

                    if (!$result['success']) {
                        Log::error('Payout bénéficiaire échoué', [
                            'cycle_id'      => $cycle->id,
                            'beneficiary'   => $beneficiaryId,
                            'amount'        => $beneficiaryAmount,
                            'error'         => $result['error'] ?? 'inconnu',
                        ]);
                    } else {
                        // Enregistrer la transaction de redistribution
                        Transaction::create([
                            'cycle_id'           => $cycle->id,
                            'user_id'            => $beneficiaryId,
                            'amount'             => $beneficiaryAmount,
                            'method'             => $method,
                            'type'               => 'redistribution',
                            'external_reference' => 'redistribution-' . $ref,
                            'status'             => 'success',
                            'paid_at'            => now(),
                            'description'        => 'Pot distribué — Cycle ' . $cycle->cycle_number,
                        ]);
                    }
                }
            }
        }

        if ($cycleWasPaid) {
            $tontine = $cycle->tontine;
            $nextCycle = $tontine->currentCycle;

            if ($nextCycle && $nextCycle->id !== $cycle->id) {
                $dueDate = $nextCycle->due_date->isoFormat('D MMMM YYYY');
                $members = $tontine->activeMembers;

                foreach ($members as $member) {
                    $this->notifier->notifyCycleStart($member, $tontine->name, $dueDate);
                }
            }
        }
    }

    public function confirmWithdrawal(SavingsWithdrawal $withdrawal): void
    {
        if ($withdrawal->status === 'paid') {
            return;
        }

        $withdrawal->update(['status' => 'paid', 'paid_at' => now()]);
    }
}
