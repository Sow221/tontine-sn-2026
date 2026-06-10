<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ActivityLogger
{
    private static array $sensitiveRoutes = [
        'auth', 'payment', 'admin', 'cycle', 'tontines', 'profil',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            if ($request->user() && $this->shouldLog($request)) {
                ActivityLog::create([
                    'user_id' => $request->user()->id,
                    'action' => $request->method().' '.$request->path(),
                    'ip_address' => $request->ip(),
                    'payload' => $this->sanitize($request->all()),
                ]);
            }
        } catch (\Throwable) {
            // Ne jamais bloquer la réponse à cause du logging
        }

        return $response;
    }

    private function shouldLog(Request $request): bool
    {
        foreach (self::$sensitiveRoutes as $route) {
            if (str_contains($request->path(), $route)) {
                return true;
            }
        }

        return false;
    }

    private function sanitize(array $data): array
    {
        $sensitive = [
            'password', 'password_confirmation', 'current_password',
            'code', 'otp', '_token', 'confirm_delete',
            'kyc_consent', 'bid_rate', 'amount',
            'new_owner_id', 'beneficiary_id',
        ];

        return array_diff_key($data, array_flip($sensitive));
    }
}
