<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PrivacyPageController extends Controller
{
    public function index(): View
    {
        return view('public.privacy');
    }
}
