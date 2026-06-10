<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Non authentifié.'], 401)
                : redirect()->route('auth.login');
        }

        // Route réservée exclusivement aux membres (pas aux admins)
        $memberOnly = $roles === ['member'];

        // Un admin qui tente d'accéder à l'espace membre est redirigé vers son dashboard
        if ($memberOnly && $user->isAdmin()) {
            return redirect()->route('admin.dashboard')
                ->with('status', "Accès réservé aux membres. Utilisez votre espace d'administration.");
        }

        // L'utilisateur doit avoir l'un des rôles autorisés
        // Les admins passent sur toutes les routes non-memberOnly
        $authorized = in_array($user->role, $roles)
            || (! $memberOnly && $user->isAdmin());

        if (! $authorized) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Accès non autorisé.'], 403)
                : abort(403, 'Accès non autorisé.');
        }

        return $next($request);
    }
}
