<?php

namespace App\Http\Controllers\Admin;

use App\Models\SalonSetting;
use App\Support\GoogleMapEmbedUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SalonSettingController extends AdminController
{
    public function edit(): View
    {
        $setting = SalonSetting::current();

        return view('admin.settings.edit', compact('setting'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->merge([
            'google_map_embed_url' => GoogleMapEmbedUrl::normalize($request->input('google_map_embed_url')),
        ]);

        $validated = $request->validate([
            'shop_name' => ['required', 'string', 'max:255'],
            'shop_name_display_type' => ['required', Rule::in([SalonSetting::DISPLAY_TYPE_TEXT, SalonSetting::DISPLAY_TYPE_LOGO])],
            'logo_alt_text' => ['nullable', 'string', 'max:255'],
            'logo_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'address' => ['nullable', 'string', 'max:255'],
            'access_directions' => ['nullable', 'string'],
            'business_hours' => ['nullable', 'string'],
            'closed_days' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'payment_methods' => ['nullable', 'string'],
            'cut_price' => ['nullable', 'string', 'max:255'],
            'seat_count' => ['nullable', 'string', 'max:255'],
            'staff_count' => ['nullable', 'string', 'max:255'],
            'parking' => ['nullable', 'string'],
            'commitment_conditions' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'other_info' => ['nullable', 'string'],
            'google_map_url' => ['nullable', 'url', 'max:500'],
            'google_map_embed_url' => ['nullable', 'string', 'url'],
        ]);

        $setting = SalonSetting::current();

        $setting->update([
            ...collect($validated)->except(['logo_image'])->all(),
            'logo_image' => $this->storeImage($request->file('logo_image'), 'settings/logos', $setting->logo_image),
        ]);

        return redirect()->route('admin.settings.edit')->with('success', '店舗情報を更新しました。');
    }

    public function destroyLogo(): RedirectResponse
    {
        $setting = SalonSetting::current();

        if ($setting->logo_image) {
            $this->deleteImage($setting->logo_image);
            $setting->update([
                'logo_image' => null,
            ]);
        }

        return redirect()->route('admin.settings.edit')->with('success', 'ロゴ画像を削除しました。');
    }
}
