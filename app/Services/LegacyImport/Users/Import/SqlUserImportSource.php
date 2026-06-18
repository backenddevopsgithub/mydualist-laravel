<?php

namespace App\Services\LegacyImport\Users\Import;

use App\Services\LegacyImport\Support\WordPressValueMapper;
use App\Services\LegacyImport\Users\WordPressUserRecord;
use App\Support\WordPress\SqlDumpReader;

class SqlUserImportSource implements UserImportSource
{
    private SqlDumpReader $reader;

    public function __construct(
        string $path,
        private readonly string $tablePrefix = 'wp_',
    ) {
        $this->reader = new SqlDumpReader($path, $tablePrefix);
    }

    public function records(): iterable
    {
        foreach ($this->reader->usersById() as $userId => $user) {
            $meta = $this->reader->usermetaByUserId()[$userId] ?? [];
            $record = $this->mapUser($userId, $user, $meta);

            if ($record !== null) {
                yield $userId => $record;
            }
        }
    }

    /**
     * @param  array<string, string|null>  $user
     * @param  array<string, string>  $meta
     */
    private function mapUser(int $userId, array $user, array $meta): ?WordPressUserRecord
    {
        $email = strtolower(trim((string) ($user['user_email'] ?? '')));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $verified = ($meta['_verified'] ?? '0') === '1';
        $registeredAt = WordPressValueMapper::parseDateTime($user['user_registered'] ?? null);

        return new WordPressUserRecord(
            wpLegacyId: $userId,
            email: $email,
            wpPasswordHash: WordPressValueMapper::nullableString($user['user_pass'] ?? null),
            firstName: WordPressValueMapper::nullableString($meta['first_name'] ?? null),
            lastName: WordPressValueMapper::nullableString($meta['last_name'] ?? null),
            gender: WordPressValueMapper::normalizeGender($meta['_gender'] ?? null),
            emailVerifiedAt: $verified ? ($registeredAt ?? now()) : null,
            role: WordPressValueMapper::resolveUserRole($meta[$this->capabilitiesKey($meta)] ?? null),
            registeredAt: $registeredAt,
            displayName: WordPressValueMapper::nullableString($user['display_name'] ?? null),
        );
    }

    /**
     * @param  array<string, string>  $meta
     */
    private function capabilitiesKey(array $meta): string
    {
        foreach (array_keys($meta) as $key) {
            if (str_ends_with($key, 'capabilities')) {
                return $key;
            }
        }

        return $this->tablePrefix.'capabilities';
    }
}
