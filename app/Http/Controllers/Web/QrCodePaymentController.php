<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\QrCodePaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QrCodePaymentController extends Controller
{
    public function __construct(private QrCodePaymentService $qrService) {}

    /**
     * Show QR code payment form
     */
    public function show()
    {
        $user = Auth::user();
        $tontineMembers = $user->memberships()
            ->wherePivot('status', 'active')
            ->with('members')
            ->get()
            ->pluck('members')
            ->flatten()
            ->unique('id')
            ->where('id', '!=', $user->id)
            ->values();

        return view('qr-payment.generate', compact('user', 'tontineMembers'));
    }

    /**
     * Generate a QR code for payment request
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'to_user_id'  => 'required|exists:users,id',
            'amount'      => 'required|integer|min:100|max:1000000',
            'description' => 'nullable|string|max:255',
        ]);

        $from = Auth::user();
        $to = User::findOrFail($validated['to_user_id']);

        // Verify they're in the same tontine
        $inSameTontine = $from->memberships()
            ->wherePivot('status', 'active')
            ->whereHas('members', fn($q) => $q->where('users.id', $to->id))
            ->exists();

        abort_unless($inSameTontine, 403, 'Les utilisateurs ne sont pas dans la même tontine');

        $paymentData = $this->qrService->generatePaymentQrCode(
            $from,
            $to,
            $validated['amount'],
            $validated['description'] ?? ''
        );

        return view('qr-payment.generated', compact('paymentData'));
    }

    /**
     * Scan and process QR code payment
     */
    public function scan($token)
    {
        $payer = Auth::user();
        $paymentData = cache()->get("payment_qr:{$token}");

        abort_unless($paymentData, 404, 'Requête de paiement invalide ou expirée');

        $recipient = User::find($paymentData['to_id']);

        return view('qr-payment.confirm', compact('token', 'paymentData', 'recipient'));
    }

    /**
     * Confirm and process QR payment
     */
    public function confirm(Request $request, string $token)
    {
        $payer = Auth::user();

        $transaction = $this->qrService->processQrPayment($token, $payer);

        abort_unless($transaction, 422, 'Impossible de traiter le paiement');

        return redirect()->route('historique.index')
            ->with('success', 'Paiement P2P enregistré avec succès.');
    }
}
