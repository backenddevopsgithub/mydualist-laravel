<?php

namespace App\Domains\Billing\Support;

use App\Enums\BillingProductCode;
use Illuminate\Validation\ValidationException;

class BillingProductMapper
{
    /**
     * @var array<string, BillingProductCode>
     */
    private const PLAN_TO_PRODUCT = [
        'additional_list' => BillingProductCode::AdditionalList,
        'unlimited_one_list' => BillingProductCode::UnlimitedOneList,
        'unlimited_forever' => BillingProductCode::UnlimitedForever,
        'request_pack_25' => BillingProductCode::RequestPack25,
        'community_dua_paid' => BillingProductCode::CommunityDuaPaid,
    ];

    public static function productCodeFromPlan(string $plan): ?BillingProductCode
    {
        return self::PLAN_TO_PRODUCT[$plan] ?? null;
    }

    public static function productCodeFromInput(string $value): BillingProductCode
    {
        $fromPlan = self::productCodeFromPlan($value);

        if ($fromPlan !== null) {
            return $fromPlan;
        }

        $fromCode = BillingProductCode::tryFrom(strtoupper($value));

        if ($fromCode !== null) {
            return $fromCode;
        }

        throw ValidationException::withMessages([
            'product_code' => ['The selected product is invalid.'],
        ]);
    }
}
