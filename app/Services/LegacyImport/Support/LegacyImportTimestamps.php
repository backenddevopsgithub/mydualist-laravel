<?php

namespace App\Services\LegacyImport\Support;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

class LegacyImportTimestamps
{
    /**
     * Persist WordPress timestamps that are excluded from model $fillable.
     */
    public static function apply(
        Model $model,
        ?CarbonInterface $createdAt,
        ?CarbonInterface $updatedAt = null,
    ): void {
        if ($createdAt === null && $updatedAt === null) {
            return;
        }

        $attributes = [];

        if ($createdAt !== null) {
            $attributes['created_at'] = $createdAt;
        }

        if ($updatedAt !== null) {
            $attributes['updated_at'] = $updatedAt;
        } elseif ($createdAt !== null) {
            $attributes['updated_at'] = $createdAt;
        }

        if ($attributes === []) {
            return;
        }

        $model->forceFill($attributes)->saveQuietly();
    }
}
