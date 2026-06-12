<?php

namespace App\Domains\Submissions\Actions;

use App\Actions\Action;
use App\Enums\DuaSubmissionStatus;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;
use Illuminate\Support\Str;

class CreatePersonalDuaAction extends Action
{
    public function handle(mixed ...$args): mixed
    {
        /** @var DuaList $duaList */
        $duaList = $args[0];
        /** @var User $user */
        $user = $args[1];
        /** @var string $content */
        $content = $args[2];

        return DuaSubmission::query()->create([
            'dua_list_id' => $duaList->id,
            'user_id' => $user->id,
            'first_name' => $user->first_name ?: Str::before($user->name, ' '),
            'last_name' => $user->last_name ?: Str::after($user->name, ' '),
            'email' => $user->email,
            'is_anonymous' => false,
            'is_personal_dua' => true,
            'content' => trim($content),
            'note' => null,
            'status' => DuaSubmissionStatus::Pending,
        ]);
    }
}
