<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyGreenApiWebhook
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.greenapi.webhook_secret');

        // Secret non configuré → log + blocage (ne jamais ouvrir sans protection)
        if (empty($secret)) {
            Log::critical('GREENAPI_WEBHOOK_SECRET manquant — webhook bloqué', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Service unavailable'], 503);
        }

        // Comparaison en temps constant pour éviter les timing attacks
        $provided = (string) $request->route('token', '');

        if (! hash_equals($secret, $provided)) {
            Log::warning('Webhook GreenAPI : token invalide', [
                'ip' => $request->ip(),
                'token' => substr($provided, 0, 6).'…',
            ]);

            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Validation minimale du format GreenAPI (tous les webhooks ont typeWebhook)
        if (! $request->has('typeWebhook')) {
            Log::warning('Webhook GreenAPI : payload non conforme', [
                'ip' => $request->ip(),
                'body' => array_keys($request->all()),
            ]);

            return response()->json(['error' => 'Invalid payload'], 400);
        }

        return $next($request);
    }
}
