<?php

namespace App\Services;

use App\Mail\MagicLinkMail;
use App\Models\MagicLink;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthService
{
    public function sendMagicLink(string $email): bool
    {
        // Invalider les anciens liens non utilisés
        MagicLink::where('email', $email)
                 ->where('used', false)
                 ->update(['used' => true]);

        $token = Str::random(64);

        MagicLink::create([
            'email'      => $email,
            'token'      => hash('sha256', $token),
            'expires_at' => now()->addMinutes(15),
        ]);

        $url = route('auth.magic.verify', ['token' => $token]);

        Mail::to($email)->send(new MagicLinkMail($url));

        return true;
    }

    public function verifyToken(string $token): ?User
    {
        $link = MagicLink::where('token', hash('sha256', $token))
                         ->where('used', false)
                         ->first();

        if (!$link || !$link->isValid()) {
            return null;
        }

        $link->update(['used' => true]);

        return User::firstOrCreate(
            ['email' => $link->email],
            ['role'  => 'member']
        );
    }
}
