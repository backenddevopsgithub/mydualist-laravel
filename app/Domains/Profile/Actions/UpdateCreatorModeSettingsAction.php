<?php

namespace App\Domains\Profile\Actions;

use App\Actions\Action;
use App\Models\DuaList;
use App\Models\User;
use App\Support\CreatorMode;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class UpdateCreatorModeSettingsAction extends Action
{
    /**
     * @param  array{dua_list_id: int, donation_link?: string|null, donation_note?: string|null}  $data
     */
    public function handle(mixed ...$args): mixed
    {
        /** @var User $user */
        $user = $args[0];
        $data = $args[1];

        if (! CreatorMode::enabled()) {
            throw ValidationException::withMessages([
                'creator_mode' => 'Creator Mode is not available.',
            ]);
        }

        $duaList = DuaList::query()
            ->whereKey($data['dua_list_id'])
            ->where('user_id', $user->id)
            ->where('list_mode', CreatorMode::MODE_CREATOR)
            ->firstOrFail();

        Gate::authorize('update', $duaList);

        $updates = [];

        if (array_key_exists('donation_link', $data) && filled($data['donation_link'])) {
            $updates['donation_link'] = $data['donation_link'];
        }

        if (array_key_exists('donation_note', $data) && filled($data['donation_note'])) {
            $updates['donation_note'] = $data['donation_note'];
        }

        if ($updates !== []) {
            $duaList->update($updates);
        }

        return $duaList->fresh();
    }
}
