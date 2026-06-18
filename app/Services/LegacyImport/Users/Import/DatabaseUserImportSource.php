<?php

namespace App\Services\LegacyImport\Users\Import;

use App\Services\LegacyImport\Support\WordPressValueMapper;
use App\Services\LegacyImport\Users\WordPressUserRecord;
use App\Support\WordPress\WordPressConnection;

class DatabaseUserImportSource implements UserImportSource
{
    public function records(): iterable
    {
        $connection = WordPressConnection::connection();
        $prefix = WordPressConnection::prefix();

        $users = $connection->table("{$prefix}users")
            ->orderBy('ID')
            ->get([
                'ID',
                'user_email',
                'user_pass',
                'user_registered',
                'display_name',
            ]);

        foreach ($users as $user) {
            $userId = (int) $user->ID;
            $meta = $this->userMeta($connection, $prefix, $userId);

            $record = $this->mapUser($userId, (array) $user, $meta);

            if ($record !== null) {
                yield $userId => $record;
            }
        }
    }

    /**
     * @return array<string, string>
     */
    private function userMeta(\Illuminate\Database\Connection $connection, string $prefix, int $userId): array
    {
        return $connection->table("{$prefix}usermeta")
            ->where('user_id', $userId)
            ->pluck('meta_value', 'meta_key')
            ->map(fn ($value): string => (string) $value)
            ->all();
    }

    /**
     * @param  array<string, mixed>  $user
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

        return 'wp_capabilities';
    }
}
