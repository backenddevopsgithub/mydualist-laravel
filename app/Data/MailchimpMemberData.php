<?php

namespace App\Data;

readonly class MailchimpMemberData
{
    public function __construct(
        public string $email,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $category = null,
        public ?string $listName = null,
        public ?string $startDate = null,
        public ?string $endDate = null,
        public ?int $submissionCount = null,
    ) {}

    public function normalizedEmail(): string
    {
        return mb_strtolower($this->email);
    }

    public function subscriberHash(): string
    {
        return md5($this->normalizedEmail());
    }

    /**
     * @return array<string, mixed>
     */
    public function baseMergeFields(): array
    {
        return array_filter([
            'FNAME' => $this->firstName ?? '',
            'LNAME' => $this->lastName ?? '',
        ], fn (mixed $value): bool => $value !== null);
    }

    /**
     * @return array<string, mixed>
     */
    public function listCreatorMergeFields(): array
    {
        return array_merge($this->baseMergeFields(), array_filter([
            'MMERGE13' => $this->submissionCount,
            'MMERGE15' => $this->category ?? '',
            'MMERGE6' => $this->listName ?? '',
            'MMERGE16' => $this->startDate ?? '',
            'MMERGE17' => $this->endDate ?? '',
        ], fn (mixed $value): bool => $value !== null));
    }
}
