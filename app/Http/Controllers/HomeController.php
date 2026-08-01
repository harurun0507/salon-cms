<?php

namespace App\Http\Controllers;

use App\Models\Gallery;
use App\Models\MenuCategory;
use App\Models\News;
use App\Models\SalonSetting;
use App\Models\StaffMember;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $setting = SalonSetting::current();

        return view('public.home', [
            'setting' => $setting,
            'heroImages' => $setting->publishedHeroImages()->get(),
            'newsList' => News::published()->limit(5)->get(),
            'galleries' => Gallery::published()->limit(6)->get(),
            'categories' => MenuCategory::query()->with('publishedMenus')->orderBy('sort_order')->get(),
            'staffMembers' => StaffMember::published()->limit(4)->get(),
        ]);
    }
}
