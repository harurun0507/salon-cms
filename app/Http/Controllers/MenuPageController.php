<?php

namespace App\Http\Controllers;

use App\Models\MenuCategory;
use Illuminate\View\View;

class MenuPageController extends Controller
{
    public function index(): View
    {
        return view('public.menu', [
            'categories' => MenuCategory::query()->with('publishedMenus')->orderBy('sort_order')->get(),
        ]);
    }
}
