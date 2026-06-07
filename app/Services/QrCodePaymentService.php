<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodePaymentService
{
    /**
     * Generate a payment request QR code
     * QR code contains: /pay/{paymentToken}
     */
    public function generatePaymentQrCode(User $from, User $to, int $amount, string $description = ''): array
    {
        // Créer un token unique pour cette requête de paiement
        $paymentToken = Str::random(32);
        
        // Stocker la requête en cache (expire après 1h)
        cache()->put("payment_qr:{$paymentToken}", [
            'from_id'    => $from->id,
            'to_id'      => $to->id,
            'amount'     => $amount,
            'description' => $description,
            'expires_at' => now()->addHour(),
        ], 3600);

        // Générer l'URL de paiement
        $paymentUrl = url("/pay/{$paymentToken}");

        // Générer le QR code
        $qrCode = QrCode::size(300)
            ->encoding('UTF-8')
            ->format('svg')
            ->generate($paymentUrl);

        return [
            'token'    => $paymentToken,
            'url'      => $paymentUrl,
            'qr_code'  => $qrCode,
            'amount'   => $amount,
            'to'       => [
                'id'    => $to->id,
                'name'  => $to->name,
                'phone' => $to->phone_number,
            ],
            'from'     => [
                'id'    => $from->id,
                'name'  => $from->name,
                'phone' => $from->phone_number,
            ],
        ];
    }

    /**
     * Validate and process a QR code payment
     */
    public function processQrPayment(string $paymentToken, User $payer): ?Transaction
    {
        $paymentData = cache()->get("payment_qr:{$paymentToken}");

        if (!$paymentData || now()->isAfter($paymentData['expires_at'])) {
            return null; // Token expired or invalid
        }

        $recipient = User::find($paymentData['to_id']);
        if (!$recipient || !$recipient->is_active) {
            return null;
        }

        // Create a simple peer-to-peer transaction (not tied to a cycle)
        // Send notification so the recipient knows they received money
        $transaction = Transaction::create([
            'user_id'     => $payer->id,
            'amount'      => $paymentData['amount'],
            'method'      => 'direct_transfer',
            'type'        => 'p2p_transfer',
            'status'      => 'success',
            'paid_at'     => now(),
            'description' => $paymentData['description'] ?? "Paiement à {$recipient->name}",
            'metadata'    => [
                'recipient_id'   => $recipient->id,
                'recipient_name' => $recipient->name,
                'qr_token'       => $paymentToken,
            ],
        ]);

        // Invalidate the QR token
        cache()->forget("payment_qr:{$paymentToken}");

        return $transaction;
    }
}
