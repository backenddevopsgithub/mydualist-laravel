<?php

namespace App\Domains\Support\Actions;

use App\Actions\Action;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class CreateSupportTicketAction extends Action
{
    /**
     * @param  array{reason: string, email: string, first_name: string, surname: string, comments: string}  $data
     */
    public function handle(mixed ...$args): mixed
    {
        /** @var User $user */
        $user = $args[0];
        $data = $args[1];
        /** @var UploadedFile|null $image */
        $image = $args[2] ?? null;

        $imagePath = $image?->store('support-uploads', 'public');

        return SupportTicket::query()->create([
            'user_id' => $user->id,
            'reason' => $data['reason'],
            'email' => $data['email'],
            'first_name' => $data['first_name'],
            'surname' => $data['surname'],
            'comments' => $data['comments'],
            'image_path' => $imagePath,
        ]);
    }
}
