<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user()->load('creditScore', 'badges');
        $referralLink = route('auth.register', ['ref' => $user->referral_code]);
        $referralsCount = $user->referrals()->count();
        return view('profile.show', compact('user', 'referralLink', 'referralsCount'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name'         => ['required', 'string', 'max:100'],
            'email'        => ['required', 'email', 'unique:users,email,' . $user->id],
            'phone_number' => ['nullable', 'string', 'regex:/^\+?[0-9\s\-]{7,20}$/'],
            'avatar'       => ['nullable', 'image', 'max:2048'],
        ], [
            'name.required'       => 'Le nom est obligatoire.',
            'email.required'      => "L'email est obligatoire.",
            'email.unique'        => 'Cet email est déjà utilisé.',
            'avatar.image'        => 'Le fichier doit être une image.',
            'avatar.max'          => "L'image ne doit pas dépasser 2 Mo.",
            'phone_number.regex'  => 'Format de téléphone invalide (ex: +221 77 000 00 00).',
        ]);

        try {
            $data = [
                'name'         => $request->name,
                'email'        => $request->email,
                'phone_number' => $request->phone_number,
            ];

            if ($request->hasFile('avatar')) {
                if ($user->avatar && !str_starts_with($user->avatar, 'http')) {
                    Storage::disk('public')->delete($user->avatar);
                }
                $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
            }

            $user->update($data);

            return back()->with('success', 'Profil mis à jour.');
        } catch (\Throwable $e) {
            Log::error('Erreur mise à jour profil', ['user_id' => $user->id, 'error' => $e->getMessage(), 'class' => get_class($e)]);
            return back()->withErrors(['error' => 'Erreur lors de la mise à jour du profil.']);
        }
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        if (empty($user->password)) {
            return back()->withErrors(['current_password' => 'Votre compte est connecté via Google. Vous ne pouvez pas définir un mot de passe ici.']);
        }

        $request->validate([
            'current_password' => ['required'],
            'password'         => ['required', 'min:8', 'confirmed'],
        ], [
            'current_password.required' => 'Le mot de passe actuel est obligatoire.',
            'password.min'              => 'Le nouveau mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed'        => 'Les mots de passe ne correspondent pas.',
        ]);

        try {
            if (!Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => 'Mot de passe actuel incorrect.']);
            }

            $user->update(['password' => Hash::make($request->password)]);

            return back()->with('success', 'Mot de passe mis à jour.');
        } catch (\Throwable $e) {
            Log::error('Erreur mise à jour mot de passe', ['user_id' => $user->id, 'error' => $e->getMessage(), 'class' => get_class($e)]);
            return back()->withErrors(['error' => 'Erreur lors de la mise à jour du mot de passe.']);
        }
    }

    public function deleteAccount(Request $request)
    {
        $request->validate([
            'confirm_delete' => ['required', 'in:SUPPRIMER'],
        ], [
            'confirm_delete.required' => 'Veuillez taper SUPPRIMER pour confirmer.',
            'confirm_delete.in'       => 'Veuillez taper exactement SUPPRIMER pour confirmer.',
        ]);

        $user = Auth::user();

        try {
            $activeMemberships = $user->memberships()
                ->wherePivot('status', 'active')
                ->whereIn('tontines.status', ['active', 'pending'])
                ->exists();

            if ($activeMemberships) {
                return back()->withErrors(['confirm_delete' => 'Vous ne pouvez pas supprimer votre compte tant que vous êtes membre actif d\'une tontine en cours. Quittez d\'abord toutes vos tontines.']);
            }

            // Supprimer le fichier KYC avant d'effacer le chemin en base
            if ($user->kyc_document) {
                Storage::disk('local')->delete($user->kyc_document);
            }

            $user->update([
                'name'              => 'Anonyme-' . $user->id,
                'email'             => 'anonyme-' . $user->id . '@deleted.tontine.sn',
                'phone_number'      => null,
                'avatar'            => null,
                'kyc_document'      => null,
                'kyc_document_hash' => null,
                'google_id'         => null,
                'password'          => \Illuminate\Support\Str::random(64),
            ]);

            // Révoquer tous les tokens API avant suppression
            $user->tokens()->delete();

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $user->delete();

            return redirect()->route('home')->with('status', 'Votre compte a été supprimé. Vos données seront effacées conformément à notre politique de confidentialité.');
        } catch (\Throwable $e) {
            Log::error('Erreur suppression compte', ['user_id' => $user->id, 'error' => $e->getMessage(), 'class' => get_class($e)]);
            return back()->withErrors(['error' => 'Erreur lors de la suppression du compte.']);
        }
    }

    public function publicProfile(User $user)
    {
        abort_if(!$user->is_active, 404);

        $activeTontinesCount = $user->memberships()
            ->wherePivot('status', 'active')
            ->where('tontines.status', 'active')
            ->count();

        $user->load('creditScore');

        return view('profile.public', compact('user', 'activeTontinesCount'));
    }

    public function notificationSettings()
    {
        $user = Auth::user();

        return view('profile.notifications', compact('user'));
    }

    public function updateNotificationSettings(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'settings' => ['nullable', 'array'],
        ]);

        try {
            $settings = $request->input('settings', []);

            $normalized = [];
            foreach ($settings as $key => $value) {
                $normalized[$key] = (bool) $value;
            }

            $user->update(['notification_settings' => $normalized]);

            return back()->with('success', 'Préférences de notification mises à jour.');
        } catch (\Throwable $e) {
            Log::error('Erreur mise à jour notifications', ['user_id' => $user->id, 'error' => $e->getMessage(), 'class' => get_class($e)]);
            return back()->withErrors(['error' => 'Erreur lors de la mise à jour des préférences.']);
        }
    }

    public function uploadKyc(Request $request)
    {
        $request->validate([
            'kyc_document' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'kyc_consent'  => ['required', 'accepted'],
        ], [
            'kyc_document.required' => 'Veuillez sélectionner un document.',
            'kyc_document.mimes'    => 'Formats acceptés : JPG, PNG, PDF.',
            'kyc_document.max'      => 'Le fichier ne doit pas dépasser 5 Mo.',
            'kyc_consent.required'  => 'Vous devez accepter les conditions de traitement de vos données.',
            'kyc_consent.accepted'  => 'Vous devez accepter les conditions de traitement de vos données.',
        ]);

        $user = Auth::user();

        try {
            // Détection anti-doublon (SHA-256, non réversible)
            $hash = hash_file('sha256', $request->file('kyc_document')->getRealPath());
            $duplicate = User::where('kyc_document_hash', $hash)
                ->where('id', '!=', $user->id)
                ->exists();

            if ($duplicate) {
                return back()->withErrors(['kyc_document' => 'Ce document est déjà associé à un autre compte.']);
            }

            // Supprimer l'ancien fichier du stockage privé
            if ($user->kyc_document) {
                Storage::disk('local')->delete($user->kyc_document);
            }

            // Stockage privé (non accessible publiquement)
            $path = $request->file('kyc_document')->store('kyc', 'local');
            $user->update([
                'kyc_document'      => $path,
                'kyc_verified'      => false,
                'kyc_status'        => 'pending',
                'kyc_rejected_reason' => null,
                'kyc_document_hash' => $hash,
            ]);

            return back()->with('success', 'Document soumis. Votre identité sera vérifiée sous 24-48h.');
        } catch (\Throwable $e) {
            Log::error('Erreur upload KYC', ['user_id' => $user->id, 'error' => $e->getMessage(), 'class' => get_class($e)]);
            return back()->withErrors(['kyc_document' => 'Erreur lors de l\'upload du document.']);
        }
    }

    public function exportData()
    {
        $user = Auth::user()->load('creditScore', 'badges', 'transactions.cycle.tontine', 'memberships', 'referrals');

        $data = [
            'exported_at'  => now()->toIso8601String(),
            'profile'      => [
                'name'                 => $user->name,
                'email'                => $user->email,
                'phone_number'         => $user->phone_number,
                'role'                 => $user->role,
                'kyc_verified'         => $user->kyc_verified,
                'kyc_status'           => $user->kyc_status,
                'kyc_rejected_reason'  => $user->kyc_rejected_reason,
                'referral_code'        => $user->referral_code,
                'referrals'            => $user->referrals->count(),
                'created_at'           => $user->created_at->toIso8601String(),
            ],
            'credit_score' => $user->creditScore ? [
                'score'            => $user->creditScore->score,
                'badge'            => $user->creditScore->badge,
                'on_time_payments' => $user->creditScore->on_time_payments,
                'total_cycles'     => $user->creditScore->total_cycles,
            ] : null,
            'transactions' => $user->transactions->map(fn($tx) => [
                'amount'      => $tx->amount,
                'method'      => $tx->method,
                'status'      => $tx->status,
                'tontine'     => $tx->cycle?->tontine?->name,
                'cycle'       => $tx->cycle?->cycle_number,
                'created_at'  => $tx->created_at->toIso8601String(),
            ]),
            'tontines'     => $user->memberships->map(fn($t) => [
                'name'      => $t->name,
                'code'      => $t->code,
                'status'    => $t->status,
                'role'      => $t->pivot->status,
                'joined_at' => $t->pivot->joined_at,
            ]),
            'badges'       => $user->badges->map(fn($b) => [
                'name'      => $b->name,
                'tier'      => $b->tier,
                'earned_at' => $b->pivot->earned_at,
            ]),
        ];

        return response()->json($data, 200, [
            'Content-Disposition' => 'attachment; filename="mes-donnees-tontinesn.json"',
            'Content-Type'        => 'application/json',
        ]);
    }
}
