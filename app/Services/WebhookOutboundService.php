<?php

namespace App\Services;

use App\Jobs\SendWebhook;

/**
 * Webhooks sortants — permet à des systèmes tiers d'écouter les événements TontineSN.
 * L'envoi est délégué à un job asynchrone (file d'attente) pour ne jamais bloquer le flux principal.
 * Configuration dans .env : WEBHOOK_OUTBOUND_URL et WEBHOOK_OUTBOUND_SECRET
 */
class WebhookOutboundService
{
    private ?string $url;

    private ?string $secret;

    public function __construct()
    {
        $this->url = config('services.webhook_outbound.url');
        $this->secret = config('services.webhook_outbound.secret');
    }

    public function dispatch(string $event, array $payload): void
    {
        if (! $this->url) {
            return;
        }

        $body = json_encode([
            'event' => $event,
            'timestamp' => now()->toIso8601String(),
            'payload' => $payload,
        ]);

        $signature = hash_hmac('sha256', $body, $this->secret ?? '');

        SendWebhook::dispatch($this->url, $event, $payload, $signature);
    }
}
