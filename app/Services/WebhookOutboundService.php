<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Webhooks sortants — permet à des systèmes tiers d'écouter les événements TontineSN.
 * Configuration dans .env : WEBHOOK_OUTBOUND_URL et WEBHOOK_OUTBOUND_SECRET
 */
class WebhookOutboundService
{
    private ?string $url;
    private ?string $secret;

    public function __construct()
    {
        $this->url    = config('services.webhook_outbound.url');
        $this->secret = config('services.webhook_outbound.secret');
    }

    public function dispatch(string $event, array $payload): void
    {
        if (!$this->url) return;

        $body = json_encode([
            'event'     => $event,
            'timestamp' => now()->toIso8601String(),
            'payload'   => $payload,
        ]);

        $signature = hash_hmac('sha256', $body, $this->secret ?? '');

        try {
            Http::withHeaders([
                'Content-Type'        => 'application/json',
                'X-TontineSN-Event'   => $event,
                'X-TontineSN-Sig'     => $signature,
            ])->timeout(5)->post($this->url, json_decode($body, true));
        } catch (\Throwable $e) {
            // Non-bloquant : les webhooks sortants ne doivent jamais casser le flux principal
            Log::warning('Webhook sortant échoué', ['event' => $event, 'error' => $e->getMessage()]);
        }
    }
}
