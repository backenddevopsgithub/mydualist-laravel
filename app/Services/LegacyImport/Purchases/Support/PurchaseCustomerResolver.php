<?php

namespace App\Services\LegacyImport\Purchases\Support;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Services\LegacyImport\LegacyImportReport;
use App\Services\LegacyImport\Purchases\WordPressOrderRecord;
use Illuminate\Support\Str;

class PurchaseCustomerResolver
{
    public function resolve(
        WordPressOrderRecord $record,
        LegacyImportReport $report,
        bool $dryRun,
    ): ?User {
        if ($record->customerWpLegacyId !== null) {
            return User::query()->where('wp_legacy_id', $record->customerWpLegacyId)->first();
        }

        $billingEmail = WordPressOrderBillingEmailResolver::normalize($record->billingEmail);

        if ($billingEmail === null) {
            return null;
        }

        $existingUser = User::query()->where('email', $billingEmail)->first();

        if ($existingUser !== null) {
            $report->addReconciled([
                'wp_order_id' => $record->wpOrderId,
                'email' => $billingEmail,
                'user_id' => $existingUser->id,
                'reason' => 'guest_checkout_linked_by_email',
            ]);

            return $existingUser;
        }

        if ($dryRun) {
            return null;
        }

        $user = User::query()->create([
            'name' => Str::before($billingEmail, '@'),
            'email' => $billingEmail,
            'password' => Str::password(32),
            'role' => UserRole::User,
            'status' => UserStatus::Active,
            'wp_legacy_id' => null,
        ]);

        $report->addReconciled([
            'wp_order_id' => $record->wpOrderId,
            'email' => $billingEmail,
            'user_id' => $user->id,
            'reason' => 'guest_checkout_placeholder_user_created',
        ]);

        return $user;
    }

    public function canSatisfyUserRequirement(WordPressOrderRecord $record): bool
    {
        if ($record->customerWpLegacyId !== null) {
            return User::query()->where('wp_legacy_id', $record->customerWpLegacyId)->exists();
        }

        return WordPressOrderBillingEmailResolver::normalize($record->billingEmail) !== null;
    }
}
