<?php

namespace App\Domains\Blog\Services;

use App\Models\AppSetting;
use App\Services\Service;
use Illuminate\Support\Facades\Storage;

class SupportOurCauseSettings extends Service
{
    public const KEY = 'support_our_cause';

    /**
     * @return array{
     *     enabled: bool,
     *     heading: string,
     *     description: string,
     *     primary_button_text: string,
     *     primary_button_url: string,
     *     secondary_button_text: string|null,
     *     secondary_button_url: string|null,
     *     image_path: string|null
     * }
     */
    public function get(): array
    {
        $stored = AppSetting::getValue(self::KEY);
        $defaults = $this->defaults();

        if (! is_array($stored)) {
            return $defaults;
        }

        return [
            'enabled' => (bool) ($stored['enabled'] ?? $defaults['enabled']),
            'heading' => (string) ($stored['heading'] ?? $defaults['heading']),
            'description' => (string) ($stored['description'] ?? $defaults['description']),
            'primary_button_text' => (string) ($stored['primary_button_text'] ?? $defaults['primary_button_text']),
            'primary_button_url' => (string) ($stored['primary_button_url'] ?? $defaults['primary_button_url']),
            'secondary_button_text' => filled($stored['secondary_button_text'] ?? null)
                ? (string) $stored['secondary_button_text']
                : null,
            'secondary_button_url' => filled($stored['secondary_button_url'] ?? null)
                ? (string) $stored['secondary_button_url']
                : null,
            'image_path' => filled($stored['image_path'] ?? null)
                ? (string) $stored['image_path']
                : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(array $data): void
    {
        AppSetting::putValue(self::KEY, [
            'enabled' => (bool) ($data['enabled'] ?? false),
            'heading' => (string) ($data['heading'] ?? ''),
            'description' => (string) ($data['description'] ?? ''),
            'primary_button_text' => (string) ($data['primary_button_text'] ?? ''),
            'primary_button_url' => (string) ($data['primary_button_url'] ?? ''),
            'secondary_button_text' => filled($data['secondary_button_text'] ?? null)
                ? (string) $data['secondary_button_text']
                : null,
            'secondary_button_url' => filled($data['secondary_button_url'] ?? null)
                ? (string) $data['secondary_button_url']
                : null,
            'image_path' => filled($data['image_path'] ?? null)
                ? (string) $data['image_path']
                : null,
        ]);
    }

    public function imageUrl(?string $path = null): ?string
    {
        $path ??= $this->get()['image_path'];

        if (! filled($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        return null;
    }

    /**
     * @return array{
     *     enabled: bool,
     *     heading: string,
     *     description: string,
     *     primary_button_text: string,
     *     primary_button_url: string,
     *     secondary_button_text: string|null,
     *     secondary_button_url: string|null,
     *     image_path: string|null
     * }
     */
    public function defaults(): array
    {
        return [
            'enabled' => true,
            'heading' => 'Support Our Cause',
            'description' => 'Your donation helps spread Dua for Eyesight and support our educational projects. Every contribution counts!',
            'primary_button_text' => 'Thank You Donate Now',
            'primary_button_url' => 'https://donorbox.org/pilgrim-2',
            'secondary_button_text' => 'Support Pilgrim Project',
            'secondary_button_url' => 'https://donorbox.org/pilgrim-2',
            'image_path' => null,
        ];
    }
}
