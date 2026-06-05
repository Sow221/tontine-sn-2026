<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayTechService
{
    use \App\Services\Concerns\SanitizesData;
    private string $apiKey;
    private string $apiSecret;
    private string $baseUrl;
    private LoggingService $loggingService;

    public function __construct(LoggingService $loggingService)
    {
        $cfg                   = config('mobilemoney.paytech');
        $this->apiKey          = $cfg['api_key'];
        $this->apiSecret       = $cfg['api_secret'];
        $this->baseUrl         = $cfg['base_url'];
        $this->loggingService  = $loggingService;
    }

    public function initiatePayment(Transaction $transaction): array
    {
        try {
            $userId = $transaction->user_id ?? null;
            $amount = $transaction->amount;
            $reference = "TontineSN-{$transaction->id}";

            $this->loggingService->logPaymentInitiation(
                $userId,
                $amount,
                config('mobilemoney.paytech.currency'),
                $reference,
                [
                    'transaction_id' => $transaction->id,
                    'cycle_id' => $transaction->cycle_id,
                ]
            );

            $response = Http::withHeaders([
                'API_KEY'    => $this->apiKey,
                'API_SECRET' => $this->apiSecret,
            ])
            ->timeout(config('mobilemoney.paytech.timeout'))
            ->post("{$this->baseUrl}/api/payment/request-payment", [
                'item_name'        => "Cotisation TontineSN #{$transaction->cycle_id}",
                'item_price'       => $transaction->amount,
                'currency'         => config('mobilemoney.paytech.currency'),
                'ref_command'      => $reference,
                'command_name'     => "Cotisation tontine",
                'env'              => 'prod',
                'ipn_url'          => route('webhooks.paytech'),
                'success_url'      => route('payment.pending', $transaction),
                'cancel_url'       => route('payment.failed', ['cycle_id' => $transaction->cycle_id]),
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (($data['success'] ?? 0) === 1) {
                    $token = $data['token'] ?? null;

                    if (!$token) {
                        Log::error('PayTech response missing token', ['response' => $data]);

                        $this->loggingService->logPaymentError(
                            $userId, $amount, $reference,
                            'PAYTECH_MISSING_TOKEN',
                            'Token manquant dans la réponse PayTech',
                            ['response' => $data]
                        );

                        return ['success' => false, 'error' => 'Token manquant dans la réponse PayTech'];
                    }

                    $transaction->update([
                        'external_reference' => $token,
                    ]);

                    $this->loggingService->logPaymentVerification(
                        $userId,
                        $amount,
                        $reference,
                        $token,
                        ['source' => 'paytech_api_response']
                    );

                    return [
                        'success'      => true,
                        'redirect_url' => "https://paytech.sn/payment/checkout/{$token}",
                    ];
                }

                $errorMsg = $data['errors'][0] ?? 'Échec initialisation PayTech';
                Log::error('PayTech payment failed', ['response' => $data]);

                $this->loggingService->logPaymentError(
                    $userId,
                    $amount,
                    $reference,
                    'PAYTECH_INIT_FAILED',
                    $errorMsg,
                    ['response' => $data]
                );

                return ['success' => false, 'error' => $errorMsg];
            }

            $errorMsg = 'Erreur de connexion PayTech';
            Log::error('PayTech HTTP error', ['status' => $response->status(), 'body' => $response->body()]);

            $this->loggingService->logPaymentError(
                $userId,
                $amount,
                $reference,
                'PAYTECH_HTTP_ERROR',
                $errorMsg,
                ['http_status' => $response->status()]
            );

            return ['success' => false, 'error' => $errorMsg];

        } catch (\Throwable $e) {
            $userId = $transaction->user_id ?? null;
            $amount = $transaction->amount;
            $reference = "TontineSN-{$transaction->id}";

            Log::error('PayTech exception', [
                'message' => $e->getMessage(),
                'class'   => get_class($e),
                'trace'   => $e->getTraceAsString(),
            ]);

            $this->loggingService->logPaymentError(
                $userId,
                $amount,
                $reference,
                'PAYTECH_EXCEPTION',
                $e->getMessage(),
                ['exception_class' => get_class($e)]
            );

            return ['success' => false, 'error' => 'Erreur de paiement. Veuillez réessayer.'];
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
                $isVerified = ($details['success'] ?? 0) === 1
                    && ($details['payment_status'] ?? '') === 'completed';

                $this->loggingService->logWebhookEvent(
                    'payment_webhook_received',
                    $token,
                    $this->sanitizeWebhookData($details),
                    $isVerified
                );

                if ($isVerified) {
                    $refCommand = $data['ref_command'] ?? $details['ref_command'] ?? '';
                    $this->loggingService->logPaymentConfirmation(
                        $data['user_id'] ?? $details['user_id'] ?? 0,
                        (float) ($data['amount'] ?? $details['amount'] ?? 0),
                        $refCommand,
                        $token,
                        ['source' => 'webhook_verification']
                    );
                }

                return $isVerified;
            }

            Log::error('PayTech webhook verification failed', ['http_status' => $response->status()]);
            $this->loggingService->logPaymentError(
                $data['user_id'] ?? null,
                $data['amount'] ?? null,
                $data['ref_command'] ?? null,
                'PAYTECH_WEBHOOK_HTTP_ERROR',
                'HTTP error during webhook verification',
                ['http_status' => $response->status()]
            );

        } catch (\Throwable $e) {
            Log::error('PayTech webhook verification failed', [
                'message' => $e->getMessage(),
                'class'   => get_class($e),
                'trace'   => $e->getTraceAsString(),
            ]);
            $this->loggingService->logPaymentError(
                $data['user_id'] ?? null,
                $data['amount'] ?? null,
                $data['ref_command'] ?? null,
                'PAYTECH_WEBHOOK_EXCEPTION',
                $e->getMessage(),
                ['exception_class' => get_class($e)]
            );
        }

        return false;
    }

    private function sanitizeWebhookData(array $data): array
    {
        return $this->sanitizeData($data);
    }
}
