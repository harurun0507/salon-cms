<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gallery extends Model
{
    public const MAX_IMAGES = 10;

    protected $fillable = [
        'title',
        'caption',
        'staff_id',
        'sort_order',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function images(): HasMany
    {
        return $this->hasMany(GalleryImage::class)->orderBy('display_order')->orderBy('id');
    }

    public function staffMember(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class, 'staff_id');
    }

    /**
     * Alias used by the public gallery detail view.
     */
    public function staff(): BelongsTo
    {
        return $this->staffMember();
    }

    /**
     * Public-facing description (既存 caption カラムを詳細として利用).
     */
    public function getDescriptionAttribute(): ?string
    {
        return filled($this->attributes['caption'] ?? null)
            ? (string) $this->attributes['caption']
            : null;
    }

    /**
     * Display title with fallbacks: title → caption first line → ギャラリー.
     */
    public function displayTitle(): string
    {
        if (filled($this->attributes['title'] ?? null)) {
            return trim((string) $this->attributes['title']);
        }

        $fromCaption = $this->captionFirstLine();
        if ($fromCaption !== null) {
            return $fromCaption;
        }

        return 'ギャラリー';
    }

    /**
     * Description for public pages without duplicating the display title.
     * When title is empty and caption's first line is used as title, the remainder is shown.
     */
    public function displayDescription(): ?string
    {
        if (! filled($this->attributes['caption'] ?? null)) {
            return null;
        }

        $caption = (string) $this->attributes['caption'];

        if (filled($this->attributes['title'] ?? null)) {
            return $caption;
        }

        $normalized = str_replace(["\r\n", "\r"], "\n", $caption);
        $lines = explode("\n", $normalized);
        $foundTitle = false;
        $bodyLines = [];

        foreach ($lines as $line) {
            if (! $foundTitle) {
                if (trim($line) !== '') {
                    $foundTitle = true;
                }

                continue;
            }
            $bodyLines[] = $line;
        }

        $body = rtrim(implode("\n", $bodyLines));

        return $body !== '' ? $body : null;
    }

    public function scopePublished($query)
    {
        return $query
            ->where('is_published', true)
            ->whereHas('images')
            ->with(['images', 'staffMember'])
            ->orderBy('sort_order');
    }

    public function coverImage(): ?GalleryImage
    {
        if ($this->relationLoaded('images')) {
            return $this->images->sortBy([
                ['display_order', 'asc'],
                ['id', 'asc'],
            ])->first();
        }

        return $this->images()->first();
    }

    public function coverImagePath(): ?string
    {
        return $this->coverImage()?->image_path;
    }

    public function captionFirstLine(): ?string
    {
        if (! filled($this->attributes['caption'] ?? null)) {
            return null;
        }

        $normalized = str_replace(["\r\n", "\r"], "\n", (string) $this->attributes['caption']);
        foreach (explode("\n", $normalized) as $line) {
            $trimmed = trim($line);
            if ($trimmed !== '') {
                return $trimmed;
            }
        }

        return null;
    }
}
