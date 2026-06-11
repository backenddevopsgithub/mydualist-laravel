<?php

namespace App\Domains\Submissions\Actions;

use App\Actions\Action;
use App\Enums\DuaSubmissionStatus;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateDuaSubmissionAction extends Action
{
    private const MAX_PER_EMAIL_PER_LIST = 35;

    /**
     * @param  array{first_name?: string|null, last_name?: string|null, email?: string|null, content?: string, duas?: array<int, string>, note?: string|null, is_anonymous?: bool}  $data
     */
    public function handle(mixed ...$args): mixed
    {
        /** @var DuaList $duaList */
        $duaList = $args[0];
        $data = $args[1];
        /** @var User|null $user */
        $user = $args[2] ?? null;

        return DB::transaction(function () use ($duaList, $data, $user): Collection {
            /** @var DuaList $lockedList */
            $lockedList = DuaList::query()
                ->whereKey($duaList->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($lockedList->acceptsSubmissions(), 403, $lockedList->closedReason() ?? 'This list is not accepting submissions.');

            $email = isset($data['email']) ? mb_strtolower((string) $data['email']) : null;
            $contents = $this->contents($data);

            if ($email) {
                $limit = $lockedList->dua_limit_per_person ?: self::MAX_PER_EMAIL_PER_LIST;
                $submittedCount = DuaSubmission::query()
                    ->where('dua_list_id', $lockedList->id)
                    ->where('email', $email)
                    ->count();

                if ($submittedCount + count($contents) > $limit) {
                    throw ValidationException::withMessages([
                        'email' => 'You have reached the submission limit for this list.',
                    ]);
                }
            }

            return collect($contents)
                ->map(fn (string $content): DuaSubmission => DuaSubmission::query()->create([
                    'dua_list_id' => $lockedList->id,
                    'user_id' => $user?->id,
                    'first_name' => $data['first_name'] ?? null,
                    'last_name' => $data['last_name'] ?? null,
                    'email' => $email,
                    'is_anonymous' => false,
                    'content' => $content,
                    'note' => null,
                    'status' => DuaSubmissionStatus::Pending,
                ]));
        });
    }

    /**
     * @param  array{content?: string, duas?: array<int, string>}  $data
     * @return list<string>
     */
    private function contents(array $data): array
    {
        $contents = $data['duas'] ?? [$data['content'] ?? ''];

        return collect($contents)
            ->map(fn (mixed $content): string => trim((string) $content))
            ->filter()
            ->values()
            ->all();
    }
}
