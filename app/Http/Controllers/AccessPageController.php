<?php

namespace App\Http\Controllers;

use App\Models\SalonSetting;
use Illuminate\View\View;

class AccessPageController extends Controller
{
    public function index(): View
    {
        return view('public.access', [
            'setting' => SalonSetting::current(),
        ]);
    }
}
