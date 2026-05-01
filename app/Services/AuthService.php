<?php

namespace App\Services;

use App\Models\OtpCode;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AuthService
{
    public function sendOtp(string $phone): bool
    {
        // Invalider les anciens OTP
        OtpCode::where('phone_number', $phone)
                ->where('used', false)
                ->update(['used' => true]);

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        OtpCode::create([
            'phone_number' => $phone,
            'code'         => $code,
            'expires_at'   => Carbon::now()->addMinutes(config('tontine.otp.expiry_minutes')),
        ]);

        return app(NotificationService::class)->sendSms($phone, "TontineSN - Votre code : {$code}");
    }

    public function verifyOtp(string $phone, string $code): bool
    {
        $otp = OtpCode::where('phone_number', $phone)
                      ->where('used', false)
                      ->latest()
                      ->first();

        if (!$otp) return false;

        $otp->increment('attempts');

        if (!$otp->isValid($code)) return false;

        $otp->update(['used' => true]);
        return true;
    }

    public function findOrCreateUser(string $phone): User
    {
        return User::firstOrCreate(
            ['phone_number' => $phone],
            ['role' => 'member']
        );
    }
}
