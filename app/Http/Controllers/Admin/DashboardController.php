<?php

namespace App\Http\Controllers\Admin;

use App\Models\Banner;
use App\Models\Gallery;
use App\Models\HeroImage;
use App\Models\Menu;
use App\Models\MenuCategory;
use App\Models\News;
use App\Models\SalonSetting;
use App\Models\SocialLink;
use App\Models\StaffMember;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends AdminController
{
    private const RECENT_LIMIT = 5;

    private const RECENT_FETCH_PER_TYPE = 5;

    public function index(): View
    {
        $setting = SalonSetting::current();
        $now = now();
        $isAdmin = auth()->user()?->isAdmin() ?? false;

        $newsCounts = $this->aggregatePublishCounts(
            News::query(),
            'is_published = 1 AND (published_at IS NULL OR published_at <= ?)',
            [$now]
        );

        $galleryCounts = $this->aggregatePublishCounts(Gallery::query(), 'is_published = 1');
        $menuCounts = $this->aggregatePublishCounts(Menu::query(), 'is_published = 1');
        $staffCounts = $this->aggregatePublishCounts(StaffMember::query(), 'is_published = 1');

        $publishedHeroCount = (int) HeroImage::query()->where('is_published', true)->count();

        $siteStatus = [
            'public_url' => route('home'),
            'public_ready' => true,
            'indexing' => ! $setting->noindex,
            'ga_configured' => $setting->hasGaMeasurementId(),
            'reservation_configured' => filled($setting->hot_pepper_url),
            'sns_configured' => SocialLink::hasAnyConfigured(),
        ];

        $attentionItems = $this->buildAttentionItems(
            $setting,
            $isAdmin,
            $publishedHeroCount,
            (int) $staffCounts['published'],
            (int) $menuCounts['published'],
        );

        return view('admin.dashboard', [
            'siteStatus' => $siteStatus,
            'newsCounts' => $newsCounts,
            'galleryCounts' => $galleryCounts,
            'menuCounts' => $menuCounts,
            'staffCounts' => $staffCounts,
            'recentUpdates' => $this->recentUpdates($now),
            'attentionItems' => $attentionItems,
        ]);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  list<mixed>  $bindings
     * @return array{total: int, published: int, unpublished: int}
     */
    private function aggregatePublishCounts($query, string $publishedSql, array $bindings = []): array
    {
        $row = $query
            ->toBase()
            ->selectRaw(
                "COUNT(*) as total, COALESCE(SUM(CASE WHEN {$publishedSql} THEN 1 ELSE 0 END), 0) as published",
                $bindings
            )
            ->first();

        $total = (int) ($row->total ?? 0);
        $published = (int) ($row->published ?? 0);

        return [
            'total' => $total,
            'published' => $published,
            'unpublished' => max(0, $total - $published),
        ];
    }

    /**
     * @return list<array{type: string, type_label: string, title: string, updated_at: Carbon, is_published: bool, edit_url: string}>
     */
    private function recentUpdates(Carbon $now): array
    {
        $heroPositions = $this->positionMap(HeroImage::query()->ordered());
        $bannerPositions = $this->positionMap(Banner::query()->ordered());
        $galleryPositions = $this->positionMap(
            Gallery::query()->orderBy('sort_order')->orderByDesc('id')
        );
        $staffPositions = $this->positionMap(
            StaffMember::query()->orderBy('sort_order')->orderBy('id')
        );
        $menuPositions = $this->menuPositionMap();

        $items = collect()
            ->merge($this->recentNews($now))
            ->merge($this->recentGalleries($galleryPositions))
            ->merge($this->recentMenus($menuPositions))
            ->merge($this->recentStaff($staffPositions))
            ->merge($this->recentBanners($now, $bannerPositions))
            ->merge($this->recentHeroImages($heroPositions))
            ->sortByDesc(fn (array $item) => $item['updated_at']->getTimestamp())
            ->values()
            ->take(self::RECENT_LIMIT);

        return $items->all();
    }

    /**
     * 1-based display positions matching admin list order.
     *
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $orderedQuery
     * @return array<int, int>
     */
    private function positionMap(Builder $orderedQuery): array
    {
        return $orderedQuery
            ->pluck('id')
            ->values()
            ->mapWithKeys(fn ($id, $index) => [(int) $id => $index + 1])
            ->all();
    }

    /**
     * Menu admin order: categories by sort_order, then menus by sort_order within each category.
     *
     * @return array<int, int>
     */
    private function menuPositionMap(): array
    {
        $menus = MenuCategory::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->with(['menus' => fn ($q) => $q->orderBy('menu_category_menu.sort_order')->orderBy('menus.id')])
            ->get()
            ->flatMap(fn (MenuCategory $category) => $category->menus);

        return $menus
            ->values()
            ->mapWithKeys(fn (Menu $menu, $index) => [(int) $menu->id => $index + 1])
            ->all();
    }

    /**
     * Prefer a real title when filled; otherwise「種別名＋表示順番号」.
     */
    private function resolveRecentTitle(?string $value, string $fallbackLabel, int $sortIndex): string
    {
        $trimmed = trim((string) $value);

        return $trimmed !== '' ? $trimmed : $fallbackLabel.$sortIndex;
    }

    /**
     * @return Collection<int, array{type: string, type_label: string, title: string, updated_at: Carbon, is_published: bool, edit_url: string}>
     */
    private function recentNews(Carbon $now): Collection
    {
        return News::query()
            ->orderByDesc('updated_at')
            ->limit(self::RECENT_FETCH_PER_TYPE)
            ->get(['id', 'title', 'is_published', 'published_at', 'updated_at'])
            ->map(function (News $news) use ($now) {
                $isPublished = $news->is_published
                    && ($news->published_at === null || $news->published_at->lte($now));

                return [
                    'type' => 'news',
                    'type_label' => 'お知らせ',
                    'title' => (string) $news->title,
                    'updated_at' => $news->updated_at,
                    'is_published' => $isPublished,
                    'edit_url' => route('admin.news.index'),
                ];
            });
    }

    /**
     * @param  array<int, int>  $positions
     * @return Collection<int, array{type: string, type_label: string, title: string, updated_at: Carbon, is_published: bool, edit_url: string}>
     */
    private function recentGalleries(array $positions): Collection
    {
        return Gallery::query()
            ->orderByDesc('updated_at')
            ->limit(self::RECENT_FETCH_PER_TYPE)
            ->get(['id', 'caption', 'is_published', 'updated_at'])
            ->map(function (Gallery $gallery) use ($positions) {
                $sortIndex = $positions[(int) $gallery->id] ?? 1;

                return [
                    'type' => 'gallery',
                    'type_label' => 'ギャラリー',
                    'title' => $this->resolveRecentTitle(
                        filled($gallery->getAttributes()['title'] ?? null)
                            ? trim((string) $gallery->getAttributes()['title'])
                            : $gallery->captionFirstLine(),
                        'ギャラリー画像',
                        $sortIndex
                    ),
                    'updated_at' => $gallery->updated_at,
                    'is_published' => (bool) $gallery->is_published,
                    'edit_url' => route('admin.galleries.edit', $gallery),
                ];
            });
    }

    /**
     * @param  array<int, int>  $positions
     * @return Collection<int, array{type: string, type_label: string, title: string, updated_at: Carbon, is_published: bool, edit_url: string}>
     */
    private function recentMenus(array $positions): Collection
    {
        return Menu::query()
            ->orderByDesc('updated_at')
            ->limit(self::RECENT_FETCH_PER_TYPE)
            ->get(['id', 'name', 'is_published', 'updated_at'])
            ->map(function (Menu $menu) use ($positions) {
                $sortIndex = $positions[(int) $menu->id] ?? 1;

                return [
                    'type' => 'menu',
                    'type_label' => 'メニュー',
                    'title' => $this->resolveRecentTitle($menu->name, 'メニュー', $sortIndex),
                    'updated_at' => $menu->updated_at,
                    'is_published' => (bool) $menu->is_published,
                    'edit_url' => route('admin.menus.edit', $menu),
                ];
            });
    }

    /**
     * @param  array<int, int>  $positions
     * @return Collection<int, array{type: string, type_label: string, title: string, updated_at: Carbon, is_published: bool, edit_url: string}>
     */
    private function recentStaff(array $positions): Collection
    {
        return StaffMember::query()
            ->orderByDesc('updated_at')
            ->limit(self::RECENT_FETCH_PER_TYPE)
            ->get(['id', 'name', 'is_published', 'updated_at'])
            ->map(function (StaffMember $staff) use ($positions) {
                $sortIndex = $positions[(int) $staff->id] ?? 1;

                return [
                    'type' => 'staff',
                    'type_label' => 'スタッフ',
                    'title' => $this->resolveRecentTitle($staff->name, 'スタッフ', $sortIndex),
                    'updated_at' => $staff->updated_at,
                    'is_published' => (bool) $staff->is_published,
                    'edit_url' => route('admin.staff.index'),
                ];
            });
    }

    /**
     * @param  array<int, int>  $positions
     * @return Collection<int, array{type: string, type_label: string, title: string, updated_at: Carbon, is_published: bool, edit_url: string}>
     */
    private function recentBanners(Carbon $now, array $positions): Collection
    {
        return Banner::query()
            ->orderByDesc('updated_at')
            ->limit(self::RECENT_FETCH_PER_TYPE)
            ->get(['id', 'title', 'is_published', 'published_from', 'published_until', 'updated_at'])
            ->map(function (Banner $banner) use ($now, $positions) {
                $isPublished = $banner->is_published
                    && ($banner->published_from === null || $banner->published_from->lte($now))
                    && ($banner->published_until === null || $banner->published_until->gte($now));
                $sortIndex = $positions[(int) $banner->id] ?? 1;

                return [
                    'type' => 'banner',
                    'type_label' => 'キャンペーン',
                    'title' => $this->resolveRecentTitle($banner->title, 'キャンペーン', $sortIndex),
                    'updated_at' => $banner->updated_at,
                    'is_published' => $isPublished,
                    'edit_url' => route('admin.home.banners'),
                ];
            });
    }

    /**
     * @param  array<int, int>  $positions
     * @return Collection<int, array{type: string, type_label: string, title: string, updated_at: Carbon, is_published: bool, edit_url: string}>
     */
    private function recentHeroImages(array $positions): Collection
    {
        return HeroImage::query()
            ->orderByDesc('updated_at')
            ->limit(self::RECENT_FETCH_PER_TYPE)
            ->get(['id', 'alt_text', 'is_published', 'updated_at'])
            ->map(function (HeroImage $hero) use ($positions) {
                $sortIndex = $positions[(int) $hero->id] ?? 1;

                return [
                    'type' => 'hero',
                    'type_label' => 'メインビジュアル',
                    'title' => $this->resolveRecentTitle($hero->alt_text, 'メインビジュアル', $sortIndex),
                    'updated_at' => $hero->updated_at,
                    'is_published' => (bool) $hero->is_published,
                    'edit_url' => route('admin.home.hero'),
                ];
            });
    }

    /**
     * @return list<array{key: string, label: string, url: string|null}>
     */
    private function buildAttentionItems(
        SalonSetting $setting,
        bool $isAdmin,
        int $publishedHeroCount,
        int $publishedStaffCount,
        int $publishedMenuCount,
    ): array {
        $items = [];

        if ($isAdmin && ! $setting->hasGaMeasurementId()) {
            $items[] = [
                'key' => 'ga4',
                'label' => 'アクセス解析（GA4）の測定IDが未設定です',
                'url' => route('admin.system.analytics'),
            ];
        }

        if ($isAdmin && blank($setting->og_image)) {
            $items[] = [
                'key' => 'ogp',
                'label' => 'OGP画像が未設定です',
                'url' => route('admin.system.seo'),
            ];
        }

        if ($isAdmin && blank($setting->favicon_path)) {
            $items[] = [
                'key' => 'favicon',
                'label' => 'ファビコンが未設定です',
                'url' => route('admin.system.seo'),
            ];
        }

        if (blank($setting->hot_pepper_url)) {
            $items[] = [
                'key' => 'reservation',
                'label' => '予約URLが未設定です',
                'url' => route('admin.store.reservations'),
            ];
        }

        if (! SocialLink::hasAnyConfigured()) {
            $items[] = [
                'key' => 'sns',
                'label' => 'SNSリンクが未設定です',
                'url' => route('admin.store.sns'),
            ];
        }

        if ($publishedHeroCount === 0) {
            $items[] = [
                'key' => 'hero',
                'label' => '公開中のメインビジュアルがありません',
                'url' => route('admin.home.hero'),
            ];
        }

        if ($publishedStaffCount === 0) {
            $items[] = [
                'key' => 'staff',
                'label' => '公開中のスタッフがいません',
                'url' => route('admin.staff.index'),
            ];
        }

        if ($publishedMenuCount === 0) {
            $items[] = [
                'key' => 'menus',
                'label' => '公開中のメニューがありません',
                'url' => route('admin.menus.index'),
            ];
        }

        return $items;
    }
}
