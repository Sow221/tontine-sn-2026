<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MobileMoneyService
{
    public function initiateWave(Transaction $transaction, string $phone): array
    {
        $cfg = config('mobilemoney.wave');

        try {
            $response = Http::withToken($cfg['api_key'])
                ->timeout($cfg['timeout'])
                ->post("{$cfg['base_url']}/checkout/sessions", [
                    'amount'       => $transaction->amount,
                    'currency'     => $cfg['currency'],
                    'client_reference' => (string) $transaction->id,
                    'success_url'  => route('webhooks.wave'),
                    'error_url'    => route('payment.failed'),
                    'phone_number' => $phone,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $transaction->update(['external_reference' => $data['id'] ?? null]);
                return ['success' => true, 'checkout_url' => $data['wave_launch_url'] ?? null];
            }

            Log::error('Wave initiation failed', ['response' => $response->body()]);
            return ['success' => false, 'error' => 'Échec initialisation Wave'];

        } catch (\Exception $e) {
            Log::error('Wave exception', ['message' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function initiateOrangeMoney(Transaction $transaction, string $phone): array
    {
        $cfg = config('mobilemoney.orange_money');

        try {
            $response = Http::withBasicAuth($cfg['api_key'], $cfg['api_secret'])
                ->timeout($cfg['timeout'])
                ->post("{$cfg['base_url']}/webpayment", [
                    'merchant_key'  => $cfg['api_key'],
                    'currency'      => $cfg['currency'],
                    'order_id'      => (string) $transaction->id,
                    'amount'        => $transaction->amount,
                    'return_url'    => route('webhooks.orange'),
                    'cancel_url'    => route('payment.failed'),
                    'notif_url'     => route('webhooks.orange'),
                    'lang'          => 'fr',
                    'reference'     => "TontineSN-{$transaction->id}",
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $transaction->update(['external_reference' => $data['pay_token'] ?? null]);
                return ['success' => true, 'payment_url' => $data['payment_url'] ?? null];
            }

            Log::error('Orange Money initiation failed', ['response' => $response->body()]);
            return ['success' => false, 'error' => 'Échec initialisation Orange Money'];

        } catch (\Exception $e) {
            Log::error('Orange Money exception', ['message' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function verifyWaveWebhook(string $payload, string $signature): bool
    {
        $expected = hash_hmac('sha256', $payload, config('mobilemoney.wave.webhook_secret'));
        return hash_equals($expected, $signature);
    }
}
