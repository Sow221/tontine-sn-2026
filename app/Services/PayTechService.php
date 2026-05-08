<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayTechService
{
    private string $apiKey;
    private string $apiSecret;
    private string $baseUrl;

    public function __construct()
    {
        $cfg           = config('mobilemoney.paytech');
        $this->apiKey    = $cfg['api_key'];
        $this->apiSecret = $cfg['api_secret'];
        $this->baseUrl   = $cfg['base_url'];
    }

    public function initiatePayment(Transaction $transaction): array
    {
        try {
            $response = Http::withHeaders([
                'API_KEY'    => $this->apiKey,
                'API_SECRET' => $this->apiSecret,
            ])
            ->timeout(config('mobilemoney.paytech.timeout'))
            ->post("{$this->baseUrl}/api/payment/request-payment", [
                'item_name'        => "Cotisation TontineSN #{$transaction->cycle_id}",
                'item_price'       => $transaction->amount,
                'currency'         => config('mobilemoney.paytech.currency'),
                'ref_command'      => "TontineSN-{$transaction->id}",
                'command_name'     => "Cotisation tontine",
                'env'              => app()->isProduction() ? 'prod' : 'test',
                'ipn_url'          => route('webhooks.paytech'),
                'success_url'      => route('payment.pending', $transaction),
                'cancel_url'       => route('payment.failed'),
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (($data['success'] ?? 0) === 1) {
                    $transaction->update([
                        'external_reference' => $data['token'] ?? null,
                    ]);
                    return [
                        'success'      => true,
                        'redirect_url' => "https://paytech.sn/payment/checkout/{$data['token']}",
                    ];
                }

                Log::error('PayTech payment failed', ['response' => $data]);
                return ['success' => false, 'error' => $data['errors'][0] ?? 'Échec initialisation PayTech'];
            }

            Log::error('PayTech HTTP error', ['status' => $response->status(), 'body' => $response->body()]);
            return ['success' => false, 'error' => 'Erreur de connexion PayTech'];

        } catch (\Exception $e) {
            Log::error('PayTech exception', ['message' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function verifyWebhook(array $data): bool
    {
        $token = $data['token'] ?? '';

        try {
            $response = Http::withHeaders([
                'API_KEY'    => $this->apiKey,
                'API_SECRET' => $this->apiSecret,
            ])->get("{$this->baseUrl}/api/payment/details/{$token}");

            if ($response->successful()) {
                $details = $response->json();
                return ($details['success'] ?? 0) === 1
                    && ($details['payment_status'] ?? '') === 'completed';
            }
        } catch (\Exception $e) {
            Log::error('PayTech webhook verification failed', ['message' => $e->getMessage()]);
        }

        return false;
    }
}
