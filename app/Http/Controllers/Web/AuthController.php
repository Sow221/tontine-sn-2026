<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private AuthService $authService) {}

    public function showLogin()
    {
        return view('auth.login');
    }

    public function sendMagicLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $this->authService->sendMagicLink($request->email);

        // On retourne toujours succès pour ne pas révéler si l'email existe
        return redirect()->route('auth.magic.sent')
                         ->with('email', $request->email);
    }

    public function magicLinkSent()
    {
        return view('auth.magic-sent', [
            'email' => session('email'),
        ]);
    }

    public function verifyMagicLink(Request $request)
    {
        $token = $request->query('token');

        if (!$token) {
            return redirect()->route('auth.login')
                             ->withErrors(['email' => 'Lien invalide.']);
        }

        $user = $this->authService->verifyToken($token);

        if (!$user) {
            return redirect()->route('auth.login')
                             ->withErrors(['email' => 'Ce lien est expiré ou déjà utilisé.']);
        }

        auth()->login($user, remember: true);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('auth.login');
    }
}
