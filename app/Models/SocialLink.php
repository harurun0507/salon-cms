<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class SocialLink extends Model
{
    protected $fillable = [
        'service_key',
        'url',
        'is_visible',
        'display_order',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'display_order' => 'integer',
    ];

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order')->orderBy('id');
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }

    public function meta(): array
    {
        return config('social_links.services.'.$this->service_key, [
            'label' => $this->service_key,
            'url_label' => 'URL',
            'placeholder' => null,
            'default_order' => $this->display_order,
        ]);
    }

    public function label(): string
    {
        return (string) ($this->meta()['label'] ?? $this->service_key);
    }

    public function urlLabel(): string
    {
        return (string) ($this->meta()['url_label'] ?? 'URL');
    }

    public function placeholder(): ?string
    {
        $value = $this->meta()['placeholder'] ?? null;

        return filled($value) ? (string) $value : null;
    }

    public function isPubliclyShown(): bool
    {
        return $this->is_visible && filled($this->url);
    }

    public static function serviceKeys(): array
    {
        return array_keys(config('social_links.services', []));
    }

    public static function ensureDefaults(): Collection
    {
        foreach (config('social_links.services', []) as $key => $meta) {
            static::query()->firstOrCreate(
                ['service_key' => $key],
                [
                    'url' => null,
                    'is_visible' => false,
                    'display_order' => (int) ($meta['default_order'] ?? 1),
                ]
            );
        }

        static::syncLegacyInstagramUrl();

        return static::query()->ordered()->get();
    }

    /**
     * One-time / safety sync from salon_settings.instagram_url when the
     * Instagram social_links row still has no URL.
     */
    public static function syncLegacyInstagramUrl(): void
    {
        $instagram = static::query()->where('service_key', 'instagram')->first();
        if (! $instagram || filled($instagram->url)) {
            return;
        }

        $legacy = SalonSetting::query()->value('instagram_url');
        if (! filled($legacy)) {
            return;
        }

        $instagram->update([
            'url' => trim((string) $legacy),
            'is_visible' => true,
        ]);
    }

    public static function visibleForPublic(): Collection
    {
        return static::ensureDefaults()
            ->filter(fn (self $link) => $link->isPubliclyShown())
            ->values();
    }

    public static function hasAnyConfigured(): bool
    {
        static::ensureDefaults();

        return static::query()
            ->whereNotNull('url')
            ->where('url', '!=', '')
            ->exists();
    }

    /**
     * Keep salon_settings.instagram_url in sync for older callers during transition.
     */
    public static function syncLegacyInstagramColumn(?string $url): void
    {
        $setting = SalonSetting::current();
        $normalized = filled($url) ? trim($url) : null;
        if ($setting->instagram_url === $normalized) {
            return;
        }

        $setting->update(['instagram_url' => $normalized]);
    }
}
