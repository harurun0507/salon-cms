<?php

namespace App\Http\Controllers;

use App\Models\Gallery;
use App\Models\SalonSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GalleryPageController extends Controller
{
    public function index(): View
    {
        return view('public.gallery', [
            'galleries' => Gallery::published()->get(),
            'setting' => SalonSetting::current(),
        ]);
    }

    public function show(Gallery $gallery): View|RedirectResponse
    {
        if (! $gallery->is_published) {
            abort(404);
        }

        $gallery->load(['images', 'staffMember']);

        if ($gallery->images->isEmpty()) {
            abort(404);
        }

        return view('public.gallery-show', [
            'gallery' => $gallery,
            'setting' => SalonSetting::current(),
        ]);
    }
}
