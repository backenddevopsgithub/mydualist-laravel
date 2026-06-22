<?php

namespace App\Services\LegacyImport\Validation;

use App\Models\BillingPurchase;
use App\Models\BlogPost;
use App\Models\CommunityDua;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\DuaSuggestion;
use App\Models\EntitlementGrant;
use App\Models\User;
use App\Services\LegacyImport\Purchases\Support\WordPressHposDetector;
use App\Services\LegacyImport\Purchases\Support\WordPressHposOrderTimestamps;
use App\Services\LegacyImport\Support\LegacyImportTimestamps;
use App\Services\LegacyImport\Support\WordPressValueMapper;
use App\Services\Service;
use App\Support\WordPress\WordPressConnection;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Schema;

class LegacyDateBackfillService extends Service
{
    /**
     * @return array<string, int>
     */
    public function backfill(bool $dryRun = false, ?string $entity = null): array
    {
        $entities = $entity === null || $entity === 'all'
            ? ['users', 'lists', 'submissions', 'purchases', 'community_duas', 'blog_posts']
            : [$entity];

        $counts = [];

        foreach ($entities as $name) {
            $counts[$name] = match ($name) {
                'users' => $this->backfillUsers($dryRun),
                'lists' => $this->backfillLists($dryRun),
                'submissions' => $this->backfillSubmissions($dryRun),
                'purchases' => $this->backfillPurchases($dryRun),
                'community_duas' => $this->backfillCommunityDuas($dryRun),
                'blog_posts' => $this->backfillBlogPosts($dryRun),
                default => 0,
            };
        }

        return $counts;
    }

    private function backfillUsers(bool $dryRun): int
    {
        $connection = WordPressConnection::connection();
        $updated = 0;

        User::query()
            ->whereNotNull('wp_legacy_id')
            ->chunkById(100, function ($users) use ($connection, $dryRun, &$updated): void {
                $wpIds = $users->pluck('wp_legacy_id')->all();
                $registeredDates = $connection->table('users')
                    ->whereIn('ID', $wpIds)
                    ->pluck('user_registered', 'ID');

                foreach ($users as $user) {
                    $registeredAt = WordPressValueMapper::parseDateTime(
                        $registeredDates->get($user->wp_legacy_id),
                    );

                    if ($registeredAt === null) {
                        continue;
                    }

                    if (! $dryRun) {
                        LegacyImportTimestamps::apply($user, $registeredAt);
                    }

                    $updated++;
                }
            });

        return $updated;
    }

    private function backfillLists(bool $dryRun): int
    {
        $connection = WordPressConnection::connection();
        $updated = 0;

        DuaList::withTrashed()
            ->whereNotNull('wp_post_id')
            ->chunkById(100, function ($lists) use ($connection, $dryRun, &$updated): void {
                $wpIds = $lists->pluck('wp_post_id')->all();
                $posts = $connection->table('posts')
                    ->whereIn('ID', $wpIds)
                    ->get(['ID', 'post_date', 'post_modified'])
                    ->keyBy('ID');

                foreach ($lists as $list) {
                    $post = $posts->get($list->wp_post_id);

                    if ($post === null) {
                        continue;
                    }

                    $createdAt = WordPressValueMapper::parseDateTime($post->post_date ?? null);
                    $updatedAt = WordPressValueMapper::parseDateTime($post->post_modified ?? null);

                    if ($createdAt === null) {
                        continue;
                    }

                    if (! $dryRun) {
                        LegacyImportTimestamps::apply($list, $createdAt, $updatedAt);
                    }

                    $updated++;
                }
            });

        return $updated;
    }

    private function backfillSubmissions(bool $dryRun): int
    {
        $connection = WordPressConnection::connection();
        $updated = 0;

        DuaSubmission::withTrashed()
            ->whereNotNull('wp_post_id')
            ->where('wp_post_id', '>', 0)
            ->chunkById(200, function ($submissions) use ($connection, $dryRun, &$updated): void {
                $wpIds = $submissions->pluck('wp_post_id')->all();
                $posts = $connection->table('posts')
                    ->whereIn('ID', $wpIds)
                    ->pluck('post_date', 'ID');

                foreach ($submissions as $submission) {
                    $createdAt = WordPressValueMapper::parseDateTime($posts->get($submission->wp_post_id));

                    if ($createdAt === null) {
                        continue;
                    }

                    if (! $dryRun) {
                        LegacyImportTimestamps::apply($submission, $createdAt);
                    }

                    $updated++;
                }
            });

        DuaSubmission::withTrashed()
            ->whereNotNull('wp_post_id')
            ->where('wp_post_id', '<', 0)
            ->with('duaList:id,start_date,created_at')
            ->chunkById(200, function ($submissions) use ($dryRun, &$updated): void {
                foreach ($submissions as $submission) {
                    $fallback = $submission->duaList?->start_date
                        ?? $submission->duaList?->created_at;

                    if ($fallback === null) {
                        continue;
                    }

                    $createdAt = $fallback instanceof \Carbon\CarbonInterface
                        ? $fallback
                        : WordPressValueMapper::parseDateTime((string) $fallback);

                    if ($createdAt === null) {
                        continue;
                    }

                    if (! $dryRun) {
                        LegacyImportTimestamps::apply($submission, $createdAt);
                    }

                    $updated++;
                }
            });

        return $updated;
    }

    private function backfillPurchases(bool $dryRun): int
    {
        $connection = WordPressConnection::connection();
        $updated = 0;

        BillingPurchase::query()
            ->whereNotNull('wp_order_id')
            ->chunkById(100, function ($purchases) use ($connection, $dryRun, &$updated): void {
                $orderDates = $this->orderDatesById(
                    $connection,
                    $purchases->pluck('wp_order_id')->all(),
                );

                foreach ($purchases as $purchase) {
                    $createdAt = WordPressValueMapper::parseDateTime(
                        $orderDates[$purchase->wp_order_id] ?? null,
                    );

                    if ($createdAt === null) {
                        continue;
                    }

                    if (! $dryRun) {
                        LegacyImportTimestamps::apply($purchase, $createdAt);
                    }

                    $updated++;
                }
            });

        return $updated;
    }

    private function backfillCommunityDuas(bool $dryRun): int
    {
        $connection = WordPressConnection::connection();
        $updated = 0;

        CommunityDua::query()
            ->whereNotNull('wp_post_id')
            ->chunkById(100, function ($duas) use ($connection, $dryRun, &$updated): void {
                $wpIds = $duas->pluck('wp_post_id')->all();
                $posts = $connection->table('posts')
                    ->whereIn('ID', $wpIds)
                    ->pluck('post_date', 'ID');

                foreach ($duas as $dua) {
                    $createdAt = WordPressValueMapper::parseDateTime($posts->get($dua->wp_post_id));

                    if ($createdAt === null) {
                        continue;
                    }

                    if (! $dryRun) {
                        LegacyImportTimestamps::apply($dua, $createdAt);
                    }

                    $updated++;
                }
            });

        return $updated;
    }

    private function backfillBlogPosts(bool $dryRun): int
    {
        $connection = WordPressConnection::connection();
        $updated = 0;

        BlogPost::query()
            ->whereNotNull('wp_post_id')
            ->chunkById(100, function ($posts) use ($connection, $dryRun, &$updated): void {
                $wpIds = $posts->pluck('wp_post_id')->all();
                $wpPosts = $connection->table('posts')
                    ->whereIn('ID', $wpIds)
                    ->pluck('post_date', 'ID');

                foreach ($posts as $post) {
                    $publishedAt = WordPressValueMapper::parseDateTime($wpPosts->get($post->wp_post_id));

                    if ($publishedAt === null) {
                        continue;
                    }

                    if (! $dryRun) {
                        $post->forceFill(['published_at' => $publishedAt])->saveQuietly();
                        LegacyImportTimestamps::apply($post, $publishedAt);
                    }

                    $updated++;
                }
            });

        return $updated;
    }

    /**
     * @param  list<int>  $orderIds
     * @return array<int, mixed>
     */
    private function orderDatesById(Connection $connection, array $orderIds): array
    {
        if ($orderIds === []) {
            return [];
        }

        if (WordPressHposDetector::usesHpos($connection)) {
            $availableColumns = Schema::connection($connection->getName())->getColumnListing('wc_orders');
            $timestampColumns = WordPressHposOrderTimestamps::forConnection($connection);

            return $connection->table('wc_orders')
                ->whereIn('id', $orderIds)
                ->get(array_merge(['id'], $timestampColumns))
                ->mapWithKeys(fn (object $order): array => [
                    (int) $order->id => WordPressHposOrderTimestamps::createdAt($order, $timestampColumns),
                ])
                ->all();
        }

        return $connection->table('posts')
            ->whereIn('ID', $orderIds)
            ->where('post_type', 'shop_order')
            ->pluck('post_date', 'ID')
            ->all();
    }

    /**
     * @return array<string, int>
     */
    public static function liveEntityTotals(): array
    {
        return [
            'users' => User::query()->count(),
            'users_with_wp_legacy_id' => User::query()->whereNotNull('wp_legacy_id')->count(),
            'lists' => DuaList::query()->count(),
            'lists_with_wp_post_id' => DuaList::query()->whereNotNull('wp_post_id')->count(),
            'submissions' => DuaSubmission::query()->count(),
            'submissions_with_wp_post_id' => DuaSubmission::query()->whereNotNull('wp_post_id')->count(),
            'suggestions' => DuaSuggestion::query()->count(),
            'community_duas' => CommunityDua::query()->count(),
            'purchases' => BillingPurchase::query()->count(),
            'purchases_with_wp_order_id' => BillingPurchase::query()->whereNotNull('wp_order_id')->count(),
            'entitlement_grants' => EntitlementGrant::query()->count(),
        ];
    }
}
