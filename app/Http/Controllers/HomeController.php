<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Blog;
use App\Models\DesignSetting;
use App\Models\Gallery;
use App\Models\MenuCategory;
use App\Models\News;
use App\Models\SalonSetting;
use App\Models\StaffMember;
use App\Models\TopPageSection;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $setting = SalonSetting::current();
        $design = DesignSetting::current();
        $topSections = $design->usesVerticalScrollIndicator()
            ? TopPageSection::visibleOrderedForPublicNav()
            : TopPageSection::visibleOrdered();
        $sectionMap = $topSections->keyBy('section_key');

        $banners = collect();
        $newsList = collect();
        $blogList = collect();
        $categories = collect();
        $menuModalCategories = collect();
        $galleries = collect();
        $staffMembers = collect();

        if ($sectionMap->has(TopPageSection::KEY_BANNER)) {
            $banners = Banner::query()
                ->currentlyVisible()
                ->forLocation(Banner::LOCATION_TOP)
                ->ordered()
                ->get();
        }

        if ($sectionMap->has(TopPageSection::KEY_NEWS)) {
            $count = max(1, (int) $sectionMap->get(TopPageSection::KEY_NEWS)->display_count);
            $newsList = News::published()->limit($count)->get();
        }

        if ($sectionMap->has(TopPageSection::KEY_BLOG)) {
            $count = max(1, (int) $sectionMap->get(TopPageSection::KEY_BLOG)->display_count);
            $blogList = Blog::published()->limit($count)->get();
        }

        if ($sectionMap->has(TopPageSection::KEY_MENU)) {
            $categories = $this->menuCategoriesForTop();
            if ($design->usesVerticalScrollIndicator()) {
                $menuModalCategories = MenuCategory::queryForPublicListing()
                    ->filter(fn (MenuCategory $category) => $category->publishedMenus->isNotEmpty())
                    ->values();
            }
        }

        if ($sectionMap->has(TopPageSection::KEY_GALLERY)) {
            $count = max(1, (int) $sectionMap->get(TopPageSection::KEY_GALLERY)->display_count);
            $galleries = Gallery::published()->limit($count)->get();
        }

        if ($sectionMap->has(TopPageSection::KEY_STAFF)) {
            $count = max(1, (int) $sectionMap->get(TopPageSection::KEY_STAFF)->display_count);
            $staffMembers = StaffMember::published()->limit($count)->get();
        }

        return view('public.home', [
            'setting' => $setting,
            'design' => $design,
            'heroImages' => $setting->publishedHeroImages()->get(),
            'topSections' => $topSections,
            'banners' => $banners,
            'newsList' => $newsList,
            'blogList' => $blogList,
            'categories' => $categories,
            'menuModalCategories' => $menuModalCategories,
            'galleries' => $galleries,
            'staffMembers' => $staffMembers,
        ]);
    }

    /**
     * Top page menu excerpt: up to N published menus per category (by display order).
     * Categories with no published menus are omitted.
     */
    private function menuCategoriesForTop(int $perCategoryLimit = 3): Collection
    {
        $limited = collect();

        foreach (MenuCategory::queryForPublicListing() as $category) {
            $menus = $category->publishedMenus->take($perCategoryLimit);
            if ($menus->isEmpty()) {
                continue;
            }

            $category->setRelation('publishedMenus', $menus);
            $limited->push($category);
        }

        return $limited;
    }
}
