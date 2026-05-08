<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Cycle;
use App\Models\Transaction;
use App\Services\PayTechService;
use App\Services\TontineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function __construct(
        private TontineService $tontineService,
        private PayTechService $payTechService
    ) {}

    public function showForm(Cycle $cycle)
    {
        $cycle->load('tontine');
        return view('cycles.pay', compact('cycle'));
    }

    public function initiate(Request $request, Cycle $cycle)
    {
        $request->validate([
            'method' => 'required|in:paytech,cash',
        ]);

        $user        = Auth::user();
        $amount      = $cycle->tontine->amount;
        $transaction = $this->tontineService->recordPayment($cycle, $user->id, $amount, $request->method);

        if ($request->method === 'cash') {
            return redirect()->route('tontines.show', $cycle->tontine)
                             ->with('success', 'Paiement cash enregistré.');
        }

        $result = $this->payTechService->initiatePayment($transaction);

        if (!$result['success']) {
            $transaction->update(['status' => 'failed', 'failure_reason' => $result['error']]);
            return back()->withErrors(['payment' => $result['error']]);
        }

        return redirect()->route('payment.pending', $transaction);
    }

    public function pending(Transaction $transaction)
    {
        abort_if($transaction->user_id !== Auth::id(), 403);
        $transaction->load('cycle.tontine');
        return view('cycles.payment-pending', compact('transaction'));
    }

    public function status(Transaction $transaction)
    {
        abort_if($transaction->user_id !== Auth::id(), 403);
        return response()->json([
            'status'       => $transaction->status,
            'redirect_url' => $transaction->status === 'success'
                ? route('tontines.show', $transaction->cycle->tontine_id)
                : null,
        ]);
    }

    public function failed()
    {
        return view('cycles.payment-failed');
    }

    // ── Webhook PayTech ────────────────────────────────────────────────────

    public function paytechWebhook(Request $request)
    {
        $data = $request->all();

        if (!$this->payTechService->verifyWebhook($data)) {
            return response()->json(['error' => 'Verification failed'], 401);
        }

        $ref         = $data['ref_command'] ?? '';
        $transactionId = str_replace('TontineSN-', '', $ref);
        $transaction = Transaction::find($transactionId);

        if ($transaction) {
            $this->tontineService->confirmPayment($transaction);
        }

        return response()->json(['status' => 'ok']);
    }
}
