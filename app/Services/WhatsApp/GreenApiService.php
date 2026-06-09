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

    public function __construct()
    {
        $this->idInstance = config('services.greenapi.id_instance');
        $this->apiToken   = config('services.greenapi.api_token');
        $this->apiUrl     = config('services.greenapi.api_url', 'https://api.greenapi.com');
    }

    public function isConfigured(): bool
    {
        return !empty($this->idInstance) && !empty($this->apiToken);
    }

    /**
     * Envoie un message texte simple (Markdown supporté : *gras*, _italique_, `code`)
     * GRATUIT, illimité sur compte Developer (QR code)
     */
    public function sendText(string $phone, string $message): bool
    {
        if (!$this->isConfigured()) {
            Log::warning('Green API not configured', ['phone' => $phone]);
            return false;
        }

        $phone = $this->normalizePhone($phone);

        $payload = [
            'chatId'  => $phone . '@c.us',
            'message' => $message,
        ];

        try {
            $response = Http::timeout(15)
                ->post("{$this->apiUrl}/waInstance{$this->idInstance}/sendMessage/{$this->apiToken}", $payload);

            if ($response->successful()) {
                $data = $response->json();
                return ($data['idMessage'] ?? true) !== false;
            }

            Log::error('Green API sendMessage failed', [
                'status'   => $response->status(),
                'response' => $response->body(),
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::error('Green API exception', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Envoie une notification structurée (remplace les templates)
     * Utilise Markdown WhatsApp : *gras*, _italique_, ~barré~, `code`
     */
    public function sendNotification(string $phone, string $title, string $body, ?string $buttonUrl = null, ?string $buttonText = null): bool
    {
        $message = "*{$title}*\n\n{$body}";

        if ($buttonUrl && $buttonText) {
            $message .= "\n\n👉 *{$buttonText} :* {$buttonUrl}";
        }

        $message .= "\n\n_TontineSN_";

        return $this->sendText($phone, $message);
    }

    /**
     * Récupère l'état de l'instance (authorized, notAuthorized, etc.)
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
     * Traite un webhook entrant
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
