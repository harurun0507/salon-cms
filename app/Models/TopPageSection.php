<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class TopPageSection extends Model
{
    public const KEY_BANNER = 'banner';

    public const KEY_NEWS = 'news';

    public const KEY_BLOG = 'blog';

    public const KEY_MENU = 'menu';

    public const KEY_GALLERY = 'gallery';

    public const KEY_STAFF = 'staff';

    public const KEY_ACCESS = 'access';

    public const KEYS = [
        self::KEY_BANNER,
        self::KEY_NEWS,
        self::KEY_BLOG,
        self::KEY_GALLERY,
        self::KEY_MENU,
        self::KEY_STAFF,
        self::KEY_ACCESS,
    ];

    public const LABELS = [
        self::KEY_BANNER => 'キャンペーン',
        self::KEY_NEWS => 'お知らせ',
        self::KEY_BLOG => 'NEWS & BLOG',
        self::KEY_MENU => 'メニュー',
        self::KEY_GALLERY => 'ギャラリー',
        self::KEY_STAFF => 'スタッフ',
        self::KEY_ACCESS => 'アクセス',
    ];

    /**
     * Single source of truth for public top section flow.
     * Aligns with header nav: Concept → News → Gallery → Menu → Staff → Access.
     * Campaign sits after Concept (not in header). Blog scrolls with / after News.
     *
     * @var list<array{id: string, label: string, header: bool, keys: list<string>}>
     */
    public const PUBLIC_SECTION_FLOW = [
        ['id' => 'hero-slider', 'label' => 'トップ', 'header' => false, 'keys' => []],
        ['id' => 'concept', 'label' => 'Concept', 'header' => true, 'keys' => []],
        ['id' => 'banners', 'label' => 'Campaign', 'header' => false, 'keys' => [self::KEY_BANNER]],
        ['id' => 'news', 'label' => 'News', 'header' => true, 'keys' => [self::KEY_NEWS, self::KEY_BLOG]],
        ['id' => 'blog', 'label' => 'Blog', 'header' => false, 'keys' => [self::KEY_BLOG]],
        ['id' => 'gallery', 'label' => 'Gallery', 'header' => true, 'keys' => [self::KEY_GALLERY]],
        ['id' => 'menu', 'label' => 'Menu', 'header' => true, 'keys' => [self::KEY_MENU]],
        ['id' => 'staff', 'label' => 'Staff', 'header' => true, 'keys' => [self::KEY_STAFF]],
        ['id' => 'access', 'label' => 'Access', 'header' => true, 'keys' => [self::KEY_ACCESS]],
    ];

    public const DEFAULTS = [
        self::KEY_BANNER => ['is_visible' => true, 'display_order' => 0, 'display_count' => null],
        self::KEY_NEWS => ['is_visible' => true, 'display_order' => 1, 'display_count' => 3],
        self::KEY_BLOG => ['is_visible' => true, 'display_order' => 2, 'display_count' => 3],
        self::KEY_GALLERY => ['is_visible' => true, 'display_order' => 3, 'display_count' => 6],
        self::KEY_MENU => ['is_visible' => true, 'display_order' => 4, 'display_count' => 6],
        self::KEY_STAFF => ['is_visible' => true, 'display_order' => 5, 'display_count' => 4],
        self::KEY_ACCESS => ['is_visible' => true, 'display_order' => 6, 'display_count' => null],
    ];

    public const COUNT_MAX = [
        self::KEY_NEWS => 20,
        self::KEY_BLOG => 12,
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

    /**
     * Configurable section keys in public nav / top-page flow order.
     *
     * @return list<string>
     */
    public static function publicConfigurableKeysInOrder(): array
    {
        $keys = [];
        foreach (self::PUBLIC_SECTION_FLOW as $item) {
            foreach ($item['keys'] as $key) {
                if (! in_array($key, $keys, true)) {
                    $keys[] = $key;
                }
            }
        }

        return $keys;
    }

    /**
     * Sort visible top-page sections into the shared public flow order.
     * Hidden sections are already excluded by the caller.
     */
    public static function sortByPublicOrder(Collection $sections): Collection
    {
        $order = array_flip(self::publicConfigurableKeysInOrder());

        return $sections
            ->sortBy(fn (self $section) => $order[$section->section_key] ?? 1000)
            ->values();
    }

    /**
     * Visible sections ordered for the public top page under vertical-indicator mode.
     */
    public static function visibleOrderedForPublicNav(): Collection
    {
        return self::sortByPublicOrder(self::visibleOrdered());
    }

    /**
     * Meta for the vertical scroll indicator / wheel navigation.
     *
     * @return list<array{id: string, label: string}>
     */
    public static function publicScrollSectionMeta(): array
    {
        return array_map(
            static fn (array $item): array => [
                'id' => $item['id'],
                'label' => $item['label'],
            ],
            self::PUBLIC_SECTION_FLOW
        );
    }

    /**
     * Header / mobile nav items derived from the shared public flow.
     *
     * @param  array<string, bool>  $visibilityByKey
     * @return list<array{id: string, label: string, href: string}>
     */
    public static function publicHeaderNavItems(array $visibilityByKey): array
    {
        $items = [];

        foreach (self::PUBLIC_SECTION_FLOW as $item) {
            if (! $item['header']) {
                continue;
            }

            $keys = $item['keys'];
            if ($keys === []) {
                // Concept (and similar always-on anchors)
                $items[] = [
                    'id' => $item['id'],
                    'label' => $item['label'],
                    'href' => url('/#'.$item['id']),
                ];

                continue;
            }

            $visible = false;
            foreach ($keys as $key) {
                if ($visibilityByKey[$key] ?? false) {
                    $visible = true;
                    break;
                }
            }
            if (! $visible) {
                continue;
            }

            $hrefId = $item['id'];
            if ($item['id'] === 'news') {
                $hrefId = ($visibilityByKey[self::KEY_NEWS] ?? false) ? 'news' : 'blog';
            }

            $items[] = [
                'id' => $item['id'],
                'label' => $item['label'],
                'href' => url('/#'.$hrefId),
            ];
        }

        return $items;
    }

    /**
     * @return array<string, bool>
     */
    public static function visibilityByKey(): array
    {
        return static::ensureDefaults()
            ->mapWithKeys(fn (self $section) => [$section->section_key => (bool) $section->is_visible])
            ->all();
    }
}
