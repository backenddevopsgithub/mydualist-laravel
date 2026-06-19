<?php

use App\Enums\EntitlementKey;
use App\Models\BillingPurchase;
use App\Models\DuaList;
use App\Models\EntitlementGrant;
use App\Models\User;
use App\Services\LegacyImport\Purchases\Import\SqlPurchaseImportSource;
use App\Services\LegacyImport\Purchases\Support\WordPressHposDetector;
use App\Services\LegacyImport\Purchases\Support\WordPressHposOrderTimestamps;
use App\Services\LegacyImport\Purchases\Support\WordPressPurchaseOrderMapper;
use App\Support\WordPress\SqlDumpReader;
use Database\Seeders\BillingProductSeeder;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    $this->seed(BillingProductSeeder::class);
});

function writePurchaseImportSql(string $filename, string $body): string
{
    $path = storage_path("app/{$filename}");
    file_put_contents($path, $body);

    return $path;
}

function purchaseImportUsersSql(): string
{
    return <<<'SQL'
INSERT INTO `wp_users` (`ID`, `user_login`, `user_pass`, `user_nicename`, `user_email`, `user_registered`, `display_name`) VALUES
(42, 'creator@example.com', '$P$Bexamplehash', 'creator', 'creator@example.com', '2024-01-15 10:00:00', 'Sara Ali');
INSERT INTO `wp_usermeta` (`umeta_id`, `user_id`, `meta_key`, `meta_value`) VALUES
(1, 42, 'first_name', 'Sara');
SQL;
}

function importLegacyListsForPurchases(): void
{
    Artisan::call('migrate:users', ['--csv' => base_path('tests/Fixtures/legacy-import/users.csv')]);
    Artisan::call('migrate:lists', ['--csv' => base_path('tests/Fixtures/legacy-import/lists.csv')]);
}

test('legacy woocommerce sql import source imports processing shop_order posts', function () {
    importLegacyListsForPurchases();

    $sql = purchaseImportUsersSql().<<<'SQL'

INSERT INTO `wp_posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES
(5101, 42, '2024-02-01 10:00:00', '2024-02-01 10:00:00', '', 'Order 5101', '', 'wc-processing', 'closed', 'closed', '', 'order-5101', '', '', '2024-02-01 10:00:00', '2024-02-01 10:00:00', '', 0, 'https://example.test/?post_type=shop_order&p=5101', 0, 'shop_order', '', 0),
(5102, 42, '2024-02-02 11:00:00', '2024-02-02 11:00:00', '', 'Order 5102', '', 'wc-failed', 'closed', 'closed', '', 'order-5102', '', '', '2024-02-02 11:00:00', '2024-02-02 11:00:00', '', 0, 'https://example.test/?post_type=shop_order&p=5102', 0, 'shop_order', '', 0);
INSERT INTO `wp_postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES
(101, 5101, '_customer_user', '42'),
(102, 5101, '_order_total', '2.00'),
(103, 5101, '_order_currency', 'gbp'),
(104, 5101, '_list_id', '301'),
(105, 5102, '_customer_user', '42'),
(106, 5102, '_order_total', '7.99'),
(107, 5102, '_order_currency', 'gbp');
INSERT INTO `wp_woocommerce_order_items` (`order_item_id`, `order_item_name`, `order_item_type`, `order_id`) VALUES
(1, '25 Dua Requests', 'line_item', 5101),
(2, 'Extra List', 'line_item', 5102);
INSERT INTO `wp_woocommerce_order_itemmeta` (`meta_id`, `order_item_id`, `meta_key`, `meta_value`) VALUES
(201, 1, '_product_id', '728'),
(202, 2, '_product_id', '914');
SQL;

    $path = writePurchaseImportSql('testing-legacy-purchases.sql', $sql);

    Artisan::call('migrate:users', ['--sql' => $path]);
    Artisan::call('migrate:lists', ['--sql' => $path]);
    Artisan::call('migrate:purchases', ['--sql' => $path]);

    expect(BillingPurchase::query()->where('wp_order_id', 5101)->exists())->toBeTrue()
        ->and(BillingPurchase::query()->where('wp_order_id', 5102)->exists())->toBeFalse();

    $purchase = BillingPurchase::query()->where('wp_order_id', 5101)->first();

    expect($purchase->dua_list_id)->toBe(DuaList::query()->where('wp_post_id', 301)->value('id'))
        ->and(EntitlementGrant::query()->where('source_purchase_id', $purchase->id)->value('entitlement_key'))
        ->toBe(EntitlementKey::ListVisibleSubmissionPack);
});

test('hpos sql import source imports processing and completed orders only', function () {
    importLegacyListsForPurchases();

    $sql = purchaseImportUsersSql().<<<'SQL'

INSERT INTO `wp_wc_orders` (`id`, `status`, `currency`, `type`, `tax_amount`, `total_amount`, `customer_id`, `billing_email`, `date_created_gmt`, `date_updated_gmt`) VALUES
(6001, 'wc-processing', 'gbp', 'shop_order', 0.00000000, 2.00000000, 42, 'creator@example.com', '2024-02-01 10:00:00', '2024-02-01 10:00:00'),
(6002, 'wc-completed', 'gbp', 'shop_order', 0.00000000, 7.99000000, 42, 'creator@example.com', '2024-02-02 11:00:00', '2024-02-02 11:00:00'),
(6003, 'wc-failed', 'gbp', 'shop_order', 0.00000000, 7.99000000, 42, 'creator@example.com', '2024-02-03 12:00:00', '2024-02-03 12:00:00'),
(6004, 'wc-cancelled', 'gbp', 'shop_order', 0.00000000, 7.99000000, 42, 'creator@example.com', '2024-02-04 13:00:00', '2024-02-04 13:00:00');
INSERT INTO `wp_wc_orders_meta` (`id`, `order_id`, `meta_key`, `meta_value`) VALUES
(1, 6001, '_list_id', '301');
INSERT INTO `wp_wc_order_product_lookup` (`order_item_id`, `order_id`, `product_id`, `variation_id`, `customer_id`, `date_created`, `product_qty`, `product_net_revenue`, `product_gross_revenue`, `coupon_amount`, `tax_amount`, `shipping_amount`, `shipping_tax_amount`) VALUES
(1, 6001, 728, 0, 42, '2024-02-01 10:00:00', 1, 2.00000000, 2.00000000, 0.00000000, 0.00000000, 0.00000000, 0.00000000),
(2, 6002, 914, 0, 42, '2024-02-02 11:00:00', 1, 7.99000000, 7.99000000, 0.00000000, 0.00000000, 0.00000000, 0.00000000),
(3, 6003, 914, 0, 42, '2024-02-03 12:00:00', 1, 7.99000000, 7.99000000, 0.00000000, 0.00000000, 0.00000000, 0.00000000),
(4, 6004, 914, 0, 42, '2024-02-04 13:00:00', 1, 7.99000000, 7.99000000, 0.00000000, 0.00000000, 0.00000000, 0.00000000);
SQL;

    $path = writePurchaseImportSql('testing-hpos-purchases.sql', $sql);

    $reader = new SqlDumpReader($path);
    expect(WordPressHposDetector::dumpUsesHpos($reader))->toBeTrue();

    Artisan::call('migrate:users', ['--sql' => $path]);
    Artisan::call('migrate:lists', ['--sql' => $path]);

    $exitCode = Artisan::call('migrate:purchases', [
        '--sql' => $path,
        '--dry-run' => true,
        '--report' => storage_path('app/testing-hpos-purchases-dry-run-report.json'),
    ]);

    $report = json_decode(file_get_contents(storage_path('app/testing-hpos-purchases-dry-run-report.json')), true);

    expect($exitCode)->toBe(0)
        ->and($report['counts']['failed'])->toBe(0)
        ->and($report['counts']['imported'])->toBe(2);

    Artisan::call('migrate:purchases', ['--sql' => $path]);

    expect(BillingPurchase::query()->whereIn('wp_order_id', [6001, 6002])->count())->toBe(2)
        ->and(BillingPurchase::query()->whereIn('wp_order_id', [6003, 6004])->exists())->toBeFalse()
        ->and(BillingPurchase::query()->where('wp_order_id', 6001)->value('user_id'))
        ->toBe(User::query()->where('wp_legacy_id', 42)->value('id'));
});

test('mixed sql dumps prefer hpos orders over legacy shop_order posts', function () {
    importLegacyListsForPurchases();

    $sql = purchaseImportUsersSql().<<<'SQL'

INSERT INTO `wp_posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES
(5101, 42, '2024-02-01 10:00:00', '2024-02-01 10:00:00', '', 'Legacy Order', '', 'wc-processing', 'closed', 'closed', '', 'legacy-order', '', '', '2024-02-01 10:00:00', '2024-02-01 10:00:00', '', 0, 'https://example.test/?post_type=shop_order&p=5101', 0, 'shop_order', '', 0);
INSERT INTO `wp_postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES
(101, 5101, '_customer_user', '42'),
(102, 5101, '_order_total', '2.00'),
(103, 5101, '_order_currency', 'gbp'),
(104, 5101, '_list_id', '301');
INSERT INTO `wp_woocommerce_order_items` (`order_item_id`, `order_item_name`, `order_item_type`, `order_id`) VALUES
(1, '25 Dua Requests', 'line_item', 5101);
INSERT INTO `wp_woocommerce_order_itemmeta` (`meta_id`, `order_item_id`, `meta_key`, `meta_value`) VALUES
(201, 1, '_product_id', '728');
INSERT INTO `wp_wc_orders` (`id`, `status`, `currency`, `type`, `tax_amount`, `total_amount`, `customer_id`, `billing_email`, `date_created_gmt`, `date_updated_gmt`) VALUES
(7001, 'wc-processing', 'gbp', 'shop_order', 0.00000000, 7.99000000, 42, 'creator@example.com', '2024-02-05 10:00:00', '2024-02-05 10:00:00');
INSERT INTO `wp_wc_order_product_lookup` (`order_item_id`, `order_id`, `product_id`, `variation_id`, `customer_id`, `date_created`, `product_qty`, `product_net_revenue`, `product_gross_revenue`, `coupon_amount`, `tax_amount`, `shipping_amount`, `shipping_tax_amount`) VALUES
(10, 7001, 914, 0, 42, '2024-02-05 10:00:00', 1, 7.99000000, 7.99000000, 0.00000000, 0.00000000, 0.00000000, 0.00000000);
SQL;

    $path = writePurchaseImportSql('testing-mixed-purchases.sql', $sql);

    Artisan::call('migrate:users', ['--sql' => $path]);
    Artisan::call('migrate:lists', ['--sql' => $path]);
    Artisan::call('migrate:purchases', ['--sql' => $path]);

    expect(BillingPurchase::query()->where('wp_order_id', 7001)->exists())->toBeTrue()
        ->and(BillingPurchase::query()->where('wp_order_id', 5101)->exists())->toBeFalse();
});

test('hpos purchase import is idempotent on repeated runs', function () {
    importLegacyListsForPurchases();

    $sql = purchaseImportUsersSql().<<<'SQL'

INSERT INTO `wp_wc_orders` (`id`, `status`, `currency`, `type`, `tax_amount`, `total_amount`, `customer_id`, `billing_email`, `date_created_gmt`, `date_updated_gmt`) VALUES
(6001, 'wc-processing', 'gbp', 'shop_order', 0.00000000, 2.00000000, 42, 'creator@example.com', '2024-02-01 10:00:00', '2024-02-01 10:00:00');
INSERT INTO `wp_wc_orders_meta` (`id`, `order_id`, `meta_key`, `meta_value`) VALUES
(1, 6001, '_list_id', '301');
INSERT INTO `wp_wc_order_product_lookup` (`order_item_id`, `order_id`, `product_id`, `variation_id`, `customer_id`, `date_created`, `product_qty`, `product_net_revenue`, `product_gross_revenue`, `coupon_amount`, `tax_amount`, `shipping_amount`, `shipping_tax_amount`) VALUES
(1, 6001, 728, 0, 42, '2024-02-01 10:00:00', 1, 2.00000000, 2.00000000, 0.00000000, 0.00000000, 0.00000000, 0.00000000);
SQL;

    $path = writePurchaseImportSql('testing-hpos-purchases-idempotent.sql', $sql);

    Artisan::call('migrate:users', ['--sql' => $path]);
    Artisan::call('migrate:lists', ['--sql' => $path]);
    Artisan::call('migrate:purchases', ['--sql' => $path]);
    Artisan::call('migrate:purchases', [
        '--sql' => $path,
        '--report' => storage_path('app/testing-hpos-purchases-idempotent-report.json'),
    ]);

    $report = json_decode(file_get_contents(storage_path('app/testing-hpos-purchases-idempotent-report.json')), true);

    expect(BillingPurchase::query()->where('wp_order_id', 6001)->count())->toBe(1)
        ->and($report['counts']['imported'])->toBe(0)
        ->and($report['counts']['updated'])->toBe(1)
        ->and($report['counts']['failed'])->toBe(0);
});

test('sql purchase import source exposes expected record counts for hpos fixtures', function () {
    $sql = purchaseImportUsersSql().<<<'SQL'

INSERT INTO `wp_wc_orders` (`id`, `status`, `currency`, `type`, `tax_amount`, `total_amount`, `customer_id`, `billing_email`, `date_created_gmt`, `date_updated_gmt`) VALUES
(6001, 'wc-processing', 'gbp', 'shop_order', 0.00000000, 2.00000000, 42, 'creator@example.com', '2024-02-01 10:00:00', '2024-02-01 10:00:00'),
(6002, 'wc-completed', 'gbp', 'shop_order', 0.00000000, 7.99000000, 42, 'creator@example.com', '2024-02-02 11:00:00', '2024-02-02 11:00:00'),
(6003, 'wc-failed', 'gbp', 'shop_order', 0.00000000, 7.99000000, 42, 'creator@example.com', '2024-02-03 12:00:00', '2024-02-03 12:00:00');
INSERT INTO `wp_wc_order_product_lookup` (`order_item_id`, `order_id`, `product_id`, `variation_id`, `customer_id`, `date_created`, `product_qty`, `product_net_revenue`, `product_gross_revenue`, `coupon_amount`, `tax_amount`, `shipping_amount`, `shipping_tax_amount`) VALUES
(1, 6001, 728, 0, 42, '2024-02-01 10:00:00', 1, 2.00000000, 2.00000000, 0.00000000, 0.00000000, 0.00000000, 0.00000000),
(2, 6002, 914, 0, 42, '2024-02-02 11:00:00', 1, 7.99000000, 7.99000000, 0.00000000, 0.00000000, 0.00000000, 0.00000000),
(3, 6003, 914, 0, 42, '2024-02-03 12:00:00', 1, 7.99000000, 7.99000000, 0.00000000, 0.00000000, 0.00000000, 0.00000000);
SQL;

    $path = writePurchaseImportSql('testing-hpos-purchase-source-counts.sql', $sql);
    $source = new SqlPurchaseImportSource($path);

    expect(iterator_count($source->records()))->toBe(2);
});

test('wordpress purchase order mapper ignores unsupported products', function () {
    $record = WordPressPurchaseOrderMapper::map(
        orderId: 1,
        productId: 999,
        customerId: 42,
        listId: 301,
        total: 2.0,
        currency: 'gbp',
        createdAt: '2024-02-01 10:00:00',
    );

    expect($record)->toBeNull();
});

test('hpos order timestamps resolve gmt-only wc_orders columns', function () {
    $columns = [
        'id',
        'status',
        'currency',
        'type',
        'total_amount',
        'customer_id',
        'date_created_gmt',
        'date_updated_gmt',
    ];

    $timestampColumns = WordPressHposOrderTimestamps::columns($columns);

    expect($timestampColumns)->toBe([
        'created' => 'date_created_gmt',
        'updated' => 'date_updated_gmt',
    ])
        ->and(WordPressHposOrderTimestamps::selectColumns($columns))
        ->toBe(['id', 'status', 'currency', 'total_amount', 'customer_id', 'date_created_gmt'])
        ->and(WordPressHposOrderTimestamps::createdAt([
            'date_created_gmt' => '2024-02-01 10:00:00',
        ], $timestampColumns))
        ->toBe('2024-02-01 10:00:00');
});

test('hpos order timestamps prefer local columns when present', function () {
    $columns = [
        'id',
        'status',
        'currency',
        'type',
        'total_amount',
        'customer_id',
        'date_created',
        'date_updated',
        'date_created_gmt',
        'date_updated_gmt',
    ];

    $timestampColumns = WordPressHposOrderTimestamps::columns($columns);

    expect($timestampColumns)->toBe([
        'created' => 'date_created',
        'updated' => 'date_updated',
    ])
        ->and(WordPressHposOrderTimestamps::selectColumns($columns))
        ->toBe(['id', 'status', 'currency', 'total_amount', 'customer_id', 'date_created'])
        ->and(WordPressHposOrderTimestamps::createdAt([
            'date_created' => '2024-03-01 15:00:00',
            'date_created_gmt' => '2024-03-01 10:00:00',
        ], $timestampColumns))
        ->toBe('2024-03-01 15:00:00');
});

test('hpos sql import uses gmt timestamps when local columns are absent', function () {
    $sql = purchaseImportUsersSql().<<<'SQL'

INSERT INTO `wp_wc_orders` (`id`, `status`, `currency`, `type`, `tax_amount`, `total_amount`, `customer_id`, `billing_email`, `date_created_gmt`, `date_updated_gmt`) VALUES
(8101, 'wc-processing', 'gbp', 'shop_order', 0.00000000, 2.00000000, 42, 'creator@example.com', '2024-02-10 09:30:00', '2024-02-10 09:31:00');
INSERT INTO `wp_wc_orders_meta` (`id`, `order_id`, `meta_key`, `meta_value`) VALUES
(1, 8101, '_list_id', '301');
INSERT INTO `wp_wc_order_product_lookup` (`order_item_id`, `order_id`, `product_id`, `variation_id`, `customer_id`, `date_created`, `product_qty`, `product_net_revenue`, `product_gross_revenue`, `coupon_amount`, `tax_amount`, `shipping_amount`, `shipping_tax_amount`) VALUES
(1, 8101, 728, 0, 42, '2024-02-10 09:30:00', 1, 2.00000000, 2.00000000, 0.00000000, 0.00000000, 0.00000000, 0.00000000);
SQL;

    $path = writePurchaseImportSql('testing-hpos-gmt-only-timestamps.sql', $sql);
    $source = new SqlPurchaseImportSource($path);
    $record = iterator_to_array($source->records())[8101];

    expect($record->createdAt?->format('Y-m-d H:i:s'))->toBe('2024-02-10 09:30:00');
});

test('hpos sql import prefers local timestamps when both variants exist', function () {
    $sql = purchaseImportUsersSql().<<<'SQL'

INSERT INTO `wp_wc_orders` (`id`, `status`, `currency`, `type`, `tax_amount`, `total_amount`, `customer_id`, `billing_email`, `date_created`, `date_updated`, `date_created_gmt`, `date_updated_gmt`) VALUES
(8201, 'wc-processing', 'gbp', 'shop_order', 0.00000000, 2.00000000, 42, 'creator@example.com', '2024-03-01 15:00:00', '2024-03-01 16:00:00', '2024-03-01 10:00:00', '2024-03-01 11:00:00');
INSERT INTO `wp_wc_orders_meta` (`id`, `order_id`, `meta_key`, `meta_value`) VALUES
(1, 8201, '_list_id', '301');
INSERT INTO `wp_wc_order_product_lookup` (`order_item_id`, `order_id`, `product_id`, `variation_id`, `customer_id`, `date_created`, `product_qty`, `product_net_revenue`, `product_gross_revenue`, `coupon_amount`, `tax_amount`, `shipping_amount`, `shipping_tax_amount`) VALUES
(1, 8201, 728, 0, 42, '2024-03-01 15:00:00', 1, 2.00000000, 2.00000000, 0.00000000, 0.00000000, 0.00000000, 0.00000000);
SQL;

    $path = writePurchaseImportSql('testing-hpos-local-and-gmt-timestamps.sql', $sql);
    $source = new SqlPurchaseImportSource($path);
    $record = iterator_to_array($source->records())[8201];

    expect($record->createdAt?->format('Y-m-d H:i:s'))->toBe('2024-03-01 15:00:00');
});

test('sql dump reader tracks hpos timestamp columns from wc_orders inserts', function () {
    $sql = purchaseImportUsersSql().<<<'SQL'

INSERT INTO `wp_wc_orders` (`id`, `status`, `currency`, `type`, `tax_amount`, `total_amount`, `customer_id`, `billing_email`, `date_created_gmt`, `date_updated_gmt`) VALUES
(8101, 'wc-processing', 'gbp', 'shop_order', 0.00000000, 2.00000000, 42, 'creator@example.com', '2024-02-10 09:30:00', '2024-02-10 09:31:00');
SQL;

    $path = writePurchaseImportSql('testing-hpos-reader-gmt-columns.sql', $sql);
    $reader = new SqlDumpReader($path);

    expect($reader->wcOrdersColumns())->toContain('date_created_gmt')
        ->and($reader->wcOrdersColumns())->not->toContain('date_created')
        ->and($reader->wcOrdersById()[8101]['date_created_gmt'] ?? null)->toBe('2024-02-10 09:30:00');
});

test('hpos guest checkout links purchase to existing user by billing email', function () {
    importLegacyListsForPurchases();

    $sql = purchaseImportUsersSql().<<<'SQL'

INSERT INTO `wp_wc_orders` (`id`, `status`, `currency`, `type`, `tax_amount`, `total_amount`, `customer_id`, `billing_email`, `date_created_gmt`, `date_updated_gmt`) VALUES
(9001, 'wc-processing', 'gbp', 'shop_order', 0.00000000, 2.00000000, 0, 'creator@example.com', '2024-02-01 10:00:00', '2024-02-01 10:00:00');
INSERT INTO `wp_wc_orders_meta` (`id`, `order_id`, `meta_key`, `meta_value`) VALUES
(1, 9001, '_list_id', '301');
INSERT INTO `wp_wc_order_product_lookup` (`order_item_id`, `order_id`, `product_id`, `variation_id`, `customer_id`, `date_created`, `product_qty`, `product_net_revenue`, `product_gross_revenue`, `coupon_amount`, `tax_amount`, `shipping_amount`, `shipping_tax_amount`) VALUES
(1, 9001, 728, 0, 0, '2024-02-01 10:00:00', 1, 2.00000000, 2.00000000, 0.00000000, 0.00000000, 0.00000000, 0.00000000);
SQL;

    $path = writePurchaseImportSql('testing-hpos-guest-existing-email.sql', $sql);

    Artisan::call('migrate:users', ['--sql' => $path]);
    Artisan::call('migrate:lists', ['--sql' => $path]);
    Artisan::call('migrate:purchases', ['--sql' => $path]);

    $purchase = BillingPurchase::query()->where('wp_order_id', 9001)->first();

    expect($purchase)->not->toBeNull()
        ->and($purchase->user_id)->toBe(User::query()->where('email', 'creator@example.com')->value('id'))
        ->and($purchase->metadata['guest_checkout'] ?? false)->toBeTrue()
        ->and($purchase->metadata['billing_email'] ?? null)->toBe('creator@example.com');
});

test('hpos guest checkout creates placeholder user for unknown billing email', function () {
    importLegacyListsForPurchases();

    $sql = purchaseImportUsersSql().<<<'SQL'

INSERT INTO `wp_wc_orders` (`id`, `status`, `currency`, `type`, `tax_amount`, `total_amount`, `customer_id`, `billing_email`, `date_created_gmt`, `date_updated_gmt`) VALUES
(9002, 'wc-completed', 'gbp', 'shop_order', 0.00000000, 7.99000000, 0, 'newguest@example.com', '2024-02-02 11:00:00', '2024-02-02 11:00:00');
INSERT INTO `wp_wc_order_product_lookup` (`order_item_id`, `order_id`, `product_id`, `variation_id`, `customer_id`, `date_created`, `product_qty`, `product_net_revenue`, `product_gross_revenue`, `coupon_amount`, `tax_amount`, `shipping_amount`, `shipping_tax_amount`) VALUES
(1, 9002, 914, 0, 0, '2024-02-02 11:00:00', 1, 7.99000000, 7.99000000, 0.00000000, 0.00000000, 0.00000000, 0.00000000);
SQL;

    $path = writePurchaseImportSql('testing-hpos-guest-placeholder-user.sql', $sql);

    Artisan::call('migrate:users', ['--sql' => $path]);
    Artisan::call('migrate:lists', ['--sql' => $path]);
    Artisan::call('migrate:purchases', ['--sql' => $path]);

    $placeholder = User::query()->where('email', 'newguest@example.com')->first();
    $purchase = BillingPurchase::query()->where('wp_order_id', 9002)->first();

    expect($placeholder)->not->toBeNull()
        ->and($placeholder->wp_legacy_id)->toBeNull()
        ->and($purchase)->not->toBeNull()
        ->and($purchase->user_id)->toBe($placeholder->id);

    Artisan::call('migrate:purchases', ['--sql' => $path]);

    expect(User::query()->where('email', 'newguest@example.com')->count())->toBe(1)
        ->and(BillingPurchase::query()->where('wp_order_id', 9002)->count())->toBe(1);
});

test('hpos guest checkout dry run succeeds when billing email is present', function () {
    importLegacyListsForPurchases();

    $sql = purchaseImportUsersSql().<<<'SQL'

INSERT INTO `wp_wc_orders` (`id`, `status`, `currency`, `type`, `tax_amount`, `total_amount`, `customer_id`, `billing_email`, `date_created_gmt`, `date_updated_gmt`) VALUES
(9003, 'wc-processing', 'gbp', 'shop_order', 0.00000000, 2.00000000, 0, 'unknown-guest@example.com', '2024-02-03 12:00:00', '2024-02-03 12:00:00');
INSERT INTO `wp_wc_orders_meta` (`id`, `order_id`, `meta_key`, `meta_value`) VALUES
(1, 9003, '_list_id', '301');
INSERT INTO `wp_wc_order_product_lookup` (`order_item_id`, `order_id`, `product_id`, `variation_id`, `customer_id`, `date_created`, `product_qty`, `product_net_revenue`, `product_gross_revenue`, `coupon_amount`, `tax_amount`, `shipping_amount`, `shipping_tax_amount`) VALUES
(1, 9003, 728, 0, 0, '2024-02-03 12:00:00', 1, 2.00000000, 2.00000000, 0.00000000, 0.00000000, 0.00000000, 0.00000000);
SQL;

    $path = writePurchaseImportSql('testing-hpos-guest-dry-run.sql', $sql);

    Artisan::call('migrate:users', ['--sql' => $path]);
    Artisan::call('migrate:lists', ['--sql' => $path]);

    $exitCode = Artisan::call('migrate:purchases', [
        '--sql' => $path,
        '--dry-run' => true,
        '--report' => storage_path('app/testing-hpos-guest-dry-run-report.json'),
    ]);

    $report = json_decode(file_get_contents(storage_path('app/testing-hpos-guest-dry-run-report.json')), true);

    expect($exitCode)->toBe(0)
        ->and($report['counts']['failed'])->toBe(0)
        ->and($report['counts']['imported'])->toBe(1)
        ->and(User::query()->where('email', 'unknown-guest@example.com')->exists())->toBeFalse();
});

test('legacy guest checkout resolves billing email from postmeta', function () {
    importLegacyListsForPurchases();

    $sql = purchaseImportUsersSql().<<<'SQL'

INSERT INTO `wp_posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES
(9101, 0, '2024-02-04 10:00:00', '2024-02-04 10:00:00', '', 'Guest Order', '', 'wc-processing', 'closed', 'closed', '', 'guest-order', '', '', '2024-02-04 10:00:00', '2024-02-04 10:00:00', '', 0, 'https://example.test/?post_type=shop_order&p=9101', 0, 'shop_order', '', 0);
INSERT INTO `wp_postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES
(101, 9101, '_customer_user', '0'),
(102, 9101, '_billing_email', 'creator@example.com'),
(103, 9101, '_order_total', '2.00'),
(104, 9101, '_order_currency', 'gbp'),
(105, 9101, '_list_id', '301');
INSERT INTO `wp_woocommerce_order_items` (`order_item_id`, `order_item_name`, `order_item_type`, `order_id`) VALUES
(1, '25 Dua Requests', 'line_item', 9101);
INSERT INTO `wp_woocommerce_order_itemmeta` (`meta_id`, `order_item_id`, `meta_key`, `meta_value`) VALUES
(201, 1, '_product_id', '728');
SQL;

    $path = writePurchaseImportSql('testing-legacy-guest-checkout.sql', $sql);

    Artisan::call('migrate:users', ['--sql' => $path]);
    Artisan::call('migrate:lists', ['--sql' => $path]);
    Artisan::call('migrate:purchases', ['--sql' => $path]);

    $purchase = BillingPurchase::query()->where('wp_order_id', 9101)->first();

    expect($purchase)->not->toBeNull()
        ->and($purchase->user_id)->toBe(User::query()->where('email', 'creator@example.com')->value('id'));
});

test('hpos guest checkout resolves billing email from wc_order_addresses', function () {
    importLegacyListsForPurchases();

    $sql = purchaseImportUsersSql().<<<'SQL'

INSERT INTO `wp_wc_orders` (`id`, `status`, `currency`, `type`, `tax_amount`, `total_amount`, `customer_id`, `billing_email`, `date_created_gmt`, `date_updated_gmt`) VALUES
(9004, 'wc-processing', 'gbp', 'shop_order', 0.00000000, 2.00000000, 0, '', '2024-02-05 10:00:00', '2024-02-05 10:00:00');
INSERT INTO `wp_wc_orders_meta` (`id`, `order_id`, `meta_key`, `meta_value`) VALUES
(1, 9004, '_list_id', '301');
INSERT INTO `wp_wc_order_addresses` (`id`, `order_id`, `address_type`, `email`) VALUES
(1, 9004, 'billing', 'creator@example.com');
INSERT INTO `wp_wc_order_product_lookup` (`order_item_id`, `order_id`, `product_id`, `variation_id`, `customer_id`, `date_created`, `product_qty`, `product_net_revenue`, `product_gross_revenue`, `coupon_amount`, `tax_amount`, `shipping_amount`, `shipping_tax_amount`) VALUES
(1, 9004, 728, 0, 0, '2024-02-05 10:00:00', 1, 2.00000000, 2.00000000, 0.00000000, 0.00000000, 0.00000000, 0.00000000);
SQL;

    $path = writePurchaseImportSql('testing-hpos-guest-address-email.sql', $sql);

    $reader = new SqlDumpReader($path);
    expect($reader->wcBillingEmailForOrder(9004))->toBe('creator@example.com');

    Artisan::call('migrate:users', ['--sql' => $path]);
    Artisan::call('migrate:lists', ['--sql' => $path]);
    Artisan::call('migrate:purchases', ['--sql' => $path]);

    $purchase = BillingPurchase::query()->where('wp_order_id', 9004)->first();

    expect($purchase)->not->toBeNull()
        ->and($purchase->user_id)->toBe(User::query()->where('email', 'creator@example.com')->value('id'));
});

test('mixed hpos dump imports registered and guest orders', function () {
    importLegacyListsForPurchases();

    $sql = purchaseImportUsersSql().<<<'SQL'

INSERT INTO `wp_wc_orders` (`id`, `status`, `currency`, `type`, `tax_amount`, `total_amount`, `customer_id`, `billing_email`, `date_created_gmt`, `date_updated_gmt`) VALUES
(9010, 'wc-processing', 'gbp', 'shop_order', 0.00000000, 2.00000000, 42, 'creator@example.com', '2024-02-06 10:00:00', '2024-02-06 10:00:00'),
(9011, 'wc-completed', 'gbp', 'shop_order', 0.00000000, 7.99000000, 0, 'creator@example.com', '2024-02-06 11:00:00', '2024-02-06 11:00:00');
INSERT INTO `wp_wc_orders_meta` (`id`, `order_id`, `meta_key`, `meta_value`) VALUES
(1, 9010, '_list_id', '301');
INSERT INTO `wp_wc_order_product_lookup` (`order_item_id`, `order_id`, `product_id`, `variation_id`, `customer_id`, `date_created`, `product_qty`, `product_net_revenue`, `product_gross_revenue`, `coupon_amount`, `tax_amount`, `shipping_amount`, `shipping_tax_amount`) VALUES
(1, 9010, 728, 0, 42, '2024-02-06 10:00:00', 1, 2.00000000, 2.00000000, 0.00000000, 0.00000000, 0.00000000, 0.00000000),
(2, 9011, 914, 0, 0, '2024-02-06 11:00:00', 1, 7.99000000, 7.99000000, 0.00000000, 0.00000000, 0.00000000, 0.00000000);
SQL;

    $path = writePurchaseImportSql('testing-hpos-mixed-guest-registered.sql', $sql);

    Artisan::call('migrate:users', ['--sql' => $path]);
    Artisan::call('migrate:lists', ['--sql' => $path]);
    Artisan::call('migrate:purchases', ['--sql' => $path]);

    $registeredUserId = User::query()->where('wp_legacy_id', 42)->value('id');

    expect(BillingPurchase::query()->whereIn('wp_order_id', [9010, 9011])->count())->toBe(2)
        ->and(BillingPurchase::query()->where('wp_order_id', 9010)->value('user_id'))->toBe($registeredUserId)
        ->and(BillingPurchase::query()->where('wp_order_id', 9011)->value('user_id'))->toBe($registeredUserId);
});
