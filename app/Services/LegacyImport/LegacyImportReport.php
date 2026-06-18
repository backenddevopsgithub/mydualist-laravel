<?php

namespace App\Services\LegacyImport;

class LegacyImportReport
{
    public function __construct(
        private readonly string $entity,
    ) {}

    /**
     * @var list<array<string, mixed>>
     */
    public array $imported = [];

    /**
     * @var list<array<string, mixed>>
     */
    public array $updated = [];

    /**
     * @var list<array<string, mixed>>
     */
    public array $failed = [];

    /**
     * @var list<array<string, mixed>>
     */
    public array $skipped = [];

    /**
     * @var list<array<string, mixed>>
     */
    public array $missingImages = [];

    /**
     * @var array<string, mixed>
     */
    public array $reconciliation = [];

    /**
     * @var array<string, mixed>
     */
    public array $validation = [];

    /**
     * @param  array<string, mixed>  $summary
     */
    public function addImported(array $summary): void
    {
        $this->imported[] = $summary;
    }

    /**
     * @param  array<string, mixed>  $summary
     */
    public function addUpdated(array $summary): void
    {
        $this->updated[] = $summary;
    }

    public function addFailed(?array $summary, string $reason): void
    {
        $this->failed[] = array_merge($summary ?? [], ['reason' => $reason]);
    }

    /**
     * @param  array<string, mixed>  $summary
     */
    public function addSkipped(array $summary, string $reason): void
    {
        $this->skipped[] = array_merge($summary, ['reason' => $reason]);
    }

    /**
     * @param  array<string, mixed>  $summary
     */
    public function addMissingImage(array $summary, string $url): void
    {
        $this->missingImages[] = array_merge($summary, ['url' => $url]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'entity' => $this->entity,
            'generated_at' => now()->toIso8601String(),
            'counts' => [
                'imported' => count($this->imported),
                'updated' => count($this->updated),
                'failed' => count($this->failed),
                'skipped' => count($this->skipped),
                'missing_images' => count($this->missingImages),
            ],
            'imported' => $this->imported,
            'updated' => $this->updated,
            'failed' => $this->failed,
            'skipped' => $this->skipped,
            'missing_images' => $this->missingImages,
            'reconciliation' => $this->reconciliation !== [] ? $this->reconciliation : null,
            'validation' => $this->validation !== [] ? $this->validation : null,
        ];
    }
}
