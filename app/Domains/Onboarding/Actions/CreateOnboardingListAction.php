<?php

namespace App\Domains\Onboarding\Actions;

use App\Actions\Action;
use App\Domains\Lists\Actions\CreateDuaListAction;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class CreateOnboardingListAction extends Action
{
    public function __construct(
        private readonly CreateDuaListAction $createDuaListAction,
    ) {}

    /**
     * @param  array{title: string, occasion: string, start_date: string, end_date: string}  $data
     */
    public function handle(mixed ...$args): mixed
    {
        /** @var User $user */
        $user = $args[0];
        $data = $args[1];
        /** @var UploadedFile|null $coverImage */
        $coverImage = $args[2] ?? null;

        return ($this->createDuaListAction)($user, [
            ...$data,
            'cover_image_path' => $coverImage?->store('list-covers', 'public'),
        ]);
    }
}
