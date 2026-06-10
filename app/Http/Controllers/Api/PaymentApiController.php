<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuctionBid;
use App\Models\Cycle;
use App\Models\Transaction;
use App\Services\DrawService;
use App\Services\PaymentService;
use App\Services\PayTechService;
use Illuminate\Http\Request;

class PaymentApiController extends Controller
{
    public function __construct(
        private PaymentService $paymentService,
        private PayTechService $payTechService,
        private DrawService $drawService,
    ) {}

    public function initiate(Request $request, Cycle $cycle)
    {
        $request->validate([
            'method' => ['required', 'in:wave,orange_money,free_money,card,cash'],
        ]);

        $user = $request->user();

        abort_unless(
            $cycle->tontine->members()->where('users.id', $user->id)->wherePivot('status', 'active')->exists(),
            403, 'Vous n\'êtes pas membre actif de cette tontine.'
        );

        if ($this->paymentService->hasActivePayment($cycle, $user->id)) {
            return response()->json(['message' => 'Paiement déjà effectué ou en cours pour ce cycle.'], 422);
        }

        $transaction = $this->paymentService->recordPayment(
            $cycle, $user->id, $cycle->tontine->amount, $request->method
        );

        if ($request->method === 'cash') {
            // Le cash reste pending — le créateur de la tontine le valide manuellement (cohérent avec le web)
            return response()->json([
                'status' => 'pending',
                'message' => 'Paiement en espèces enregistré. En attente de confirmation par le gestionnaire.',
                'transaction' => ['id' => $transaction->id, 'amount' => $transaction->amount],
            ]);
        }

        $result = $this->payTechService->initiatePayment($transaction);

        if (! $result['success']) {
            $transaction->update(['status' => 'failed', 'failure_reason' => $result['error']]);

            return response()->json(['message' => $result['error']], 422);
        }

        return response()->json([
            'status' => 'pending',
            'redirect_url' => $result['redirect_url'],
            'transaction' => ['id' => $transaction->id, 'amount' => $transaction->amount],
        ]);
    }

    public function bid(Request $request, Cycle $cycle)
    {
        abort_unless($cycle->tontine->type === 'auction', 422);
        abort_unless(
            $cycle->tontine->members()->where('users.id', $request->user()->id)->wherePivot('status', 'active')->exists(),
            403
        );
        abort_if($cycle->beneficiary_id !== null, 422);

        $request->validate([
            'bid_rate' => ['required', 'numeric', 'min:0.5', 'max:30'],
        ]);

        AuctionBid::updateOrCreate(
            ['cycle_id' => $cycle->id, 'user_id' => $request->user()->id],
            ['bid_rate' => $request->bid_rate]
        );

        return response()->json(['message' => 'Enchère de '.$request->bid_rate.'% enregistrée.']);
    }

    public function draw(Request $request, Cycle $cycle)
    {
        $this->authorize('update', $cycle->tontine);

        $error = $this->drawService->canDraw($cycle);
        if ($error) {
            return response()->json(['message' => $error], 422);
        }

        $this->drawService->drawBeneficiary($cycle);
        $cycle->refresh();

        return response()->json([
            'message' => 'Tirage effectué.',
            'beneficiary' => $cycle->beneficiary?->name,
            'draw_hash' => $cycle->draw_hash,
        ]);
    }

    public function status(Transaction $transaction)
    {
        abort_if($transaction->user_id !== request()->user()->id, 403);

        return response()->json([
            'id' => $transaction->id,
            'status' => $transaction->status,
            'amount' => $transaction->amount,
            'method' => $transaction->method,
            'paid_at' => $transaction->paid_at?->toIso8601String(),
        ]);
    }

    public function history(Request $request)
    {
        $transactions = $request->user()->transactions()
            ->with('cycle.tontine')
            ->latest()
            ->paginate(20);

        return response()->json($transactions->through(fn ($tx) => [
            'id' => $tx->id,
            'amount' => $tx->amount,
            'method' => $tx->method,
            'status' => $tx->status,
            'paid_at' => $tx->paid_at?->toIso8601String(),
            'tontine' => $tx->cycle?->tontine?->name,
            'cycle' => $tx->cycle?->cycle_number,
        ]));
    }
}
