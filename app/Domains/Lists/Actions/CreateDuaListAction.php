<?php

namespace App\Domains\Lists\Actions;

use App\Actions\Action;
use App\Models\DuaList;
use App\Models\User;
use Illuminate\Support\Str;

class CreateDuaListAction extends Action
{
    /**
     * @param  array{title: string, occasion: string, start_date?: string|null, end_date?: string|null, cover_image_path?: string|null}  $data
     */
    public function handle(mixed ...$args): mixed
    {
        /** @var User $user */
        $user = $args[0];
        $data = $args[1];

        $duaList = DuaList::query()->create([
            'user_id' => $user->id,
            'title' => $data['title'],
            'slug' => 'pending-'.(string) Str::uuid(),
            'occasion' => $data['occasion'],
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'cover_image_path' => $data['cover_image_path'] ?? null,
            'status' => 'active',
            'published_at' => now(),
        ]);

        $duaList->forceFill([
            'slug' => $this->publicSlug($user, $data['occasion'], $duaList->id),
        ])->save();

        return $duaList;
    }

    private function publicSlug(User $user, string $occasion, int $id): string
    {
        $firstName = Str::slug($user->first_name ?: Str::before($user->name, ' ')) ?: 'user';
        $category = Str::slug($occasion) ?: 'dua';

        return "{$firstName}-{$category}-{$id}";
    }
}
