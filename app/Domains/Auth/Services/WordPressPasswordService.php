<?php

namespace App\Domains\Auth\Services;

use App\Models\User;
use App\Services\Service;
use Hautelook\Phpass\PasswordHash;
use Illuminate\Support\Facades\Hash;

class WordPressPasswordService extends Service
{
    public function verify(string $plainPassword, User $user): bool
    {
        if ($user->password !== null && Hash::check($plainPassword, $user->password)) {
            return true;
        }

        if ($user->wp_password_hash === null) {
            return false;
        }

        $hasher = new PasswordHash(8, true);

        return $hasher->CheckPassword($plainPassword, $user->wp_password_hash);
    }

    public function upgradeFromLegacyHash(User $user, string $plainPassword): User
    {
        $user->forceFill([
            'password' => $plainPassword,
            'wp_password_hash' => null,
        ])->save();

        return $user->fresh();
    }
}
