<?php

namespace App\Models;

use App\Enums\AdminExportStatus;
use App\Enums\AdminExportType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class AdminExport extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'status',
        'filters',
        'file_path',
        'file_name',
        'row_count',
        'error_message',
        'completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AdminExportType::class,
            'status' => AdminExportStatus::class,
            'filters' => 'array',
            'completed_at' => 'datetime',
            'row_count' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isReady(): bool
    {
        return $this->status === AdminExportStatus::Completed
            && $this->file_path !== null
            && Storage::disk('local')->exists($this->file_path);
    }

    public function downloadUrl(): ?string
    {
        if (! $this->isReady()) {
            return null;
        }

        $ttlDays = (int) config('mydualist.admin_exports.download_url_ttl_days', 7);

        return URL::temporarySignedRoute(
            'filament.admin.exports.download',
            now()->addDays($ttlDays),
            ['export' => $this->id],
        );
    }
}
