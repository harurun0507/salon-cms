<?php

namespace App\Http\Controllers;

use App\Models\Gallery;
use Illuminate\View\View;

class GalleryPageController extends Controller
{
    public function index(): View
    {
        return view('public.gallery', [
            'galleries' => Gallery::published()->get(),
        ]);
    }
}
