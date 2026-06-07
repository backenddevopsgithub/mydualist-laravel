<?php

namespace App\Domains\Submissions\Actions;

use App\Actions\Action;
use App\Enums\DuaSubmissionStatus;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateDuaSubmissionAction extends Action
{
    private const MAX_PER_EMAIL_PER_LIST = 3;

    /**
     * @param  array{first_name?: string|null, last_name?: string|null, email?: string|null, content: string, note?: string|null, is_anonymous?: bool}  $data
     */
    public function handle(mixed ...$args): mixed
    {
        /** @var DuaList $duaList */
        $duaList = $args[0];
        $data = $args[1];
        /** @var User|null $user */
        $user = $args[2] ?? null;

        return DB::transaction(function () use ($duaList, $data, $user): DuaSubmission {
            /** @var DuaList $lockedList */
            $lockedList = DuaList::query()
                ->whereKey($duaList->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($lockedList->acceptsSubmissions(), 403, $lockedList->closedReason() ?? 'This list is not accepting submissions.');

            $email = isset($data['email']) ? mb_strtolower((string) $data['email']) : null;

            if ($email) {
                $submittedCount = DuaSubmission::query()
                    ->where('dua_list_id', $lockedList->id)
                    ->where('email', $email)
                    ->count();

                if ($submittedCount >= self::MAX_PER_EMAIL_PER_LIST) {
                    throw ValidationException::withMessages([
                        'email' => 'You have reached the submission limit for this list.',
                    ]);
                }
            }

            return DuaSubmission::query()->create([
                'dua_list_id' => $lockedList->id,
                'user_id' => $user?->id,
                'first_name' => $data['is_anonymous'] ?? false ? null : ($data['first_name'] ?? null),
                'last_name' => $data['is_anonymous'] ?? false ? null : ($data['last_name'] ?? null),
                'email' => $email,
                'is_anonymous' => (bool) ($data['is_anonymous'] ?? false),
                'content' => $data['content'],
                'note' => $data['note'] ?? null,
                'status' => DuaSubmissionStatus::Pending,
            ]);
        });
    }
}
