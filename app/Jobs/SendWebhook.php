<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 10;

    public function __construct(
        private string $url,
        private string $event,
        private array $payload,
        private string $signature,
    ) {}

    public function handle(): void
    {
        $body = json_encode([
            'event' => $this->event,
            'timestamp' => now()->toIso8601String(),
            'payload' => $this->payload,
        ]);

        Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-TontineSN-Event' => $this->event,
            'X-TontineSN-Sig' => $this->signature,
        ])->timeout(5)->post($this->url, json_decode($body, true));
    }

    public function failed(\Throwable $e): void
    {
        Log::warning('Webhook sortant échoué', ['event' => $this->event, 'error' => $e->getMessage()]);
    }
}
