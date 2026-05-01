<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ActivityLogger
{
    private static array $sensitiveRoutes = [
        'auth', 'payment', 'admin', 'cycle.draw',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->user() && $this->shouldLog($request)) {
            \DB::table('activity_logs')->insert([
                'user_id'    => $request->user()->id,
                'action'     => $request->method() . ' ' . $request->path(),
                'ip_address' => $request->ip(),
                'payload'    => json_encode($this->sanitize($request->all())),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $response;
    }

    private function shouldLog(Request $request): bool
    {
        foreach (self::$sensitiveRoutes as $route) {
            if (str_contains($request->path(), $route)) return true;
        }
        return false;
    }

    private function sanitize(array $data): array
    {
        return array_diff_key($data, array_flip(['password', 'code', 'otp', '_token']));
    }
}
