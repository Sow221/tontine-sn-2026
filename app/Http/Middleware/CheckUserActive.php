<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && !Auth::user()->is_active) {
            Auth::logout();

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Compte désactivé.'], 403);
            }

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('auth.login')
                ->withErrors(['email' => 'Votre compte a été désactivé. Contactez l\'administrateur.']);
        }

        return $next($request);
    }
}
