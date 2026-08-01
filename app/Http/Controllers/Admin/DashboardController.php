<?php

namespace App\Http\Controllers\Admin;

use App\Models\Gallery;
use App\Models\MenuCategory;
use App\Models\News;
use App\Models\StaffMember;
use Illuminate\View\View;

class DashboardController extends AdminController
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'newsCount' => News::count(),
            'galleryCount' => Gallery::count(),
            'menuCount' => MenuCategory::withCount('menus')->get()->sum('menus_count'),
            'staffCount' => StaffMember::count(),
        ]);
    }
}
