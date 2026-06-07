<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuctionBid;
use App\Models\Cycle;
use App\Models\CycleVeto;
use App\Services\CycleService;
use App\Services\DrawService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CycleController extends Controller
{
    public function __construct(
        private CycleService $cycleService,
        private DrawService $drawService,
        private NotificationService $notifier,
    ) {}

    public function draw(Cycle $cycle)
    {
        $this->authorize('update', $cycle->tontine);

        try {
            $error = $this->drawService->canDraw($cycle);
            if ($error) {
                return back()->withErrors(['draw' => $error]);
            }

            $this->drawService->drawBeneficiary($cycle);

            $cycle->refresh();
            if ($cycle->beneficiary_id) {
                $amount = $cycle->tontine->type === 'auction'
                    ? ($cycle->bid_amount ?? $cycle->tontine->amount * $cycle->tontine->activeMembers()->count())
                    : $cycle->tontine->amount * $cycle->tontine->activeMembers()->count();
                $this->notifier->notifyBeneficiary($cycle->beneficiary, $cycle->tontine->name, $amount);
            }

            return back()->with('success', 'Tirage effectué avec succès.');
        } catch (\Throwable $e) {
            Log::error('Erreur tirage', ['cycle' => $cycle->id, 'error' => $e->getMessage(), 'class' => get_class($e)]);
            return back()->withErrors(['draw' => 'Erreur lors du tirage. Veuillez réessayer.']);
        }
    }

    public function bid(Request $request, Cycle $cycle)
    {
        abort_unless($cycle->tontine->type === 'auction', 403);
        abort_unless(
            $cycle->tontine->members()->where('users.id', Auth::id())->wherePivot('status', 'active')->exists(),
            403
        );

        if ($cycle->beneficiary_id) {
            return back()->withErrors(['bid_rate' => 'Le tirage a déjà eu lieu, les enchères sont fermées.']);
        }

        if ($cycle->due_date->isPast()) {
            return back()->withErrors(['bid_rate' => 'La date limite des enchères est dépassée.']);
        }

        $request->validate([
            'bid_rate' => ['required', 'numeric', 'min:0.5', 'max:30'],
        ], [
            'bid_rate.required' => 'Le taux d\'enchère est obligatoire.',
            'bid_rate.min'      => 'Le taux minimum est 0.5%.',
            'bid_rate.max'      => 'Le taux maximum est 30%.',
        ]);

        try {
            AuctionBid::updateOrCreate(
                ['cycle_id' => $cycle->id, 'user_id' => Auth::id()],
                ['bid_rate' => $request->bid_rate]
            );

            return back()->with('success', 'Votre enchère de ' . $request->bid_rate . '% a été enregistrée.');
        } catch (\Throwable $e) {
            Log::error('Erreur enchère', ['cycle' => $cycle->id, 'error' => $e->getMessage(), 'class' => get_class($e)]);
            return back()->withErrors(['bid_rate' => 'Erreur lors de l\'enregistrement de l\'enchère.']);
        }
    }

    public function veto(Cycle $cycle)
    {
        $this->authorize('view', $cycle->tontine);

        if (!$cycle->beneficiary_id) {
            return back()->withErrors(['veto' => 'Aucun bénéficiaire désigné pour ce cycle.']);
        }

        if (!$this->drawService->canVeto($cycle, Auth::id())) {
            return back()->withErrors(['veto' => 'Vous n\'êtes pas autorisé à voter un veto sur ce cycle.']);
        }

        if ($this->drawService->isVetoed($cycle)) {
            return back()->withErrors(['veto' => 'Le seuil de véto est déjà atteint pour ce cycle.']);
        }

        if ($this->drawService->hasVoted($cycle, Auth::id())) {
            return back()->withErrors(['veto' => 'Vous avez déjà voté le véto pour ce cycle.']);
        }

        try {
            CycleVeto::create([
                'cycle_id' => $cycle->id,
                'user_id'  => Auth::id(),
            ]);

            if ($this->drawService->isVetoed($cycle)) {
                $this->drawService->applyVetoIfThresholdReached($cycle);
                $cycle->refresh();
                $newBeneficiary = $cycle->beneficiary?->name ?? 'inconnu';
                return back()->with('success', "Véto activé ! Nouveau bénéficiaire tiré : {$newBeneficiary}.");
            }

            $tontine   = $cycle->tontine;
            $required  = (int) ceil($tontine->activeMembers()->count() * $tontine->veto_threshold / 100);
            $remaining = max(0, $required - $this->drawService->vetoCount($cycle));

            return back()->with('success', "Vote de véto enregistré. Encore {$remaining} vote(s) requis pour annuler le tirage.");
        } catch (\Throwable $e) {
            Log::error('Erreur veto', ['cycle' => $cycle->id, 'error' => $e->getMessage(), 'class' => get_class($e)]);
            return back()->withErrors(['veto' => "Erreur lors du vote de véto."]);
        }
    }

    public function closeForcedSaving(Cycle $cycle)
    {
        $this->authorize('update', $cycle->tontine);
        abort_unless($cycle->tontine->type === 'forced_saving', 403);

        try {
            $withdrawals = $this->cycleService->closeForcedSaving($cycle->tontine);

            $tontine = $cycle->tontine;
            $memberIds = $tontine->activeMembers()->pluck('users.id');
            foreach ($withdrawals as $w) {
                if ($memberIds->contains($w['user_id'])) {
                    $member = \App\Models\User::find($w['user_id']);
                    if ($member) {
                        $this->notifier->notifySavingsWithdrawal($member, $tontine->name, $w['amount']);
                    }
                }
            }

            $summary = collect($withdrawals)
                ->map(fn($w) => $w['user'] . ' : ' . number_format($w['amount'], 0, ',', ' ') . ' FCFA')
                ->join(' | ');

            return back()->with('success', 'Épargne clôturée. Retraits à effectuer : ' . $summary);
        } catch (\Throwable $e) {
            Log::error('Erreur cloture epargne', ['cycle' => $cycle->id, 'error' => $e->getMessage(), 'class' => get_class($e)]);
            return back()->withErrors(['error' => 'Erreur lors de la clôture de l\'épargne.']);
        }
    }
}
