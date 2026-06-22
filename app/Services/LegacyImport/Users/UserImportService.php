<?php

namespace App\Services\LegacyImport\Users;

use App\Enums\UserStatus;
use App\Models\User;
use App\Services\LegacyImport\LegacyImportReport;
use App\Services\LegacyImport\Support\LegacyImportTimestamps;
use App\Services\LegacyImport\Users\Import\UserImportSource;
use App\Services\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserImportService extends Service
{
    public function import(UserImportSource $source, bool $dryRun = false): LegacyImportReport
    {
        $report = new LegacyImportReport('users');
        $batchSize = (int) config('mydualist.legacy.import.batch_size', 100);
        $batch = [];

        foreach ($source->records() as $record) {
            $batch[] = $record;

            if (count($batch) >= $batchSize) {
                $this->processBatch($batch, $report, $dryRun);
                $batch = [];
            }
        }

        if ($batch !== []) {
            $this->processBatch($batch, $report, $dryRun);
        }

        return $report;
    }

    /**
     * @param  list<WordPressUserRecord>  $batch
     */
    private function processBatch(array $batch, LegacyImportReport $report, bool $dryRun): void
    {
        foreach ($batch as $record) {
            try {
                $this->importRecord($record, $report, $dryRun);
            } catch (\Throwable $exception) {
                $report->addFailed($record->summary(), $exception->getMessage());
            }
        }
    }

    private function importRecord(WordPressUserRecord $record, LegacyImportReport $report, bool $dryRun): void
    {
        $existingByLegacy = User::query()->where('wp_legacy_id', $record->wpLegacyId)->first();
        $existingByEmail = User::query()->where('email', $record->email)->first();

        if ($this->hasConflict($record, $existingByLegacy, $existingByEmail)) {
            $report->addFailed($record->summary(), 'Email already belongs to a different wp_legacy_id.');

            return;
        }

        $existingUser = $existingByLegacy ?? $existingByEmail;
        $isNew = $existingUser === null;
        $isEmailReconciliation = ! $isNew && $existingByLegacy === null;

        if ($dryRun) {
            if ($isNew) {
                $report->addImported($record->summary());
            } elseif ($isEmailReconciliation) {
                $report->addReconciled($record->summary());
            } else {
                $report->addUpdated($record->summary());
            }

            return;
        }

        DB::transaction(function () use ($record, $report, $existingUser, $isNew, $isEmailReconciliation): void {
            if ($isNew) {
                $user = User::query()->create($this->attributesForNewUser($record));
                LegacyImportTimestamps::apply($user, $record->registeredAt);

                $report->addImported($record->summary());

                return;
            }

            $this->applyImportToExistingUser($existingUser, $record);
            LegacyImportTimestamps::apply($existingUser->fresh(), $record->registeredAt);

            if ($isEmailReconciliation) {
                $report->addReconciled($record->summary());
            } else {
                $report->addUpdated($record->summary());
            }
        });
    }

    private function hasConflict(
        WordPressUserRecord $record,
        ?User $existingByLegacy,
        ?User $existingByEmail,
    ): bool {
        if (
            $existingByLegacy !== null
            && $existingByEmail !== null
            && $existingByLegacy->id !== $existingByEmail->id
        ) {
            return true;
        }

        return $existingByEmail !== null
            && $existingByEmail->wp_legacy_id !== null
            && $existingByEmail->wp_legacy_id !== $record->wpLegacyId
            && ($existingByLegacy === null || $existingByLegacy->id !== $existingByEmail->id);
    }

    /**
     * @return array<string, mixed>
     */
    private function attributesForNewUser(WordPressUserRecord $record): array
    {
        $attributes = [
            'email' => $record->email,
            'wp_legacy_id' => $record->wpLegacyId,
            'wp_password_hash' => $record->wpPasswordHash,
            'first_name' => $record->firstName,
            'last_name' => $record->lastName,
            'gender' => $record->gender,
            'role' => $record->role,
            'status' => UserStatus::Active,
            'name' => $this->resolveName($record),
            'email_verified_at' => $record->emailVerifiedAt,
            'password' => Str::password(32),
        ];

        return $attributes;
    }

    private function applyImportToExistingUser(User $user, WordPressUserRecord $record): void
    {
        $attributes = [
            'email' => $record->email,
            'wp_legacy_id' => $record->wpLegacyId,
            'wp_password_hash' => $record->wpPasswordHash,
        ];

        if ($this->shouldOverrideRoleOnReconcile()) {
            $attributes['role'] = $record->role;
        }

        if (blank($user->first_name) && filled($record->firstName)) {
            $attributes['first_name'] = $record->firstName;
        }

        if (blank($user->last_name) && filled($record->lastName)) {
            $attributes['last_name'] = $record->lastName;
        }

        if (blank($user->gender) && filled($record->gender)) {
            $attributes['gender'] = $record->gender;
        }

        if (blank($user->name)) {
            $attributes['name'] = $this->resolveName($record);
        }

        if ($user->email_verified_at === null && $record->emailVerifiedAt !== null) {
            $attributes['email_verified_at'] = $record->emailVerifiedAt;
        }

        $user->fill($attributes)->save();
    }

    private function resolveName(WordPressUserRecord $record): string
    {
        $name = trim(implode(' ', array_filter([$record->firstName, $record->lastName])));

        if ($name === '') {
            $name = $record->displayName ?: $record->email;
        }

        return $name;
    }

    private function shouldOverrideRoleOnReconcile(): bool
    {
        return (bool) config('mydualist.legacy.import.override_roles_on_reconcile', false);
    }
}
