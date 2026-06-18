<?php

namespace App\Services\LegacyImport\Users\Import;

use App\Services\LegacyImport\Support\WordPressValueMapper;
use App\Services\LegacyImport\Users\WordPressUserRecord;
use RuntimeException;

class CsvUserImportSource implements UserImportSource
{
    public function __construct(
        private readonly string $path,
    ) {}

    public function records(): iterable
    {
        if (! is_readable($this->path)) {
            throw new RuntimeException("CSV import file is not readable: {$this->path}");
        }

        $handle = fopen($this->path, 'r');

        if ($handle === false) {
            throw new RuntimeException("Unable to open CSV import file: {$this->path}");
        }

        try {
            $headers = fgetcsv($handle);

            if ($headers === false) {
                return;
            }

            $headers = array_map(fn (string $header): string => strtolower(trim($header)), $headers);

            while (($row = fgetcsv($handle)) !== false) {
                if ($row === [null] || $row === []) {
                    continue;
                }

                /** @var array<string, string|null> $data */
                $data = array_combine($headers, array_pad($row, count($headers), null));

                if ($data === false) {
                    continue;
                }

                $record = $this->mapRow($data);

                if ($record !== null) {
                    yield $record->wpLegacyId => $record;
                }
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  array<string, string|null>  $data
     */
    private function mapRow(array $data): ?WordPressUserRecord
    {
        $wpLegacyId = (int) ($data['wp_legacy_id'] ?? $data['id'] ?? 0);
        $email = strtolower(trim((string) ($data['user_email'] ?? $data['email'] ?? '')));

        if ($wpLegacyId <= 0 || $email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $verified = in_array(strtolower((string) ($data['verified'] ?? $data['_verified'] ?? '0')), ['1', 'true', 'yes'], true);
        $registeredAt = WordPressValueMapper::parseDateTime($data['user_registered'] ?? $data['registered_at'] ?? null);

        return new WordPressUserRecord(
            wpLegacyId: $wpLegacyId,
            email: $email,
            wpPasswordHash: WordPressValueMapper::nullableString($data['user_pass'] ?? $data['wp_password_hash'] ?? null),
            firstName: WordPressValueMapper::nullableString($data['first_name'] ?? null),
            lastName: WordPressValueMapper::nullableString($data['last_name'] ?? null),
            gender: WordPressValueMapper::normalizeGender($data['gender'] ?? $data['_gender'] ?? null),
            emailVerifiedAt: $verified ? ($registeredAt ?? now()) : null,
            role: WordPressValueMapper::resolveUserRole($data['wp_capabilities'] ?? $data['capabilities'] ?? null),
            registeredAt: $registeredAt,
            displayName: WordPressValueMapper::nullableString($data['display_name'] ?? null),
        );
    }
}
