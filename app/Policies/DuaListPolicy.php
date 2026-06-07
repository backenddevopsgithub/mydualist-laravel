<?php

namespace App\Policies;

use App\Models\DuaList;
use App\Models\User;

class DuaListPolicy
{
    public function view(User $user, DuaList $duaList): bool
    {
        return $this->owns($user, $duaList) || $user->isAdmin();
    }

    public function update(User $user, DuaList $duaList): bool
    {
        return $this->owns($user, $duaList) || $user->isAdmin();
    }

    public function archive(User $user, DuaList $duaList): bool
    {
        return $this->owns($user, $duaList) || $user->isAdmin();
    }

    public function restore(User $user, DuaList $duaList): bool
    {
        return $this->owns($user, $duaList) || $user->isAdmin();
    }

    public function delete(User $user, DuaList $duaList): bool
    {
        return $this->owns($user, $duaList) || $user->isAdmin();
    }

    private function owns(User $user, DuaList $duaList): bool
    {
        return $duaList->user_id === $user->id;
    }
}
