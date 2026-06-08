<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuctionBid;
use App\Models\CreditScore;
use App\Models\Cycle;
use App\Models\CycleVeto;
use App\Models\Transaction;

class DrawService
{
    public function canDraw(Cycle $cycle): ?string
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
            $paidCount   = $cycle->successfulTransactions()->count();
            $memberCount = $tontine->activeMembers()->count();

            // Si un quorum est configuré, vérifier uniquement le quorum
            if ($tontine->quorum > 1) {
                $required = (int) ceil($memberCount * $tontine->quorum / 100);
                if ($paidCount < $required) {
                    return 'Quorum non atteint : ' . $paidCount . '/' . $required . ' paiements requis.';
                }
            } else {
                // Sans quorum configuré, on exige 100%
                if ($cycle->completionRate() < 100) {
                    return 'Tirage impossible : ' . $cycle->completionRate() . '% collecté seulement.';
                }
            }
        }

        return null;
    }

    public function drawBeneficiary(Cycle $cycle): void
    {
        if (in_array($cycle->tontine->type, ['forced_saving', 'ceremonial'])) return;

        $tontine = $cycle->tontine;

        $alreadyWon = Cycle::where('tontine_id', $tontine->id)
            ->whereNotNull('beneficiary_id')
            ->pluck('beneficiary_id');

        $eligible = $tontine->activeMembers()
            ->whereNotIn('users.id', $alreadyWon)
            ->get();

        if ($eligible->isEmpty()) return;

        if ($tontine->type === 'auction') {
            $winner = $this->resolveAuctionWinner($cycle, $eligible);
        } elseif ($tontine->weighted_draw) {
            $winner = $this->weightedRandomDraw($eligible);
        } else {
            $winner = $tontine->draw_method === 'random'
                ? $eligible->random()
                : $eligible->sortBy(fn($u) => $u->pivot->position)->first();
        }

        $hash = hash('sha256', $tontine->id . $cycle->cycle_number . $winner->id . now()->timestamp);

        $cycle->update([
            'beneficiary_id' => $winner->id,
            'draw_hash'      => $hash,
            'drawn_at'       => now(),
        ]);
    }

    /**
     * Applique le véto : annule le tirage en cours et en lance un nouveau immédiatement.
     * Retourne true si le seuil est atteint et le re-tirage effectué.
     */
    public function applyVetoIfThresholdReached(Cycle $cycle): bool
    {
        if (!$this->isVetoed($cycle)) return false;

        // Sauvegarder le bénéficiaire veté avant de l'effacer
        $vetoedUserId = $cycle->beneficiary_id;

        // Annuler le tirage et supprimer les votes
        $cycle->update(['beneficiary_id' => null, 'draw_hash' => null, 'drawn_at' => null]);
        CycleVeto::where('cycle_id', $cycle->id)->delete();

        // Vérifier qu'il reste des membres éligibles avant le re-tirage
        $tontine    = $cycle->tontine;
        $alreadyWon = Cycle::where('tontine_id', $tontine->id)
            ->whereNotNull('beneficiary_id')
            ->pluck('beneficiary_id');
        $eligible = $tontine->activeMembers()
            ->whereNotIn('users.id', $alreadyWon)
            ->when($vetoedUserId, fn($q) => $q->where('users.id', '!=', $vetoedUserId))
            ->count();

        if ($eligible === 0) {
            // Aucun membre éligible : marquer le cycle comme bloqué via un statut
            // On laisse beneficiary_id null et on notifie le créateur
            app(\App\Services\NotificationService::class)->send(
                $tontine->creator,
                'general',
                "⚠️ Tous les membres ont déjà reçu le pot dans la tontine \u00ab {$tontine->name} \u00bb. Le tirage ne peut pas être rellancé. Contactez les membres."
            );
            return true;
        }

        // Re-tirer immédiatement un nouveau bénéficiaire
        $this->drawBeneficiary($cycle);

        return true;
    }

    public function canVeto(Cycle $cycle, int $userId): bool
    {
        $tontine = $cycle->tontine;
        if (!$tontine->veto_threshold) return false;

        if ($cycle->beneficiary_id === $userId) return false;

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
        if (!$tontine->veto_threshold) return false;

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

        $weights = $eligible->mapWithKeys(fn($u) => [
            $u->id => max(1, (int) (($scores->get($u->id)?->score ?? 0) * 10)),
        ]);

        $totalWeight = $weights->sum();
        if ($totalWeight <= 0) return $eligible->first();
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
            ->sortByDesc(fn($u) => $bids->get($u->id)?->bid_rate ?? 0)
            ->first();

        $winnerBid = $bids->get($winner->id);
        $pot       = $cycle->tontine->amount * $eligible->count();

        if ($winnerBid) {
            $netAmount     = (int) round($pot * (1 - $winnerBid->bid_rate / 100));
            $totalInterest = $pot - $netAmount;

            $cycle->update([
                'bid_amount'      => $netAmount,
                'total_collected' => $pot,
            ]);

            $others = $eligible->where('id', '!=', $winner->id);
            $othersCount = $others->count();

            if ($othersCount > 0 && $totalInterest > 0) {
                $sharePerMember = (int) floor($totalInterest / $othersCount);
                // Le reste est attribué au premier membre pour ne rien perdre
                $remainder = $totalInterest - ($sharePerMember * $othersCount);
                $first = true;

                foreach ($others as $member) {
                    $amount = $first ? $sharePerMember + $remainder : $sharePerMember;
                    $first  = false;

                    Transaction::updateOrCreate(
                        [
                            'cycle_id'           => $cycle->id,
                            'user_id'            => $member->id,
                            'external_reference' => 'redistribution-' . $cycle->id . '-' . $member->id,
                        ],
                        [
                            'amount'  => $amount,
                            'method'  => 'cash',
                            'status'  => 'success',
                            'paid_at' => now(),
                        ]
                    );
                }
            }
        }

        return $winner;
    }
}
