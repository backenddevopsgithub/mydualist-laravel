<?php

namespace App\Domains\Community\Actions;

use App\Actions\Action;
use App\Enums\CommunityDuaStatus;
use App\Enums\CommunityDuaType;
use App\Models\CommunityDua;
use App\Domains\Community\Services\CommunityDuaQueueService;
use App\Support\WhatsAppNotificationFieldsResolver;

class CreateFreeCommunityDuaAction extends Action
{
    public function __construct(
        private readonly CommunityDuaQueueService $queue,
        private readonly WhatsAppNotificationFieldsResolver $whatsappFields,
    ) {}

    /**
     * @param  array{first_name: string, last_name: string, email: string, gender: string, content: string, whatsapp_notifications?: bool|null, whatsapp_country_code?: string|null, whatsapp_phone?: string|null, whatsapp_verification_token?: string|null}  $data
     */
    public function handle(mixed ...$args): mixed
    {
        $data = $args[0];
        $whatsapp = $this->whatsappFields->resolve($data);

        $communityDua = CommunityDua::query()->create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'gender' => $data['gender'],
            'whatsapp_country_code' => $whatsapp['whatsapp_country_code'],
            'whatsapp_phone' => $whatsapp['whatsapp_phone'],
            'whatsapp_verified_at' => $whatsapp['whatsapp_verified_at'],
            'content' => $data['content'],
            'type' => CommunityDuaType::Free,
            'status' => CommunityDuaStatus::Active,
            'required_completions' => CommunityDuaType::Free->requiredCompletions(),
            'completion_count' => 0,
            'is_visible' => true,
        ]);

        $this->queue->notifyWaitingUsersOfNewDua($communityDua);

        return $communityDua;
    }
}
