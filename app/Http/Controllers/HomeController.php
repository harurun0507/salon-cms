<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Blog;
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
        $topSections = TopPageSection::visibleOrdered();
        $sectionMap = $topSections->keyBy('section_key');

        $banners = collect();
        $newsList = collect();
        $blogList = collect();
        $categories = collect();
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
            $count = max(1, (int) $sectionMap->get(TopPageSection::KEY_MENU)->display_count);
            $categories = $this->menuCategoriesForTop($count);
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
            'heroImages' => $setting->publishedHeroImages()->get(),
            'topSections' => $topSections,
            'banners' => $banners,
            'newsList' => $newsList,
            'blogList' => $blogList,
            'categories' => $categories,
            'galleries' => $galleries,
            'staffMembers' => $staffMembers,
        ]);
    }

    private function menuCategoriesForTop(int $limit): Collection
    {
        $categories = MenuCategory::query()
            ->with('publishedMenus')
            ->orderBy('sort_order')
            ->get();

        $remaining = $limit;
        $limited = collect();

        foreach ($categories as $category) {
            if ($remaining <= 0) {
                break;
            }

            $menus = $category->publishedMenus->take($remaining);
            if ($menus->isEmpty()) {
                continue;
            }

            $category->setRelation('publishedMenus', $menus);
            $limited->push($category);
            $remaining -= $menus->count();
        }

        return $limited;
    }
}
