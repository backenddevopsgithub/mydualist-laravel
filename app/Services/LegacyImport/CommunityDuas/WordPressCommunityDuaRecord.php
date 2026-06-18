<?php

namespace App\Services\LegacyImport\CommunityDuas;

use App\Enums\CommunityDuaStatus;
use App\Enums\CommunityDuaType;
use Carbon\Carbon;

readonly class WordPressCommunityDuaRecord
{
    public function __construct(
        public int $wpPostId,
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $gender,
        public string $content,
        public CommunityDuaType $type,
        public CommunityDuaStatus $status,
        public int $requiredCompletions,
        public int $completionCount,
        public bool $isVisible,
        public ?int $wpOrderId,
        public ?Carbon $createdAt,
    ) {}

    /**
     * @return array{wp_post_id: int, email: string, type: string}
     */
    public function summary(): array
    {
        return [
            'wp_post_id' => $this->wpPostId,
            'email' => $this->email,
            'type' => $this->type->value,
        ];
    }
}
