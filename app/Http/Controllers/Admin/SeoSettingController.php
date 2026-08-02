<?php

namespace App\Http\Controllers\Admin;

use App\Models\SalonSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SeoSettingController extends AdminController
{
    public function edit(): View
    {
        $setting = SalonSetting::current();

        return view('admin.system.seo', compact('setting'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:1000'],
            'meta_keywords' => ['nullable', 'string', 'max:500'],
            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string', 'max:1000'],
            'og_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'twitter_card' => ['required', Rule::in([
                SalonSetting::TWITTER_CARD_SUMMARY,
                SalonSetting::TWITTER_CARD_SUMMARY_LARGE_IMAGE,
            ])],
            'noindex' => ['required', 'in:0,1'],
            'favicon' => ['nullable', 'file', 'mimes:ico,png,svg,webp,jpg,jpeg', 'max:1024'],
        ]);

        $setting = SalonSetting::current();

        $setting->update([
            ...collect($validated)->except(['og_image', 'favicon', 'noindex'])->all(),
            'noindex' => ($validated['noindex'] ?? '0') === '1',
            'og_image' => $this->storeImage($request->file('og_image'), 'settings/og', $setting->og_image),
            'favicon_path' => $this->storeImage($request->file('favicon'), 'settings/favicon', $setting->favicon_path),
        ]);

        return redirect()->route('admin.system.seo')->with('success', 'SEO設定を更新しました。');
    }
}
