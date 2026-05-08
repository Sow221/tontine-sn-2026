<?php

namespace App\Policies;

use App\Models\Tontine;
use App\Models\User;

class TontinePolicy
{
    public function view(User $user, Tontine $tontine): bool
    {
        return $user->isAdmin()
            || $tontine->created_by === $user->id
            || $tontine->members()->where('users.id', $user->id)->exists();
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
