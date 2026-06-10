<?php

namespace App\Services;

use App\Services\Concerns\SanitizesData;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LoggingService
{
    use SanitizesData;

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
     */
    private function sanitizePayload(array $payload): array
    {
        return $this->sanitizeData($payload, ['authorization', 'credential', 'otp']);
    }
}
