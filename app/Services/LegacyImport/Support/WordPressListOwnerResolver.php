<?php

namespace App\Services\LegacyImport\Support;

use Illuminate\Support\Facades\Log;

class WordPressListOwnerResolver
{
    /**
     * Resolve the WordPress user ID that owns a dua_list post.
     *
     * Production uses posts.post_author; postmeta.user is a stale creation snapshot.
     *
     * @param  array<string, string>  $meta
     * @return array{owner_wp_id: ?int, post_author: int, meta_user: int}
     */
    public static function resolve(int $wpPostId, int $postAuthor, array $meta): array
    {
        $metaUser = (int) ($meta['user'] ?? 0);

        $ownerWpId = $postAuthor;

        if ($ownerWpId <= 0) {
            $ownerWpId = $metaUser;
        }

        if ($postAuthor !== $metaUser) {
            Log::warning('WordPress list owner field mismatch', [
                'wp_post_id' => $wpPostId,
                'post_author' => $postAuthor,
                'meta_user' => $metaUser,
            ]);
        }

        return [
            'owner_wp_id' => $ownerWpId > 0 ? $ownerWpId : null,
            'post_author' => $postAuthor,
            'meta_user' => $metaUser,
        ];
    }
}
