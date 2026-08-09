<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use Illuminate\View\View;

class CampaignPageController extends Controller
{
    public function index(): View
    {
        return view('public.campaign', [
            'banners' => Banner::query()
                ->currentlyVisible()
                ->forLocation(Banner::LOCATION_TOP)
                ->ordered()
                ->get(),
        ]);
    }
}
