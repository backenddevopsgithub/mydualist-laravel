<?php

namespace App\Services\LegacyImport\Users;

use App\Enums\UserStatus;
use App\Models\User;
use App\Services\LegacyImport\LegacyImportReport;
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

        if (
            $existingByEmail !== null
            && $existingByEmail->wp_legacy_id !== null
            && $existingByEmail->wp_legacy_id !== $record->wpLegacyId
        ) {
            $report->addFailed($record->summary(), 'Email already belongs to a different wp_legacy_id.');

            return;
        }

        if ($dryRun) {
            if ($existingByLegacy !== null || $existingByEmail !== null) {
                $report->addUpdated($record->summary());
            } else {
                $report->addImported($record->summary());
            }

            return;
        }

        DB::transaction(function () use ($record, $report, $existingByLegacy, $existingByEmail): void {
            $name = trim(implode(' ', array_filter([$record->firstName, $record->lastName])));

            if ($name === '') {
                $name = $record->displayName ?: $record->email;
            }

            $attributes = [
                'email' => $record->email,
                'name' => $name,
                'first_name' => $record->firstName,
                'last_name' => $record->lastName,
                'gender' => $record->gender,
                'role' => $record->role,
                'status' => UserStatus::Active,
                'wp_password_hash' => $record->wpPasswordHash,
                'email_verified_at' => $record->emailVerifiedAt,
            ];

            if ($existingByLegacy === null && $existingByEmail === null) {
                $attributes['password'] = Str::password(32);
            }

            if ($record->registeredAt !== null) {
                $attributes['created_at'] = $record->registeredAt;
                $attributes['updated_at'] = $record->registeredAt;
            }

            User::query()->updateOrCreate(
                ['wp_legacy_id' => $record->wpLegacyId],
                $attributes,
            );

            if ($existingByLegacy === null && $existingByEmail === null) {
                $report->addImported($record->summary());
            } else {
                $report->addUpdated($record->summary());
            }
        });
    }
}
