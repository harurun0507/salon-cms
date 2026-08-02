<?php

namespace App\Http\Controllers\Admin;

use App\Models\SalonSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReservationSettingController extends AdminController
{
    public function edit(): View
    {
        $setting = SalonSetting::current();

        return view('admin.store.reservations', compact('setting'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'hot_pepper_url' => ['nullable', 'url', 'max:500'],
            // Future: line_booking_url, phone_booking, own_booking_url
        ]);

        SalonSetting::current()->update($validated);

        return redirect()->route('admin.store.reservations')->with('success', '予約設定を更新しました。');
    }
}
