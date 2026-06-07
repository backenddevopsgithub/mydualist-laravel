<?php

namespace App\Domains\Profile\Actions;

use App\Actions\Action;
use App\Models\DuaList;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UploadListImageAction extends Action
{
    /**
     * @param  array{dua_list_id: int}  $data
     */
    public function handle(mixed ...$args): mixed
    {
        /** @var User $user */
        $user = $args[0];
        $data = $args[1];
        /** @var UploadedFile $coverImage */
        $coverImage = $args[2];

        $duaList = DuaList::query()
            ->whereKey($data['dua_list_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($duaList->cover_image_path) {
            Storage::disk('public')->delete($duaList->cover_image_path);
        }

        $duaList->forceFill([
            'cover_image_path' => $coverImage->store('list-covers', 'public'),
        ])->save();

        return $duaList->fresh();
    }
}
