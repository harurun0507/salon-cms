<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class TopPageSection extends Model
{
    public const KEY_BANNER = 'banner';

    public const KEY_NEWS = 'news';

    public const KEY_MENU = 'menu';

    public const KEY_GALLERY = 'gallery';

    public const KEY_STAFF = 'staff';

    public const KEY_ACCESS = 'access';

    public const KEYS = [
        self::KEY_BANNER,
        self::KEY_NEWS,
        self::KEY_MENU,
        self::KEY_GALLERY,
        self::KEY_STAFF,
        self::KEY_ACCESS,
    ];

    public const LABELS = [
        self::KEY_BANNER => 'バナー',
        self::KEY_NEWS => 'お知らせ',
        self::KEY_MENU => 'メニュー',
        self::KEY_GALLERY => 'ギャラリー',
        self::KEY_STAFF => 'スタッフ',
        self::KEY_ACCESS => 'アクセス',
    ];

    public const DEFAULTS = [
        self::KEY_BANNER => ['is_visible' => true, 'display_order' => 0, 'display_count' => null],
        self::KEY_NEWS => ['is_visible' => true, 'display_order' => 1, 'display_count' => 3],
        self::KEY_MENU => ['is_visible' => true, 'display_order' => 2, 'display_count' => 6],
        self::KEY_GALLERY => ['is_visible' => true, 'display_order' => 3, 'display_count' => 6],
        self::KEY_STAFF => ['is_visible' => true, 'display_order' => 4, 'display_count' => 4],
        self::KEY_ACCESS => ['is_visible' => true, 'display_order' => 5, 'display_count' => null],
    ];

    public const COUNT_MAX = [
        self::KEY_NEWS => 20,
        self::KEY_MENU => 30,
        self::KEY_GALLERY => 24,
        self::KEY_STAFF => 20,
    ];

    protected $fillable = [
        'section_key',
        'is_visible',
        'display_order',
        'display_count',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'display_order' => 'integer',
        'display_count' => 'integer',
    ];

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order')->orderBy('id');
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }

    public function label(): string
    {
        return self::LABELS[$this->section_key] ?? $this->section_key;
    }

    public function supportsDisplayCount(): bool
    {
        return array_key_exists($this->section_key, self::COUNT_MAX);
    }

    public function maxDisplayCount(): ?int
    {
        return self::COUNT_MAX[$this->section_key] ?? null;
    }

    public static function ensureDefaults(): Collection
    {
        foreach (self::DEFAULTS as $key => $defaults) {
            static::query()->firstOrCreate(
                ['section_key' => $key],
                $defaults
            );
        }

        return static::query()->ordered()->get();
    }

    public static function visibleOrdered(): Collection
    {
        static::ensureDefaults();

        return static::query()->visible()->ordered()->get();
    }
}
