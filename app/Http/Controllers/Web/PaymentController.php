<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Cycle;
use App\Models\Transaction;
use App\Services\NotificationService;
use App\Services\PaymentService;
use App\Services\PayTechService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $paymentService,
        private PayTechService $payTechService,
        private NotificationService $notifier,
    ) {}

    public function showForm(Cycle $cycle)
    {
        $cycle->load('tontine');

        abort_unless(
            $cycle->tontine->members()
                ->where('users.id', Auth::id())
                ->wherePivot('status', 'active')
                ->exists(),
            403
        );

        try {
            $penalty     = $cycle->isOverdue() && $cycle->tontine->penalty_rate > 0
                ? (int) round($cycle->tontine->amount * $cycle->tontine->penalty_rate / 100)
                : 0;
            $totalAmount = $cycle->tontine->amount + $penalty;
        } catch (\Throwable $e) {
            Log::error('Erreur affichage formulaire paiement', ['cycle' => $cycle->id, 'error' => $e->getMessage(), 'class' => get_class($e)]);
            return back()->withErrors(['error' => 'Impossible de charger le formulaire de paiement.']);
        }

        return view('cycles.pay', compact('cycle', 'penalty', 'totalAmount'));
    }

    public function initiate(Request $request, Cycle $cycle)
    {
        $request->validate([
            'method' => 'required|in:wave,orange_money,free_money,card,cash',
        ]);

        $user = Auth::user();

        abort_unless(
            $cycle->tontine->members()
                ->where('users.id', $user->id)
                ->wherePivot('status', 'active')
                ->exists(),
            403
        );

        try {
            if ($this->paymentService->hasActivePayment($cycle, $user->id)) {
                return back()->withErrors(['payment' => 'Vous avez déjà un paiement en cours ou effectué pour ce cycle.']);
            }

            $amount      = $cycle->tontine->amount;
            $transaction = $this->paymentService->recordPayment($cycle, $user->id, $amount, $request->method);

            if ($request->method === 'cash') {
                // Le paiement cash reste en pending — le créateur de la tontine le valide manuellement
                return redirect()->route('payment.pending', $transaction);
            }

            $result = $this->payTechService->initiatePayment($transaction);

            if (!$result['success']) {
                $errorMsg = $result['error'] ?? 'Erreur de paiement. Veuillez réessayer.';
                $transaction->update(['status' => 'failed', 'failure_reason' => $errorMsg]);
                Log::error('Paiement échoué', ['transaction' => $transaction->id, 'error' => $errorMsg]);
                return back()->withErrors(['payment' => 'Le paiement a échoué. Veuillez réessayer ou choisir un autre mode de paiement.']);
            }

            return redirect()->away($result['redirect_url']);
        } catch (\Throwable $e) {
            Log::error('Erreur initiation paiement', ['cycle' => $cycle->id, 'error' => $e->getMessage(), 'class' => get_class($e)]);
            return back()->withErrors(['payment' => 'Une erreur est survenue. Veuillez réessayer.']);
        }
    }

    public function reverse(Transaction $transaction)
    {
        abort_if($transaction->user_id !== Auth::id(), 403);

        try {
            if (!$transaction->isReversible()) {
                return back()->withErrors(['reverse' => 'Ce paiement ne peut plus être annulé (délai de 24h dépassé ou déjà traité).']);
            }

            $transaction->update([
                'status'         => 'reversed',
                'failure_reason' => 'Annulé par l\'utilisateur',
            ]);

            $transaction->load('cycle.tontine');
            if ($transaction->cycle?->tontine) {
                $this->notifier->send(
                    $transaction->user,
                    'general',
                    "Votre paiement de {$transaction->amount} FCFA pour {$transaction->cycle->tontine->name} a été annulé."
                );
            }

            return back()->with('success', 'Paiement annulé avec succès.');
        } catch (\Throwable $e) {
            Log::error('Erreur annulation paiement', ['transaction' => $transaction->id, 'error' => $e->getMessage(), 'class' => get_class($e)]);
            return back()->withErrors(['reverse' => 'Erreur lors de l\'annulation du paiement.']);
        }
    }

    public function successCash(Transaction $transaction)
    {
        abort_if($transaction->user_id !== Auth::id(), 403);
        $transaction->load('cycle.tontine');
        return view('cycles.payment-success-cash', compact('transaction'));
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

        try {
            return response()->json([
                'status'       => $transaction->status,
                'redirect_url' => $transaction->status === 'success'
                    ? route('tontines.show', $transaction->cycle->tontine_id)
                    : null,
                'receipt_url'  => $transaction->status === 'success'
                    ? route('transactions.receipt', $transaction)
                    : null,
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Erreur de vérification'], 500);
        }
    }

    public function receipt(\App\Models\Transaction $transaction)
    {
        abort_if($transaction->user_id !== Auth::id(), 403);
        abort_unless($transaction->status === 'success', 404);
        $transaction->load('cycle.tontine', 'user');

        try {
            $options = new Options();
            $options->set('isRemoteEnabled', true);
            $options->set('isHtml5ParserEnabled', true);

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml(view('cycles.receipt', compact('transaction'))->render());
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            return response($dompdf->output(), 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="recu-' . $transaction->id . '.pdf"',
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur génération reçu PDF', ['transaction' => $transaction->id, 'error' => $e->getMessage(), 'class' => get_class($e)]);
            return back()->withErrors(['error' => 'Impossible de générer le reçu.']);
        }
    }

    public function failed(Request $request)
    {
        try {
            $cycle = $request->cycle_id
                ? \App\Models\Cycle::with('tontine')->find($request->cycle_id)
                : null;
        } catch (\Exception $e) {
            $cycle = null;
        }

        return view('cycles.payment-failed', compact('cycle'));
    }

    public function paytechWebhook(Request $request)
    {
        try {
            $data = $request->all();

            if (!$this->payTechService->verifyWebhook($data)) {
                Log::warning('Webhook PayTech verification echeouee', ['token' => $data['token'] ?? '']);
                return response()->json(['error' => 'Verification failed'], 401);
            }

            $ref           = $data['ref_command'] ?? '';
            $transactionId = str_replace('TontineSN-', '', $ref);
            $transaction   = Transaction::find($transactionId);

            if ($transaction) {
                $webhookAmount = isset($data['amount']) ? (int) $data['amount'] : null;

                if ($webhookAmount !== null && $webhookAmount !== $transaction->amount) {
                    Log::warning('Webhook PayTech : montant incohérent', [
                        'transaction_id' => $transaction->id,
                        'expected'       => $transaction->amount,
                        'received'       => $webhookAmount,
                    ]);
                    return response()->json(['error' => 'Amount mismatch'], 422);
                }

                $this->paymentService->confirmPayment($transaction, $webhookAmount);
                $transaction->load('user', 'cycle.tontine');
                if ($transaction->user && $transaction->cycle?->tontine) {
                    $this->notifier->notifyPaymentConfirmed(
                        $transaction->user,
                        $transaction->amount,
                        $transaction->cycle->tontine->name
                    );
                }
            } else {
                Log::warning('Transaction introuvable pour webhook', ['ref' => $ref, 'transaction_id' => $transactionId]);
            }

            return response()->json(['status' => 'ok']);
        } catch (\Throwable $e) {
            Log::error('Erreur webhook PayTech', ['error' => $e->getMessage(), 'class' => get_class($e)]);
            return response()->json(['status' => 'error', 'message' => 'Internal error'], 500);
        }
    }
}
