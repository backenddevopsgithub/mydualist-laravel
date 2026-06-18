<?php

namespace App\Enums;

enum EntitlementProductType: string
{
    case ExtraList = 'extra_list';
    case RequestPack25 = 'request_pack_25';
    case UnlimitedOneList = 'unlimited_one_list';
    case UnlimitedForever = 'unlimited_forever';

    public function label(): string
    {
        return match ($this) {
            self::ExtraList => 'Extra list',
            self::RequestPack25 => '25-pack',
            self::UnlimitedOneList => 'Unlimited one list',
            self::UnlimitedForever => 'Unlimited forever',
        };
    }

    public function entitlementKey(): EntitlementKey
    {
        return match ($this) {
            self::ExtraList => EntitlementKey::UserExtraListSlot,
            self::RequestPack25 => EntitlementKey::ListVisibleSubmissionPack,
            self::UnlimitedOneList => EntitlementKey::ListUnlimitedOverride,
            self::UnlimitedForever => EntitlementKey::UserUnlimitedForever,
        };
    }

    public function requiresList(): bool
    {
        return match ($this) {
            self::RequestPack25, self::UnlimitedOneList => true,
            default => false,
        };
    }

    public function isStackable(): bool
    {
        return match ($this) {
            self::ExtraList, self::RequestPack25 => true,
            default => false,
        };
    }

    public function defaultQuantity(): int
    {
        return match ($this) {
            self::RequestPack25 => (int) config('billing.request_pack_size'),
            default => 1,
        };
    }

    public function billingProductCode(): BillingProductCode
    {
        return match ($this) {
            self::ExtraList => BillingProductCode::AdditionalList,
            self::RequestPack25 => BillingProductCode::RequestPack25,
            self::UnlimitedOneList => BillingProductCode::UnlimitedOneList,
            self::UnlimitedForever => BillingProductCode::UnlimitedForever,
        };
    }

    public static function fromEntitlementKey(EntitlementKey $key): ?self
    {
        return match ($key) {
            EntitlementKey::UserExtraListSlot => self::ExtraList,
            EntitlementKey::ListVisibleSubmissionPack => self::RequestPack25,
            EntitlementKey::ListUnlimitedOverride => self::UnlimitedOneList,
            EntitlementKey::UserUnlimitedForever => self::UnlimitedForever,
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type): array => [$type->value => $type->label()])
            ->all();
    }
}
