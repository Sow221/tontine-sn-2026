<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Cycle;
use App\Models\SavingsWithdrawal;
use App\Models\Tontine;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CycleService
{
    public function createCycles(Tontine $tontine): void
    {
        if ($tontine->cycles()->exists()) {
            return;
        }

        $members = $tontine->activeMembers()->count();
        if ($members === 0) {
            return;
        }

        DB::transaction(function () use ($tontine, $members) {
            match ($tontine->type) {
                'auction' => $this->createAuctionCycles($tontine, $members),
                'forced_saving' => $this->createForcedSavingCycles($tontine, $members),
                'ceremonial' => $this->createCeremonialCycles($tontine),
                default => $this->createFixedCycles($tontine, $members),
            };
        });
    }

    private function createFixedCycles(Tontine $tontine, int $members): void
    {
        $this->createRotatingCycles($tontine, $members);
    }

    private function createAuctionCycles(Tontine $tontine, int $members): void
    {
        $this->createRotatingCycles($tontine, $members);
    }

    private function createRotatingCycles(Tontine $tontine, int $members): void
    {
        $date = $tontine->start_date->copy();
        $cycles = [];

        for ($i = 1; $i <= $members; $i++) {
            $cycles[] = [
                'tontine_id' => $tontine->id,
                'cycle_number' => $i,
                'due_date' => $date->copy(),
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $date = $this->nextDate($date, $tontine->frequency);
        }

        if (! empty($cycles)) {
            Cycle::insert($cycles);
        }
    }

    private function createForcedSavingCycles(Tontine $tontine, int $members): void
    {
        $start = $tontine->start_date->copy();
        $end = $tontine->end_date
            ? $tontine->end_date->copy()
            : $start->copy()->addMonths(12);

        $date = $start->copy();
        $cycle = 1;
        $cycles = [];

        // Limite à 120 cycles : 10 ans hebdo ou ~10 ans mensuel — protège contre boucle infinie
        while ($date->lte($end) && $cycle <= 120) {
            $cycles[] = [
                'tontine_id' => $tontine->id,
                'cycle_number' => $cycle,
                'due_date' => $date->copy(),
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $date = $this->nextDate($date, $tontine->frequency);
            $cycle++;
        }

        if (! empty($cycles)) {
            Cycle::insert($cycles);
        }
    }

    private function createCeremonialCycles(Tontine $tontine): void
    {
        $eventDate = $tontine->end_date
            ? $tontine->end_date->copy()
            : $tontine->start_date->copy()->addMonth();

        Cycle::create([
            'tontine_id' => $tontine->id,
            'cycle_number' => 1,
            'due_date' => $eventDate,
            'status' => 'pending',
            'beneficiary_id' => $tontine->created_by,
        ]);
    }

    public function updateCycleTotal(Cycle $cycle): void
    {
        $total = $cycle->successfulTransactions()
            ->excludeRedistribution()
            ->sum('amount');
        $expected = $cycle->expectedTotal();

        $status = match (true) {
            $total >= $expected => 'paid',
            $total > 0 => 'partial',
            $cycle->isOverdue() => 'overdue',
            default => 'pending',
        };

        $cycle->update(['total_collected' => $total, 'status' => $status]);
    }

    public function closeForcedSaving(Tontine $tontine): array
    {
        if ($tontine->status === 'completed') {
            return [];
        }

        $withdrawals = [];

        DB::transaction(function () use ($tontine, &$withdrawals) {
            $members = $tontine->activeMembers()->lockForUpdate()->get();

            foreach ($members as $member) {
                $saved = Transaction::success()->forTontine($tontine->id)
                    ->forUser($member->id)
                    ->excludeRedistribution()
                    ->sum('amount');

                if ($saved > 0) {
                    SavingsWithdrawal::updateOrCreate(
                        ['tontine_id' => $tontine->id, 'user_id' => $member->id],
                        ['amount' => $saved, 'status' => 'pending']
                    );
                    $withdrawals[] = [
                        'user_id' => $member->id,
                        'user' => $member->name,
                        'amount' => $saved,
                    ];
                }
            }

            // Le statut completed n'est positionné qu'après la création de tous les withdrawals
            $tontine->update(['status' => 'completed']);
        });

        return $withdrawals;
    }

    private function nextDate(Carbon $date, string $frequency): Carbon
    {
        return match ($frequency) {
            'daily' => $date->copy()->addDay(),
            'weekly' => $date->copy()->addWeek(),
            'monthly' => $date->copy()->addMonth(),
            default => $date->copy()->addMonth(),
        };
    }
}
