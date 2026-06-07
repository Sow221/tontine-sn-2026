<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Jobs\RecalculateCreditScore;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function __construct(private NotificationService $notifier) {}
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required'    => "L'email est obligatoire.",
            'password.required' => 'Le mot de passe est obligatoire.',
        ]);

        try {
            $user = User::where('email', $request->email)->first();

            if ($user && !$user->is_active) {
                return back()->withErrors(['email' => 'Votre compte a été désactivé. Contactez l\'administrateur.'])->onlyInput('email');
            }

            if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
                return back()->withErrors(['email' => 'Email ou mot de passe incorrect.'])->onlyInput('email');
            }

            $request->session()->regenerate();
            $user = Auth::user();
            $user->update(['last_seen_at' => now()]);

            if ($user->isAdmin()) {
                return redirect()->route('admin.dashboard');
            }

            if ($inviteCode = session()->pull('invite_code')) {
                return redirect()->route('tontines.join.form', ['code' => $inviteCode]);
            }

            return redirect()->intended(route('dashboard'));
        } catch (\Throwable $e) {
            Log::error('Erreur connexion', ['error' => $e->getMessage(), 'class' => get_class($e)]);
            return back()->withErrors(['email' => 'Erreur de connexion. Veuillez réessayer.'])->onlyInput('email');
        }
    }

    public function showRegister(Request $request)
    {
        // Stocker le code de parrainage en session pour le retrouver après inscription
        if ($request->filled('ref')) {
            session(['referral_code' => strtoupper($request->ref)]);
        }
        return view('auth.register');
    }

    public function register(RegisterRequest $request)
    {
        try {
            $refCode  = session()->pull('referral_code') ?? strtoupper($request->input('ref', ''));
            $referrer = $refCode ? User::where('referral_code', $refCode)->first() : null;

            $user = User::create([
                'name'         => $request->name,
                'email'        => $request->email,
                'phone_number' => $request->phone_number,
                'password'     => Hash::make($request->password),
                'role'         => 'member',
                'referred_by'  => $referrer?->id,
            ]);

            Auth::login($user);
            $user->update(['last_seen_at' => now()]);

            // Notifier le parrain + recalculer son score
            if ($referrer) {
                $this->notifier->notifyReferralJoined($referrer, $user);
                RecalculateCreditScore::dispatch($referrer->id)->afterResponse();
            }

            return redirect()->route('dashboard')->with('success', 'Bienvenue sur TontineSN ! Créez votre première tontine ou rejoignez un groupe avec un code.');
        } catch (\Throwable $e) {
            Log::error('Erreur inscription', ['error' => $e->getMessage(), 'class' => get_class($e)]);
            return back()->withErrors(['email' => 'Erreur lors de l\'inscription. Veuillez réessayer.'])->withInput();
        }
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        try {
            Password::sendResetLink($request->only('email'));
        } catch (\Throwable $e) {
            Log::error('Erreur envoi lien reset', ['error' => $e->getMessage(), 'class' => get_class($e)]);
        }

        return back()->with('status', 'Si cet email existe, un lien de réinitialisation a été envoyé.');
    }

    public function showResetPassword(Request $request, string $token)
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->email]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'min:8', 'confirmed'],
        ], [
            'password.min'       => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
        ]);

        try {
            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function (User $user, string $password) {
                    $user->update(['password' => Hash::make($password)]);
                }
            );

            return $status === Password::PASSWORD_RESET
                ? redirect()->route('auth.login')->with('status', 'Mot de passe réinitialisé avec succès.')
                : back()->withErrors(['email' => __($status)]);
        } catch (\Throwable $e) {
            Log::error('Erreur réinitialisation mot de passe', ['error' => $e->getMessage(), 'class' => get_class($e)]);
            return back()->withErrors(['email' => 'Erreur lors de la réinitialisation. Veuillez réessayer.']);
        }
    }

    public function redirectToGoogle(Request $request)
    {
        // Préserver le code de parrainage avant redirect OAuth
        if ($request->filled('ref')) {
            session(['referral_code' => strtoupper($request->ref)]);
        }
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect()->route('auth.login')
                             ->withErrors(['email' => 'Connexion Google annulée ou échouée.']);
        }

        try {
            $isNew    = !User::where('email', $googleUser->getEmail())->exists();
            $refCode  = $isNew ? (session()->pull('referral_code') ?? '') : '';
            $referrer = $refCode ? User::where('referral_code', strtoupper($refCode))->first() : null;

            $user = User::firstOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'name'        => $googleUser->getName(),
                    'avatar'      => $googleUser->getAvatar(),
                    'google_id'   => $googleUser->getId(),
                    'password'    => Str::random(32),
                    'role'        => 'member',
                    'referred_by' => $referrer?->id,
                ]
            );

            if (!$user->is_active) {
                return redirect()->route('auth.login')
                    ->withErrors(['email' => 'Votre compte a été désactivé. Contactez l\'administrateur.']);
            }

            $patch = array_filter([
                'google_id' => !$user->google_id ? $googleUser->getId() : null,
                'name'      => !$user->name      ? $googleUser->getName() : null,
                'avatar'    => !$user->avatar    ? $googleUser->getAvatar() : null,
            ]);
            if (!empty($patch)) $user->update($patch);

            Auth::login($user);
            $user->update(['last_seen_at' => now()]);

            // Notifier le parrain + recalculer son score (nouveaux inscrits via Google)
            if ($isNew && $referrer) {
                $this->notifier->notifyReferralJoined($referrer, $user);
                RecalculateCreditScore::dispatch($referrer->id)->afterResponse();
            }

            if ($user->isAdmin()) {
                return redirect()->route('admin.dashboard');
            }

            if ($inviteCode = session()->pull('invite_code')) {
                return redirect()->route('tontines.join.form', ['code' => $inviteCode]);
            }

            return redirect()->intended(route('dashboard'));
        } catch (\Throwable $e) {
            Log::error('Erreur Google OAuth', ['error' => $e->getMessage(), 'class' => get_class($e)]);
            return redirect()->route('auth.login')
                ->withErrors(['email' => 'Erreur lors de la connexion Google.']);
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('auth.login');
    }
}
