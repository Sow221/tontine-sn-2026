<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LoggingService
{
    use \App\Services\Concerns\SanitizesData;
    private string $correlationId;

    public function __construct()
    {
        $this->correlationId = request()->header('X-Correlation-ID')
            ?? request()->cookie('correlation_id')
            ?? Str::uuid()->toString();
    }

    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }

    public function setCorrelationId(string $correlationId): void
    {
        $this->correlationId = $correlationId;
    }

    /**
     * Log payment initiation
     *
     * @param int $userId
     * @param float $amount
     * @param string $currency
     * @param string $reference
     * @param array $metadata
     * @return void
     */
    public function logPaymentInitiation(
        int $userId,
        float $amount,
        string $currency,
        string $reference,
        array $metadata = []
    ): void {
        Log::channel('transactions')->info('payment_initiated', [
            'correlation_id' => $this->correlationId,
            'user_id' => $userId,
            'amount' => $amount,
            'currency' => $currency,
            'reference' => $reference,
            'status' => 'initiated',
            'timestamp' => now()->toIso8601String(),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Log payment verification
     *
     * @param int $userId
     * @param float $amount
     * @param string $reference
     * @param string $externalToken
     * @param array $metadata
     * @return void
     */
    public function logPaymentVerification(
        int $userId,
        float $amount,
        string $reference,
        string $externalToken,
        array $metadata = []
    ): void {
        Log::channel('transactions')->info('payment_verified', [
            'correlation_id' => $this->correlationId,
            'user_id' => $userId,
            'amount' => $amount,
            'reference' => $reference,
            'external_token' => $externalToken,
            'status' => 'verified',
            'timestamp' => now()->toIso8601String(),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Log payment confirmation
     *
     * @param int $userId
     * @param float $amount
     * @param string $reference
     * @param string $externalToken
     * @param array $metadata
     * @return void
     */
    public function logPaymentConfirmation(
        int $userId,
        float $amount,
        string $reference,
        string $externalToken,
        array $metadata = []
    ): void {
        Log::channel('transactions')->info('payment_confirmed', [
            'correlation_id' => $this->correlationId,
            'user_id' => $userId,
            'amount' => $amount,
            'reference' => $reference,
            'external_token' => $externalToken,
            'status' => 'confirmed',
            'timestamp' => now()->toIso8601String(),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Log payment error
     *
     * @param int|null $userId
     * @param float|null $amount
     * @param string|null $reference
     * @param string $errorCode
     * @param string $errorMessage
     * @param array $context
     * @return void
     */
    public function logPaymentError(
        ?int $userId,
        ?float $amount,
        ?string $reference,
        string $errorCode,
        string $errorMessage,
        array $context = []
    ): void {
        Log::channel('transactions')->error('payment_error', [
            'correlation_id' => $this->correlationId,
            'user_id' => $userId,
            'amount' => $amount,
            'reference' => $reference,
            'status' => 'failed',
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
            'timestamp' => now()->toIso8601String(),
            'context' => $context,
        ]);
    }

    /**
     * Log webhook event
     *
     * @param string $event
     * @param string $externalToken
     * @param array $payload
     * @param bool $success
     * @return void
     */
    public function logWebhookEvent(
        string $event,
        string $externalToken,
        array $payload,
        bool $success = true
    ): void {
        Log::channel('transactions')->info('webhook_event', [
            'correlation_id' => $this->correlationId,
            'event' => $event,
            'external_token' => $externalToken,
            'success' => $success,
            'timestamp' => now()->toIso8601String(),
            'payload' => $this->sanitizePayload($payload),
        ]);
    }

    /**
     * Log payment status change
     *
     * @param int $userId
     * @param string $reference
     * @param string $fromStatus
     * @param string $toStatus
     * @param array $metadata
     * @return void
     */
    public function logPaymentStatusChange(
        int $userId,
        string $reference,
        string $fromStatus,
        string $toStatus,
        array $metadata = []
    ): void {
        Log::channel('transactions')->info('payment_status_changed', [
            'correlation_id' => $this->correlationId,
            'user_id' => $userId,
            'reference' => $reference,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'timestamp' => now()->toIso8601String(),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Sanitize payload by removing sensitive data
     *
     * @param array $payload
     * @return array
     */
    private function sanitizePayload(array $payload): array
    {
        return $this->sanitizeData($payload, ['authorization', 'credential', 'otp']);
    }
}
