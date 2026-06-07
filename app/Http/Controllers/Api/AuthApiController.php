<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\RecalculateCreditScore;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthApiController extends Controller
{
    public function __construct(private NotificationService $notifier) {}

    public function register(Request $request)
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:100'],
            'email'        => ['required', 'email', 'unique:users,email'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'password'     => ['required', 'min:8', 'confirmed'],
            'ref'          => ['nullable', 'string', 'size:8'],
        ]);

        $referrer = isset($data['ref'])
            ? User::where('referral_code', strtoupper($data['ref']))->first()
            : null;

        $user = User::create([
            'name'         => $data['name'],
            'email'        => $data['email'],
            'phone_number' => $data['phone_number'] ?? null,
            'password'     => Hash::make($data['password']),
            'role'         => 'member',
            'referred_by'  => $referrer?->id,
        ]);

        // Notifier le parrain + recalculer son score
        if ($referrer) {
            $this->notifier->notifyReferralJoined($referrer, $user);
            RecalculateCreditScore::dispatch($referrer->id)->afterResponse();
        }

        return response()->json([
            'token' => $user->createToken('mobile')->plainTextToken,
            'user'  => $this->userResource($user),
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email ou mot de passe incorrect.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Votre compte a été désactivé. Contactez l\'administrateur.'],
            ]);
        }

        $user->tokens()->where('name', 'mobile')->delete();

        return response()->json([
            'token' => $user->createToken('mobile')->plainTextToken,
            'user'  => $this->userResource($user),
        ]);
    }

    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken();
        if ($token) {
            $token->delete();
        }
        return response()->json(['message' => 'Déconnecté.']);
    }

    public function me(Request $request)
    {
        return response()->json($this->userResource($request->user()->load('creditScore')));
    }

    private function userResource(User $user): array
    {
        return [
            'id'            => $user->id,
            'name'          => $user->name,
            'email'         => $user->email,
            'phone_number'  => $user->phone_number,
            'role'          => $user->role,
            'referral_code' => $user->referral_code,
            'referral_link' => route('auth.register', ['ref' => $user->referral_code]),
            'avatar'        => $user->avatar
                ? (str_starts_with($user->avatar, 'http') ? $user->avatar : asset('storage/' . $user->avatar))
                : null,
            'credit_score'  => $user->creditScore ? [
                'score' => $user->creditScore->score,
                'badge' => $user->creditScore->badge,
            ] : null,
        ];
    }
}
