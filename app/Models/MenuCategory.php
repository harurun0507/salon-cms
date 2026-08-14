<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class MenuCategory extends Model
{
    /** @deprecated Display default only — do not use for business logic. */
    public const NAME_COMBINATION = '組み合わせ';

    protected $fillable = [
        'name',
        'sort_order',
        'allow_multiple_selection',
    ];

    protected $casts = [
        'allow_multiple_selection' => 'boolean',
    ];

    /**
     * Optional English label for common Japanese category names.
     * Returns null when no mapping exists (no DB field required).
     */
    public function englishName(): ?string
    {
        $map = [
            '組み合わせ' => 'SET',
            'セットメニュー' => 'SET',
            'カット' => 'CUT',
            'カラー' => 'COLOR',
            'パーマ' => 'PERM',
            '縮毛矯正' => 'STRAIGHT',
            'ストレート' => 'STRAIGHT',
            'トリートメント' => 'TREATMENT',
            'ヘッドスパ' => 'HEAD SPA',
            'その他' => 'OTHER',
        ];

        $name = trim((string) $this->name);

        return $map[$name] ?? null;
    }

    /**
     * Primary "set / combination" category: driven by 複数設定可, never by display name.
     */
    public function isCombination(): bool
    {
        return (bool) $this->allow_multiple_selection;
    }

    /**
     * Stable public URL fragment for deep-linking to this category on /menu.
     */
    public function publicAnchorSlug(): string
    {
        if ($this->isCombination()) {
            return 'set';
        }

        return match (trim((string) $this->name)) {
            'カット' => 'cut',
            'カラー' => 'color',
            'パーマ' => 'perm',
            '縮毛矯正', 'ストレート' => 'straight',
            'トリートメント' => 'treatment',
            'ヘッドスパ' => 'head-spa',
            'その他' => 'other',
            default => 'category-'.$this->getKey(),
        };
    }

    /**
     * Icon key for combination-menu category chips (inline SVG component).
     */
    public function tagIconKey(): string
    {
        return match (trim((string) $this->name)) {
            'カット' => 'scissors',
            'カラー' => 'droplet',
            'パーマ' => 'waves',
            '縮毛矯正', 'ストレート' => 'straight',
            'トリートメント' => 'sparkles',
            'ヘッドスパ' => 'user',
            'その他' => 'star',
            default => 'star',
        };
    }

    /**
     * Set menus stay under the allow_multiple category only on the public site,
     * even when also linked to cut/color/etc. for tag display.
     */
    public function includesMenuOnPublicListing(Menu $menu): bool
    {
        if ($this->isCombination()) {
            return true;
        }

        return ! $menu->isCombinationMenu();
    }

    /**
     * Categories with published menus filtered for public listing rules.
     *
     * @return Collection<int, self>
     */
    public static function queryForPublicListing(): Collection
    {
        return static::query()
            ->with([
                'publishedMenus.categories' => fn ($q) => $q->orderBy('menu_categories.sort_order'),
            ])
            ->orderBy('sort_order')
            ->get()
            ->each(function (self $category) {
                $menus = $category->publishedMenus
                    ->filter(fn (Menu $menu) => $category->includesMenuOnPublicListing($menu))
                    ->values();
                $category->setRelation('publishedMenus', $menus);
            });
    }

    public function menus(): BelongsToMany
    {
        return $this->belongsToMany(Menu::class, 'menu_category_menu')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order')
            ->orderBy('menus.id');
    }

    public function publishedMenus(): BelongsToMany
    {
        return $this->menus()->where('menus.is_published', true);
    }

    /**
     * Payload for the public vertical-indicator menu modal.
     *
     * @return array{
     *     id: int,
     *     name: string,
     *     englishName: ?string,
     *     isCombination: bool,
     *     menus: list<array{
     *         id: int,
     *         name: string,
     *         price: ?string,
     *         isInquiryPrice: bool,
     *         description: ?string,
     *         tags: list<string>
     *     }>
     * }
     */
    public function toPublicModalData(): array
    {
        $this->loadMissing([
            'publishedMenus.categories' => fn ($q) => $q->orderBy('menu_categories.sort_order'),
        ]);

        $menus = $this->publishedMenus
            ->filter(fn (Menu $menu) => $this->includesMenuOnPublicListing($menu))
            ->values()
            ->map(function (Menu $menu) {
                $tags = $this->isCombination()
                    ? $menu->constituentCategoriesForDisplay()
                        ->map(fn (self $category) => (string) $category->name)
                        ->values()
                        ->all()
                    : [];

                return [
                    'id' => (int) $menu->id,
                    'name' => (string) $menu->name,
                    'price' => filled($menu->price) ? (string) $menu->price : null,
                    'isInquiryPrice' => $menu->isInquiryPrice(),
                    'description' => filled($menu->description) ? (string) $menu->description : null,
                    'tags' => $tags,
                ];
            })
            ->all();

        return [
            'id' => (int) $this->id,
            'name' => (string) $this->name,
            'englishName' => $this->englishName(),
            'isCombination' => $this->isCombination(),
            'menus' => $menus,
        ];
    }
}
