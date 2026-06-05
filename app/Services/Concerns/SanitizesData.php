<?php

declare(strict_types=1);

namespace App\Services\Concerns;

trait SanitizesData
{
    private function sanitizeData(array $data, array $extraSensitiveKeys = []): array
    {
        $sensitiveKeys = array_merge([
            'api_key', 'api_secret', 'secret', 'password', 'token', 'pin',
            'cvv', 'card_number', 'authorization', 'credential', 'otp',
        ], $extraSensitiveKeys);

        $sanitized = [];

        foreach ($data as $key => $value) {
            $isSensitive = false;
            foreach ($sensitiveKeys as $sk) {
                if (str_contains(strtolower((string) $key), $sk)) {
                    $isSensitive = true;
                    break;
                }
            }
            if ($isSensitive) {
                $sanitized[$key] = '***REDACTED***';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeData($value, $extraSensitiveKeys);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
