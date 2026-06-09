<?php

namespace App\Policies;

use App\Models\Tontine;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class TontinePolicy
{
    public function view(User $user, Tontine $tontine): bool
    {
        if ($user->isAdmin() || $tontine->created_by === $user->id) return true;

        // Tontine publique visible par tous (pending/active)
        if ($tontine->isPublic() && in_array($tontine->status, ['pending', 'active'])) return true;

        return Cache::remember(
            "tontine_member_{$tontine->id}_{$user->id}",
            now()->addMinutes(5),
            fn() => $tontine->members()->where('users.id', $user->id)->exists()
        );
    }

    public function update(User $user, Tontine $tontine): bool
    {
        return $user->isAdmin() || $tontine->created_by === $user->id;
    }

    public function delete(User $user, Tontine $tontine): bool
    {
        return $user->isAdmin()
            || ($tontine->created_by === $user->id && $tontine->status === 'pending');
    }
}
