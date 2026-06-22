<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Str;

class QrCodePaymentService
{
    public function __construct(private NotificationService $notifier) {}

    public function generatePaymentQrCode(User $from, User $to, int $amount, string $description = ''): array
    {
        $paymentToken = Str::random(32);

        cache()->put("payment_qr:{$paymentToken}", [
            'from_id' => $from->id,
            'to_id' => $to->id,
            'amount' => $amount,
            'description' => $description,
            'expires_at' => now()->addHour(),
        ], 3600);

        $paymentUrl = url("/pay/{$paymentToken}");

        // QR code via API externe — pas de dépendance PHP locale
        $qrCode = '<img src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data='
            .urlencode($paymentUrl)
            .'" alt="QR Code paiement" width="300" height="300" style="border-radius:8px;">';

        return [
            'token' => $paymentToken,
            'url' => $paymentUrl,
            'qr_code' => $qrCode,
            'amount' => $amount,
            'to' => [
                'id' => $to->id,
                'name' => $to->name,
                'phone' => $to->phone_number,
            ],
            'from' => [
                'id' => $from->id,
                'name' => $from->name,
                'phone' => $from->phone_number,
            ],
        ];
    }

    public function processQrPayment(string $paymentToken, User $payer): ?Transaction
    {
        $paymentData = cache()->get("payment_qr:{$paymentToken}");

        if (! $paymentData || now()->isAfter($paymentData['expires_at'])) {
            return null;
        }

        // Sécurité : seul le destinataire du QR (to_id) peut payer via ce token
        if ((int) $paymentData['to_id'] !== $payer->id) {
            return null;
        }

        $recipient = User::find($paymentData['from_id']);
        if (! $recipient || ! $recipient->is_active) {
            return null;
        }

        $transaction = Transaction::create([
            'user_id' => $payer->id,
            'cycle_id' => null,
            'amount' => $paymentData['amount'],
            'method' => 'direct_transfer',
            'type' => 'p2p_transfer',
            'status' => 'success',
            'paid_at' => now(),
            'description' => $paymentData['description'] ?? "Paiement à {$recipient->name}",
            'metadata' => [
                'recipient_id' => $recipient->id,
                'recipient_name' => $recipient->name,
                'qr_token' => $paymentToken,
            ],
        ]);

        cache()->forget("payment_qr:{$paymentToken}");

        // Notifier le destinataire du paiement reçu
        $montant = number_format($paymentData['amount'], 0, ',', ' ');
        $this->notifier->sendEmail(
            $recipient,
            '💸 Paiement P2P reçu — TontineSN',
            "Bonjour <strong>{$recipient->name}</strong>,<br><br>"
            ."<strong>{$payer->name}</strong> vous a envoyé <strong>{$montant} FCFA</strong> via QR code.<br><br>"
            .($paymentData['description'] ? "Motif : {$paymentData['description']}<br><br>" : '')
            .'Connectez-vous sur TontineSN pour voir votre historique.',
            'p2p_received'
        );

        return $transaction;
    }
}
