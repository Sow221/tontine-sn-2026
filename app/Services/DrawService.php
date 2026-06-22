<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuctionBid;
use App\Models\CreditScore;
use App\Models\Cycle;
use App\Models\CycleVeto;
use App\Models\TontineDebt;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class DrawService
{
    public function __construct(
        private NotificationService $notifier,
    ) {}

    public function canDraw(Cycle $cycle, bool $force = false): ?string
    {
        if ($cycle->beneficiary_id) {
            return 'Le tirage a déjà été effectué.';
        }

        $tontine = $cycle->tontine;

        if ($tontine->type === 'auction') {
            if ($cycle->auctionBids()->count() === 0) {
                return 'Aucune enchère soumise pour ce cycle.';
            }
        } else {
            $paidCount = $cycle->successfulTransactions()->count();
            $memberCount = $tontine->activeMembers()->count();

            if ($tontine->quorum > 1) {
                $required = (int) ceil($memberCount * $tontine->quorum / 100);
                if ($paidCount < $required) {
                    return 'Quorum non atteint : '.$paidCount.'/'.$required.' paiements requis.';
                }
            } else {
                if ($cycle->completionRate() < 100) {
                    if (! $force) {
                        return 'Tirage impossible : '.$cycle->completionRate().'% collecté seulement.';
                    }
                    if (! $cycle->due_date->isPast()) {
                        return 'Le tirage forcé n\'est possible qu\'après la date d\'échéance.';
                    }
                    if ($paidCount === 0) {
                        return 'Tirage impossible : aucun membre n\'a payé ce cycle.';
                    }
                }
            }
        }

        return null;
    }

    public function drawBeneficiary(Cycle $cycle, bool $force = false): void
    {
        if (in_array($cycle->tontine->type, ['forced_saving', 'ceremonial'])) {
            return;
        }

        DB::transaction(function () use ($cycle, $force) {
            // Verrou exclusif pour éviter un double tirage concurrent
            $locked = Cycle::lockForUpdate()->findOrFail($cycle->id);

            if ($locked->beneficiary_id) {
                return;
            }

            $tontine = $locked->tontine;

            // Tirage forcé : créer les dettes pour les membres n'ayant pas payé ce cycle
            $paidUserIds = collect();
            if ($force) {
                $paidUserIds = $locked->successfulTransactions()->pluck('user_id');
                $allMembers = $tontine->activeMembers()->get();

                foreach ($allMembers as $member) {
                    if (! $paidUserIds->contains($member->id)) {
                        TontineDebt::firstOrCreate(
                            ['cycle_id' => $locked->id, 'user_id' => $member->id],
                            ['tontine_id' => $tontine->id, 'amount' => $tontine->amount, 'status' => 'pending']
                        );
                        // Pénaliser le score crédit du débiteur
                        $score = CreditScore::firstOrCreate(
                            ['user_id' => $member->id],
                            ['score' => 5.0, 'badge' => 'silver']
                        );
                        $newScore = max(0, round($score->score - 1, 1));
                        $badge = $newScore >= 7 ? 'gold' : ($newScore >= 4 ? 'silver' : 'bronze');
                        $score->update(['score' => $newScore, 'badge' => $badge]);
                    }
                }
            }

            $alreadyWon = Cycle::where('tontine_id', $tontine->id)
                ->whereNotNull('beneficiary_id')
                ->pluck('beneficiary_id');

            // Membres éligibles :
            // - force : uniquement ceux qui ont payé CE cycle ET pas déjà gagnants
            // - normal : tous les actifs sans dette pendante ET pas déjà gagnants
            if ($force) {
                $eligible = $tontine->activeMembers()
                    ->whereNotIn('users.id', $alreadyWon)
                    ->whereIn('users.id', $paidUserIds)
                    ->get();
            } else {
                $debtorIds = TontineDebt::where('tontine_id', $tontine->id)
                    ->where('status', 'pending')
                    ->pluck('user_id');

                $eligible = $tontine->activeMembers()
                    ->whereNotIn('users.id', $alreadyWon)
                    ->whereNotIn('users.id', $debtorIds)
                    ->get();
            }

            if ($eligible->isEmpty()) {
                return;
            }

            if ($tontine->type === 'auction') {
                $winner = $this->resolveAuctionWinner($locked, $eligible);
            } elseif ($tontine->weighted_draw) {
                $winner = $this->weightedRandomDraw($eligible);
            } else {
                $winner = $tontine->draw_method === 'random'
                    ? $eligible->random()
                    : $eligible->sortBy(fn ($u) => $u->pivot->position)->first();
            }

            $hash = hash('sha256', $tontine->id.$locked->cycle_number.$winner->id.now()->timestamp);

            $locked->update([
                'beneficiary_id' => $winner->id,
                'draw_hash' => $hash,
                'drawn_at' => now(),
            ]);
        });

        $cycle->refresh();
    }

    /**
     * Applique le véto : annule le tirage en cours et en lance un nouveau immédiatement.
     * Retourne true si le seuil est atteint et le re-tirage effectué.
     */
    public function applyVetoIfThresholdReached(Cycle $cycle): bool
    {
        if (! $this->isVetoed($cycle)) {
            return false;
        }

        $vetoedUserId = $cycle->beneficiary_id;

        $cycle->update(['beneficiary_id' => null, 'draw_hash' => null, 'drawn_at' => null]);
        CycleVeto::where('cycle_id', $cycle->id)->delete();

        $tontine = $cycle->tontine;
        $alreadyWon = Cycle::where('tontine_id', $tontine->id)
            ->whereNotNull('beneficiary_id')
            ->pluck('beneficiary_id');
        $hasEligible = $tontine->activeMembers()
            ->whereNotIn('users.id', $alreadyWon)
            ->when($vetoedUserId, fn ($q) => $q->where('users.id', '!=', $vetoedUserId))
            ->exists();

        if (! $hasEligible) {
            $this->notifier->send(
                $tontine->creator,
                'general',
                "⚠️ Tous les membres ont déjà reçu le pot dans la tontine « {$tontine->name} ». Le tirage ne peut pas être relancé. Contactez les membres."
            );

            return true;
        }

        $this->drawBeneficiary($cycle);

        return true;
    }

    public function canVeto(Cycle $cycle, int $userId): bool
    {
        $tontine = $cycle->tontine;
        if (! $tontine->veto_threshold) {
            return false;
        }

        if ($cycle->beneficiary_id === $userId) {
            return false;
        }

        $member = $tontine->members()
            ->where('users.id', $userId)
            ->wherePivot('status', 'active')
            ->exists();

        return $member && $cycle->beneficiary_id !== null;
    }

    public function vetoCount(Cycle $cycle): int
    {
        return CycleVeto::where('cycle_id', $cycle->id)->count();
    }

    public function hasVoted(Cycle $cycle, int $userId): bool
    {
        return CycleVeto::where('cycle_id', $cycle->id)->where('user_id', $userId)->exists();
    }

    public function isVetoed(Cycle $cycle): bool
    {
        $tontine = $cycle->tontine;
        if (! $tontine->veto_threshold) {
            return false;
        }

        $total = $tontine->activeMembers()->count();
        $vetos = $this->vetoCount($cycle);
        $required = (int) ceil($total * $tontine->veto_threshold / 100);

        return $vetos >= $required;
    }

    private function weightedRandomDraw($eligible)
    {
        $scores = CreditScore::whereIn('user_id', $eligible->pluck('id'))
            ->selectRaw('user_id, MAX(score) as score')
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $weights = $eligible->mapWithKeys(fn ($u) => [
            $u->id => max(1, (int) (($scores->get($u->id)?->score ?? 0) * 10)),
        ]);

        $totalWeight = $weights->sum();
        if ($totalWeight <= 0) {
            return $eligible->first();
        }
        $rand = random_int(1, $totalWeight);
        $cumulative = 0;

        foreach ($weights as $userId => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) {
                return $eligible->firstWhere('id', $userId);
            }
        }

        return $eligible->first();
    }

    private function resolveAuctionWinner(Cycle $cycle, $eligible)
    {
        $bids = AuctionBid::where('cycle_id', $cycle->id)
            ->orderByDesc('bid_rate')
            ->orderBy('created_at')
            ->get()
            ->keyBy('user_id');

        $winner = $eligible
            ->sortByDesc(fn ($u) => $bids->get($u->id)?->bid_rate ?? 0)
            ->first();

        $winnerBid = $bids->get($winner->id);
        $pot = $cycle->tontine->amount * $eligible->count();

        if ($winnerBid) {
            $netAmount = (int) round($pot * (1 - $winnerBid->bid_rate / 100));
            $totalInterest = $pot - $netAmount;

            $cycle->update([
                'bid_amount' => $netAmount,
                'total_collected' => $pot,
            ]);

            $others = $eligible->where('id', '!=', $winner->id);
            $othersCount = $others->count();

            if ($othersCount > 0 && $totalInterest > 0) {
                $sharePerMember = (int) floor($totalInterest / $othersCount);
                $remainder = $totalInterest - ($sharePerMember * $othersCount);
                $first = true;

                foreach ($others as $member) {
                    $amount = $first ? $sharePerMember + $remainder : $sharePerMember;
                    $first = false;

                    Transaction::updateOrCreate(
                        [
                            'cycle_id' => $cycle->id,
                            'user_id' => $member->id,
                            'external_reference' => 'redistribution-'.$cycle->id.'-'.$member->id,
                        ],
                        [
                            'amount' => $amount,
                            'method' => 'direct_transfer',
                            'type' => 'redistribution',
                            'status' => 'success',
                            'paid_at' => now(),
                        ]
                    );
                }
            }

            $tontineName = $cycle->tontine->name;
            $cycleNum = $cycle->cycle_number;
            foreach ($eligible->where('id', '!=', $winner->id) as $loser) {
                try {
                    $this->notifier->sendEmail(
                        $loser,
                        "🏷️ Résultat enchère — {$tontineName}",
                        "Bonjour <strong>{$loser->name}</strong>,<br><br>"
                        ."L'enchère du cycle {$cycleNum} de la tontine <strong>{$tontineName}</strong> a été remportée par un autre membre.<br><br>"
                        ."Votre part d'intérêts vous a été redistribuée. Consultez votre historique pour le détail.",
                        'auction_lost'
                    );
                } catch (\Throwable) {
                }
            }
        }

        return $winner;
    }
}
