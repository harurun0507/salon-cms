<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Banner extends Model
{
    public const LOCATION_TOP = 'top';

    public const LOCATION_COMMON = 'common';

    public const LOCATION_MENU = 'menu';

    public const LOCATION_GALLERY = 'gallery';

    public const LOCATION_STAFF = 'staff';

    public const LOCATION_NEWS = 'news';

    public const LOCATIONS = [
        self::LOCATION_TOP,
        self::LOCATION_COMMON,
        self::LOCATION_MENU,
        self::LOCATION_GALLERY,
        self::LOCATION_STAFF,
        self::LOCATION_NEWS,
    ];

    public const LOCATION_LABELS = [
        self::LOCATION_TOP => 'トップ（メイン）',
        self::LOCATION_COMMON => '共通（将来用）',
        self::LOCATION_MENU => 'メニュー（将来用）',
        self::LOCATION_GALLERY => 'ギャラリー（将来用）',
        self::LOCATION_STAFF => 'スタッフ（将来用）',
        self::LOCATION_NEWS => 'お知らせ（将来用）',
    ];

    /**
     * Future feature: show display-location chips in the banner admin UI.
     * Keep false until multi-location banners are ready; backend/validation stay intact.
     */
    public const DISPLAY_LOCATION_UI_ENABLED = false;

    public const LINK_TARGETS = ['_self', '_blank'];

    protected $fillable = [
        'title',
        'description',
        'image_path',
        'alt_text',
        'link_url',
        'link_target',
        'display_location',
        'display_order',
        'is_published',
        'published_from',
        'published_until',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'display_order' => 'integer',
        'published_from' => 'datetime',
        'published_until' => 'datetime',
    ];

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order')->orderBy('id');
    }

    public function scopeForLocation(Builder $query, string $location): Builder
    {
        return $query->where('display_location', $location);
    }

    /**
     * Published and within the optional publish window.
     * Null published_from = immediate; null published_until = no end.
     */
    public function scopeCurrentlyVisible(Builder $query, ?Carbon $at = null): Builder
    {
        $at ??= now();

        return $query
            ->where('is_published', true)
            ->where(function (Builder $q) use ($at) {
                $q->whereNull('published_from')
                    ->orWhere('published_from', '<=', $at);
            })
            ->where(function (Builder $q) use ($at) {
                $q->whereNull('published_until')
                    ->orWhere('published_until', '>=', $at);
            });
    }

    public function altTextOrTitle(): string
    {
        $alt = trim((string) $this->alt_text);

        return $alt !== '' ? $alt : (string) $this->title;
    }

    public function hasLink(): bool
    {
        return filled($this->link_url);
    }

    public function opensInNewTab(): bool
    {
        return $this->link_target === '_blank';
    }
}
