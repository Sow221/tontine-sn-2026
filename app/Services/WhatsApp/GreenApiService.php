<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GreenApiService
{
    private ?string $idInstance;
    private ?string $apiToken;
    private string $apiUrl;
    private string $mediaUrl;

    public function __construct()
    {
        $this->idInstance = config('services.greenapi.id_instance');
        $this->apiToken   = config('services.greenapi.api_token');
        $this->apiUrl     = config('services.greenapi.api_url', 'https://7107.api.greenapi.com');
        $this->mediaUrl   = config('services.greenapi.media_url', 'https://7107.api.greenapi.com');
    }

    public function isConfigured(): bool
    {
        return !empty($this->idInstance) && !empty($this->apiToken);
    }

    /**
     * Send a template message with optional URL button
     */
    public function sendTemplate(string $phone, string $templateName, array $params = [], ?string $buttonUrl = null): bool
    {
        if (!$this->isConfigured()) {
            Log::warning('Green API not configured', ['phone' => $phone]);
            return false;
        }

        $phone = $this->normalizePhone($phone);

        $components = [];

        if (!empty($params)) {
            $components[] = [
                'type'       => 'body',
                'parameters' => array_map(fn($p) => ['type' => 'text', 'text' => (string) $p], $params),
            ];
        }

        if ($buttonUrl) {
            $components[] = [
                'type'       => 'button',
                'sub_type'   => 'url',
                'index'      => '0',
                'parameters' => [['type' => 'text', 'text' => $buttonUrl]],
            ];
        }

        $payload = [
            'chatId'      => $phone . '@c.us',
            'template'    => [
                'name'       => $templateName,
                'language'   => ['code' => 'fr'],
                'components' => $components,
            ],
        ];

        return $this->post('sendTemplate', $payload);
    }

    /**
     * Send a simple text message
     */
    public function sendText(string $phone, string $message): bool
    {
        if (!$this->isConfigured()) {
            Log::warning('Green API not configured', ['phone' => $phone]);
            return false;
        }

        $phone = $this->normalizePhone($phone);

        $payload = [
            'chatId'   => $phone . '@c.us',
            'message'  => $message,
        ];

        return $this->post('sendMessage', $payload);
    }

    /**
     * Send an image with caption
     */
    public function sendImage(string $phone, string $imageUrl, string $caption = ''): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        $phone = $this->normalizePhone($phone);

        $payload = [
            'chatId'   => $phone . '@c.us',
            'file'     => $imageUrl,
            'caption'  => $caption,
        ];

        return $this->post('sendFileByUrl', $payload);
    }

    /**
     * Get instance state (authorized, not_authorized, etc.)
     */
    public function getState(): ?string
    {
        if (!$this->isConfigured()) {
            return null;
        }

        try {
            $response = Http::timeout(10)->get("{$this->apiUrl}/waInstance{$this->idInstance}/getStateInstance/{$this->apiToken}");

            if ($response->successful()) {
                return $response->json()['stateInstance'] ?? null;
            }
        } catch (\Throwable $e) {
            Log::error('Green API getState failed', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Set webhook URL for incoming messages
     */
    public function setWebhook(string $webhookUrl): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        return $this->post('webhook', ['webhookUrl' => $webhookUrl]);
    }

    /**
     * Process incoming webhook
     */
    public function processWebhook(array $payload): array
    {
        $type = $payload['typeWebhook'] ?? 'unknown';

        return [
            'type'      => $type,
            'message'   => $payload['messageData'] ?? null,
            'sender'    => $payload['senderData'] ?? null,
            'timestamp' => $payload['timestamp'] ?? null,
            'raw'       => $payload,
        ];
    }

    private function post(string $method, array $payload): bool
    {
        try {
            $url = "{$this->apiUrl}/waInstance{$this->idInstance}/{$method}/{$this->apiToken}";

            $response = Http::timeout(15)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, $payload);

            if ($response->successful()) {
                $data = $response->json();
                return ($data['idMessage'] ?? true) !== false;
            }

            Log::error('Green API request failed', [
                'method'   => $method,
                'status'   => $response->status(),
                'response' => $response->body(),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('Green API exception', [
                'method' => $method,
                'error'  => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($phone, '00')) {
            $phone = substr($phone, 2);
        }

        if (str_starts_with($phone, '0')) {
            $phone = '221' . substr($phone, 1);
        }

        if (!str_starts_with($phone, '221')) {
            $phone = '221' . $phone;
        }

        return $phone;
    }
}
