<?php

namespace App\Http\Controllers\Admin;

use App\Models\SalonSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SnsSettingController extends AdminController
{
    public function edit(): View
    {
        $setting = SalonSetting::current();

        return view('admin.store.sns', compact('setting'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'instagram_url' => ['nullable', 'url', 'max:500'],
            // Future: line_url, tiktok_url, youtube_url, facebook_url
        ]);

        SalonSetting::current()->update($validated);

        return redirect()->route('admin.store.sns')->with('success', 'SNS設定を更新しました。');
    }
}
