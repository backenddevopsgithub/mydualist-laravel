<?php

namespace App\Services\LegacyImport\Users;

use App\Enums\UserRole;
use Carbon\Carbon;

readonly class WordPressUserRecord
{
    public function __construct(
        public int $wpLegacyId,
        public string $email,
        public ?string $wpPasswordHash,
        public ?string $firstName,
        public ?string $lastName,
        public ?string $gender,
        public ?Carbon $emailVerifiedAt,
        public UserRole $role,
        public ?Carbon $registeredAt,
        public ?string $displayName,
    ) {}

    /**
     * @return array{wp_legacy_id: int, email: string}
     */
    public function summary(): array
    {
        return [
            'wp_legacy_id' => $this->wpLegacyId,
            'email' => $this->email,
        ];
    }
}
