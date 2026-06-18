<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaLibraryItem extends Model implements HasMedia
{
    use InteractsWithMedia;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'uploaded_by',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('default')
            ->useDisk(config('media-library.disk_name', 'media'));
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(200)
            ->height(200)
            ->sharpen(10);
    }

    public function signedPreviewUrl(?string $conversion = null): ?string
    {
        $media = $this->getFirstMedia('default');

        if ($media === null) {
            return null;
        }

        return URL::temporarySignedRoute(
            'filament.admin.media.preview',
            now()->addMinutes(30),
            [
                'media' => $media->id,
                'conversion' => $conversion ?? '',
            ],
        );
    }
}
