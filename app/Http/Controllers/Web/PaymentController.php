<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Cycle;
use App\Models\Transaction;
use App\Services\MobileMoneyService;
use App\Services\TontineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function __construct(
        private TontineService $tontineService,
        private MobileMoneyService $mmService
    ) {}

    public function showForm(Cycle $cycle)
    {
        $cycle->load('tontine');
        return view('cycles.pay', compact('cycle'));
    }

    public function initiate(Request $request, Cycle $cycle)
    {
        $request->validate([
            'method' => 'required|in:wave,orange_money,cash',
        ]);

        $user   = Auth::user();
        $amount = $cycle->tontine->amount;

        $transaction = $this->tontineService->recordPayment(
            $cycle, $user->id, $amount, $request->method
        );

        if ($request->method === 'cash') {
            return redirect()->route('tontines.show', $cycle->tontine)
                             ->with('success', 'Paiement cash enregistré.');
        }

        $result = $request->method === 'wave'
            ? $this->mmService->initiateWave($transaction, $user->phone_number)
            : $this->mmService->initiateOrangeMoney($transaction, $user->phone_number);

        if (!$result['success']) {
            $transaction->update(['status' => 'failed', 'failure_reason' => $result['error']]);
            return back()->withErrors(['payment' => $result['error']]);
        }

        $redirectUrl = $result['checkout_url'] ?? $result['payment_url'] ?? null;

        return $redirectUrl
            ? redirect()->away($redirectUrl)
            : back()->withErrors(['payment' => 'URL de paiement non disponible.']);
    }

    public function failed()
    {
        return view('cycles.payment-failed');
    }

    // ── Webhooks ───────────────────────────────────────────────────────────

    public function waveWebhook(Request $request)
    {
        $signature = $request->header('Wave-Signature', '');

        if (!$this->mmService->verifyWaveWebhook($request->getContent(), $signature)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $data = $request->json()->all();

        if (($data['status'] ?? '') === 'succeeded') {
            $transaction = Transaction::where('external_reference', $data['id'])->first();
            if ($transaction) {
                $this->tontineService->confirmPayment($transaction);
            }
        }

        return response()->json(['status' => 'ok']);
    }

    public function orangeWebhook(Request $request)
    {
        $data = $request->all();

        if (($data['status'] ?? '') === 'SUCCESS') {
            $transaction = Transaction::where('external_reference', $data['pay_token'])->first();
            if ($transaction) {
                $this->tontineService->confirmPayment($transaction);
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
