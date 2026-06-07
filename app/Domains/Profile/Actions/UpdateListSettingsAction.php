<?php

namespace App\Domains\Profile\Actions;

use App\Actions\Action;
use App\Models\DuaList;
use App\Models\User;

class UpdateListSettingsAction extends Action
{
    /**
     * @param  array{dua_list_id: int, dua_limit_per_person?: int|null, display_order: string, email_frequency: string}  $data
     */
    public function handle(mixed ...$args): mixed
    {
        /** @var User $user */
        $user = $args[0];
        $data = $args[1];

        $duaList = DuaList::query()
            ->whereKey($data['dua_list_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        $duaList->forceFill([
            'dua_limit_per_person' => $data['dua_limit_per_person'] ?? null,
            'display_order' => $data['display_order'],
            'email_frequency' => $data['email_frequency'],
        ])->save();

        return $duaList->fresh();
    }
}
