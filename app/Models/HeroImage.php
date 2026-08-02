<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HeroImage extends Model
{
    public const MAX_COUNT = 10;

    /**
     * Admin-editable per-image fields (bulk save).
     * Add new columns here when extending (e.g. catch_copy, link_url).
     *
     * @var list<string>
     */
    public const ADMIN_META_FIELDS = [
        'sort_order',
        'alt_text',
        'is_published',
        // 'catch_copy',
        // 'link_url',
    ];

    protected $fillable = [
        'salon_setting_id',
        'image_path',
        'alt_text',
        'sort_order',
        'is_published',
        // Future columns: catch_copy, link_url, meta (JSON), …
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'sort_order' => 'integer',
        // 'meta' => 'array',
    ];

    public function salonSetting(): BelongsTo
    {
        return $this->belongsTo(SalonSetting::class);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->ordered();
    }
}
