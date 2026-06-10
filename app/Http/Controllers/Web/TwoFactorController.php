<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\TwoFactorSecret;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use OTPHP\TOTP;

class TwoFactorController extends Controller
{
    /**
     * Génère un secret TOTP et affiche le QR code pour le scan.
     * Le secret est stocké mais pas encore activé (enabled_at null).
     */
    public function setup()
    {
        $user = Auth::user();

        // Si déjà activé, on redirige avec message
        if ($user->hasTwoFactorEnabled()) {
            return back()->with('success', 'L\'authentification à deux facteurs est déjà activée.');
        }

        // Créer ou récupérer le secret en attente
        $record = TwoFactorSecret::firstOrCreate(
            ['user_id' => $user->id],
            ['secret' => $this->generateSecret()]
        );

        // Si la ligne existait mais était désactivée, régénérer le secret
        if ($record->wasRecentlyCreated === false && ! $record->isEnabled()) {
            $record->update(['secret' => $this->generateSecret(), 'backup_codes' => null]);
        }

        $otpUri = $this->buildOtpUri($user->email, $record->secret);

        return view('profile.2fa-setup', compact('record', 'otpUri'));
    }

    /**
     * Valide le code TOTP et active le 2FA.
     */
    public function enable(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'digits:6'],
        ], ['code.required' => 'Le code est obligatoire.', 'code.digits' => 'Le code doit contenir 6 chiffres.']);

        $user = Auth::user();
        $record = $user->twoFactorSecret;

        if (! $record || $record->isEnabled()) {
            return back()->withErrors(['code' => 'Aucun secret en attente ou 2FA déjà actif.']);
        }

        if (! $this->verifyCode($record->secret, $request->code)) {
            return back()->withErrors(['code' => 'Code incorrect. Vérifiez l\'heure de votre téléphone et réessayez.']);
        }

        // Générer des codes de secours (8 codes de 8 caractères)
        $backupCodes = collect(range(1, 8))->map(fn () => strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)))->all();

        $record->update([
            'enabled_at' => now(),
            'backup_codes' => array_map(fn ($c) => Hash::make($c), $backupCodes),
        ]);

        return redirect()->route('profile.show')
            ->with('success', '2FA activé ! Sauvegardez vos codes de secours.')
            ->with('backup_codes', $backupCodes); // Affichés une seule fois
    }

    /**
     * Désactive le 2FA après confirmation du code actuel.
     */
    public function disable(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = Auth::user();
        $record = $user->twoFactorSecret;

        if (! $record || ! $record->isEnabled()) {
            return back()->withErrors(['code' => 'Le 2FA n\'est pas activé.']);
        }

        $valid = $this->verifyCode($record->secret, $request->code)
            || $this->checkBackupCode($record, $request->code);

        if (! $valid) {
            return back()->withErrors(['code' => 'Code incorrect.']);
        }

        try {
            $record->update(['enabled_at' => null, 'backup_codes' => null]);

            return back()->with('success', 'Authentification à deux facteurs désactivée.');
        } catch (\Throwable $e) {
            Log::error('Erreur désactivation 2FA', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return back()->withErrors(['error' => 'Erreur lors de la désactivation.']);
        }
    }

    // ── Helpers privés ──────────────────────────────────────────────────────

    private function generateSecret(): string
    {
        // Base32 charset — compatible TOTP RFC 6238
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < 32; $i++) {
            $secret .= $chars[random_int(0, 31)];
        }

        return $secret;
    }

    private function buildOtpUri(string $email, string $secret): string
    {
        $issuer = rawurlencode(config('app.name', 'TontineSN'));
        $account = rawurlencode($email);

        return "otpauth://totp/{$issuer}:{$account}?secret={$secret}&issuer={$issuer}&algorithm=SHA1&digits=6&period=30";
    }

    private function verifyCode(string $secret, string $code): bool
    {
        // Vérification manuelle TOTP (window ±1 pour tolérer les décalages d'horloge)
        $timestamp = time();
        foreach ([-30, 0, 30] as $offset) {
            $counter = (int) floor(($timestamp + $offset) / 30);
            if ($this->hotp($secret, $counter) === str_pad($code, 6, '0', STR_PAD_LEFT)) {
                return true;
            }
        }

        return false;
    }

    private function hotp(string $secret, int $counter): string
    {
        // Décodage Base32 → bytes
        $base32Chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        foreach (str_split(strtoupper($secret)) as $char) {
            $pos = strpos($base32Chars, $char);
            if ($pos === false) {
                continue;
            }
            $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }
        $bytes = '';
        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $bytes .= chr(bindec($chunk));
            }
        }

        $hash = hash_hmac('sha1', pack('N*', 0).pack('N*', $counter), $bytes, true);
        $offset = ord($hash[19]) & 0x0F;
        $code = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
             (ord($hash[$offset + 3]) & 0xFF)
        ) % 1_000_000;

        return str_pad((string) $code, 6, '0', STR_PAD_LEFT);
    }

    private function checkBackupCode(TwoFactorSecret $record, string $input): bool
    {
        $codes = $record->backup_codes ?? [];
        foreach ($codes as $i => $hashed) {
            if (Hash::check(strtoupper(trim($input)), $hashed)) {
                // Invalider le code après usage (one-time)
                $codes[$i] = 'used';
                $record->update(['backup_codes' => $codes]);

                return true;
            }
        }

        return false;
    }
}
