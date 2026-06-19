<?php

namespace App\Services\LegacyImport\Purchases\Support;

class WordPressOrderBillingEmailResolver
{
    public static function normalize(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }

        $normalized = strtolower(trim($email));

        return $normalized !== '' ? $normalized : null;
    }

    /**
     * @param  array<string, string|null>  $meta
     */
    public static function fromLegacyMeta(array $meta): ?string
    {
        return self::normalize($meta['_billing_email'] ?? null);
    }

    /**
     * @param  array<string, string|null>  $order
     * @param  array<string, string|null>  $meta
     */
    public static function fromHposOrder(array $order, array $meta = [], ?string $addressEmail = null): ?string
    {
        return self::normalize($order['billing_email'] ?? null)
            ?? self::normalize($addressEmail)
            ?? self::normalize($meta['_billing_email'] ?? null);
    }
}
