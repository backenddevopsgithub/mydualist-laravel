<?php

namespace App\Services\LegacyImport\Submissions;

use Carbon\Carbon;

readonly class WordPressSubmissionRecord
{
    public function __construct(
        public ?int $wpPostId,
        public int $listWpPostId,
        public ?string $firstName,
        public ?string $lastName,
        public ?string $email,
        public ?string $gender,
        public string $content,
        public bool $isPersonalDua,
        public bool $isLocked,
        public ?int $unlockWpOrderId,
        public mixed $reported,
        public ?string $visibility,
        public mixed $status,
        public mixed $completedAt,
        public ?string $rawPhone,
        public ?Carbon $createdAt,
        public bool $fromLegacyArray = false,
    ) {}

    /**
     * @return array{wp_post_id: ?int, list_wp_post_id: int, email: ?string}
     */
    public function summary(): array
    {
        return [
            'wp_post_id' => $this->wpPostId,
            'list_wp_post_id' => $this->listWpPostId,
            'email' => $this->email,
        ];
    }
}
