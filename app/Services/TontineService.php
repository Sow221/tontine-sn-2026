<?php

namespace App\Services;

use App\Models\Cycle;
use App\Models\Tontine;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TontineService
{
    public function createCycles(Tontine $tontine): void
    {
        $members = $tontine->activeMembers()->count();
        if ($members === 0) return;

        $date = Carbon::parse($tontine->start_date);

        DB::transaction(function () use ($tontine, $members, $date) {
            for ($i = 1; $i <= $members; $i++) {
                Cycle::create([
                    'tontine_id'   => $tontine->id,
                    'cycle_number' => $i,
                    'due_date'     => $date->copy(),
                    'status'       => 'pending',
                ]);
                $date = $this->nextDate($date, $tontine->frequency);
            }
        });
    }

    public function drawBeneficiary(Cycle $cycle): void
    {
        $tontine = $cycle->tontine;

        $alreadyWon = Cycle::where('tontine_id', $tontine->id)
            ->whereNotNull('beneficiary_id')
            ->pluck('beneficiary_id');

        $eligible = $tontine->activeMembers()
            ->whereNotIn('users.id', $alreadyWon)
            ->get();

        if ($eligible->isEmpty()) return;

        $winner = $tontine->draw_method === 'random'
            ? $eligible->random()
            : $eligible->sortBy(fn($u) => $u->pivot->position)->first();

        $hash = hash('sha256', $tontine->id . $cycle->cycle_number . $winner->id . now()->timestamp);

        $cycle->update([
            'beneficiary_id' => $winner->id,
            'draw_hash'      => $hash,
            'drawn_at'       => now(),
        ]);
    }

    public function recordPayment(Cycle $cycle, int $userId, int $amount, string $method, ?string $ref = null): Transaction
    {
        return DB::transaction(function () use ($cycle, $userId, $amount, $method, $ref) {
            $transaction = Transaction::create([
                'cycle_id'           => $cycle->id,
                'user_id'            => $userId,
                'amount'             => $amount,
                'method'             => $method,
                'external_reference' => $ref,
                'status'             => $method === 'cash' ? 'success' : 'pending',
                'paid_at'            => $method === 'cash' ? now() : null,
            ]);

            if ($method === 'cash') {
                $this->updateCycleTotal($cycle);
            }

            return $transaction;
        });
    }

    public function confirmPayment(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $transaction->update(['status' => 'success', 'paid_at' => now()]);
            $this->updateCycleTotal($transaction->cycle);
        });
    }

    private function updateCycleTotal(Cycle $cycle): void
    {
        $total = $cycle->successfulTransactions()->sum('amount');
        $expected = $cycle->expectedTotal();

        $status = match(true) {
            $total >= $expected => 'paid',
            $total > 0          => 'partial',
            $cycle->isOverdue() => 'overdue',
            default             => 'pending',
        };

        $cycle->update(['total_collected' => $total, 'status' => $status]);
    }

    private function nextDate(Carbon $date, string $frequency): Carbon
    {
        return match($frequency) {
            'daily'   => $date->addDay(),
            'weekly'  => $date->addWeek(),
            'monthly' => $date->addMonth(),
        };
    }
}
