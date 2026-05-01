<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    public function __construct(private AuthService $authService) {}

    public function showLogin()
    {
        return view('auth.login');
    }

    public function sendOtp(RegisterRequest $request)
    {
        $phone = $request->phone_number;
        $sent  = $this->authService->sendOtp($phone);

        if (!$sent) {
            return back()->withErrors(['phone_number' => 'Impossible d\'envoyer le SMS. Réessayez.']);
        }

        Session::put('otp_phone', $phone);
        return redirect()->route('auth.otp.form');
    }

    public function showOtpForm()
    {
        if (!Session::has('otp_phone')) {
            return redirect()->route('auth.login');
        }
        return view('auth.otp', ['phone' => Session::get('otp_phone')]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate(['code' => 'required|digits:6']);

        $phone = Session::get('otp_phone');

        if (!$phone || !$this->authService->verifyOtp($phone, $request->code)) {
            return back()->withErrors(['code' => 'Code invalide ou expiré.']);
        }

        $user = $this->authService->findOrCreateUser($phone);
        auth()->login($user);
        Session::forget('otp_phone');

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
