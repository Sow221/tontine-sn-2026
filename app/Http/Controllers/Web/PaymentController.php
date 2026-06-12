<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Cycle;
use App\Models\Transaction;
use App\Services\CycleService;
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
        private CycleService $cycleService,
    ) {}

    public function showForm(Cycle $cycle)
    {
        $cycle->load('tontine');

        abort_unless($cycle->tontine->status === 'active', 403, 'Cette tontine est suspendue ou inactive.');

        abort_unless(
            $cycle->tontine->members()
                ->where('users.id', Auth::id())
                ->wherePivot('status', 'active')
                ->exists(),
            403
        );

        try {
            $penalty = $cycle->isOverdue() && $cycle->tontine->penalty_rate > 0
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

        abort_unless($cycle->tontine->status === 'active', 403, 'Cette tontine est suspendue ou inactive.');

        abort_unless(
            $cycle->tontine->members()
                ->where('users.id', $user->id)
                ->wherePivot('status', 'active')
                ->exists(),
            403
        );

        try {
            $amount = $cycle->tontine->amount;
            $transaction = $this->paymentService->recordPayment($cycle, $user->id, $amount, $request->method);

            if ($request->method === 'cash') {
                return redirect()->route('payment.pending', $transaction);
            }

            $result = $this->payTechService->initiatePayment($transaction);

            if (! $result['success']) {
                $errorMsg = $result['error'] ?? 'Erreur de paiement. Veuillez réessayer.';
                $transaction->update(['status' => 'failed', 'failure_reason' => $errorMsg]);
                Log::error('Paiement échoué', ['transaction' => $transaction->id, 'error' => $errorMsg]);

                return back()->withErrors(['payment' => 'Le paiement a échoué. Veuillez réessayer ou choisir un autre mode de paiement.']);
            }

            return redirect()->away($result['redirect_url']);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['payment' => $e->getMessage()]);
        } catch (\Throwable $e) {
            Log::error('Erreur initiation paiement', ['cycle' => $cycle->id, 'error' => $e->getMessage(), 'class' => get_class($e)]);

            return back()->withErrors(['payment' => 'Une erreur est survenue. Veuillez réessayer.']);
        }
    }

    public function reverse(Transaction $transaction)
    {
        abort_if($transaction->user_id !== Auth::id(), 403);

        try {
            if (! $transaction->isReversible()) {
                return back()->withErrors(['reverse' => 'Ce paiement ne peut plus être annulé (délai de 24h dépassé ou déjà traité).']);
            }

            $transaction->update([
                'status' => 'reversed',
                'failure_reason' => 'Annulé par l\'utilisateur',
            ]);

            $transaction->load('cycle.tontine', 'user');

            // Recalculer le total collecté du cycle après annulation
            if ($transaction->cycle) {
                $this->cycleService->updateCycleTotal($transaction->cycle);
            }

            if ($transaction->cycle?->tontine) {
                $method = $transaction->method;
                $isDigital = in_array($method, ['wave', 'orange_money', 'free_money', 'card'], true);

                $message = "Votre paiement de {$transaction->amount} FCFA pour {$transaction->cycle->tontine->name} a été marqué comme annulé.";
                if ($isDigital) {
                    $message .= ' Pour le remboursement réel, contactez directement votre opérateur de paiement.';
                }

                $this->notifier->send($transaction->user, 'general', $message);
            }

            $successMsg = $transaction->method === 'cash'
                ? 'Paiement espèces annulé. Le cycle a été mis à jour.'
                : 'Paiement marqué comme annulé. Pour le remboursement, contactez votre opérateur (Wave, Orange Money…).';

            return back()->with('success', $successMsg);
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
                'status' => $transaction->status,
                'redirect_url' => $transaction->status === 'success'
                    ? route('tontines.show', $transaction->cycle?->tontine_id)
                    : null,
                'receipt_url' => $transaction->status === 'success'
                    ? route('transactions.receipt', $transaction)
                    : null,
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Erreur de vérification'], 500);
        }
    }

    public function receipt(Transaction $transaction)
    {
        abort_if($transaction->user_id !== Auth::id(), 403);
        abort_unless($transaction->status === 'success', 404);
        $transaction->load('cycle.tontine', 'user');

        try {
            $options = new Options;
            $options->set('isRemoteEnabled', true);
            $options->set('isHtml5ParserEnabled', true);

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml(view('cycles.receipt', compact('transaction'))->render());
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            return response($dompdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="recu-'.$transaction->id.'.pdf"',
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur génération reçu PDF', ['transaction' => $transaction->id, 'error' => $e->getMessage(), 'class' => get_class($e)]);

            return back()->withErrors(['error' => 'Impossible de générer le reçu.']);
        }
    }

    public function dispute(Request $request, Transaction $transaction)
    {
        abort_if($transaction->user_id !== Auth::id(), 403);
        abort_unless($transaction->method === 'cash' && $transaction->status === 'success', 403);

        $request->validate(['reason' => 'nullable|string|max:500']);

        $meta = $transaction->metadata ?? [];
        if (! empty($meta['disputed'])) {
            return back()->with('info', 'Ce paiement est déjà marqué comme contesté.');
        }

        $meta['disputed'] = true;
        $meta['dispute_reason'] = $request->reason ?? 'Contesté par le membre';
        $meta['disputed_by'] = Auth::id();
        $meta['disputed_at'] = now()->toIso8601String();
        $transaction->update(['metadata' => $meta]);

        try {
            $transaction->load('cycle.tontine.creator');
            $creator = $transaction->cycle?->tontine?->creator;
            if ($creator && $creator->id !== Auth::id()) {
                $tontineName = $transaction->cycle->tontine->name;
                $cycleNum = $transaction->cycle->cycle_number;
                $reason = $request->reason ? ' Raison : '.$request->reason : '';
                $this->notifier->send(
                    $creator,
                    'general',
                    Auth::user()->name." conteste le paiement espèces du cycle {$cycleNum} de la tontine « {$tontineName} ».{$reason}"
                );
            }
        } catch (\Throwable $e) {
            Log::warning('Notification dispute échouée', ['transaction' => $transaction->id, 'error' => $e->getMessage()]);
        }

        return back()->with('success', 'Contestation enregistrée. Le créateur de la tontine a été notifié.');
    }

    public function failed(Request $request)
    {
        try {
            $cycle = $request->cycle_id
                ? Cycle::with('tontine')->find($request->cycle_id)
                : null;
        } catch (\Exception $e) {
            $cycle = null;
        }

        return view('cycles.payment-failed', compact('cycle'));
    }
}
