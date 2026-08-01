<?php

namespace App\Http\Controllers;

use App\Models\StaffMember;
use Illuminate\View\View;

class StaffPageController extends Controller
{
    public function index(): View
    {
        return view('public.staff', [
            'staffMembers' => StaffMember::published()->get(),
        ]);
    }
}
