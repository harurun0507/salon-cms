<?php

namespace App\Http\Controllers\Admin;

use App\Models\HeroImage;
use App\Models\SalonSetting;
use App\Support\GoogleMapEmbedUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SalonSettingController extends AdminController
{
    public function edit(): View
    {
        $setting = SalonSetting::current()->load('heroImages');

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
            'hero_label' => ['nullable', 'string', 'max:255'],
            'hero_title' => ['nullable', 'string'],
            'concept_title' => ['nullable', 'string', 'max:255'],
            'concept' => ['nullable', 'string'],
            'address' => ['nullable', 'string', 'max:255'],
            'business_hours' => ['nullable', 'string'],
            'closed_days' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'google_map_url' => ['nullable', 'url', 'max:500'],
            'google_map_embed_url' => ['nullable', 'string', 'url'],
            'instagram_url' => ['nullable', 'url', 'max:500'],
            'hot_pepper_url' => ['nullable', 'url', 'max:500'],
            'hero_images' => ['nullable', 'array'],
            'hero_images.*.sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'hero_images.*.alt_text' => ['nullable', 'string', 'max:255'],
            'hero_images.*.is_published' => ['nullable', 'boolean'],
            'new_hero_images' => ['nullable', 'array'],
            'new_hero_images.*' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'new_hero_meta' => ['nullable', 'array'],
            'new_hero_meta.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'new_hero_meta.*.alt_text' => ['nullable', 'string', 'max:255'],
            'new_hero_meta.*.is_published' => ['nullable', 'boolean'],
        ]);

        $setting = SalonSetting::current();
        $newFiles = collect($request->file('new_hero_images', []))->filter();
        $newMeta = $validated['new_hero_meta'] ?? [];

        $this->assertHeroImageLimit($setting, $newFiles->count());

        $setting->update([
            ...collect($validated)->except(['hero_images', 'new_hero_images', 'new_hero_meta', 'logo_image'])->all(),
            'logo_image' => $this->storeImage($request->file('logo_image'), 'settings/logos', $setting->logo_image),
        ]);

        foreach ($validated['hero_images'] ?? [] as $id => $data) {
            $heroImage = $setting->heroImages()->whereKey($id)->first();
            if (! $heroImage) {
                continue;
            }

            $heroImage->update([
                'sort_order' => (int) $data['sort_order'],
                'alt_text' => $data['alt_text'] ?? null,
                'is_published' => (bool) ($data['is_published'] ?? false),
            ]);
        }

        $nextSort = (int) ($setting->heroImages()->max('sort_order') ?? 0);

        foreach ($newFiles as $key => $file) {
            $meta = $newMeta[$key] ?? [];
            if (array_key_exists('sort_order', $meta) && $meta['sort_order'] !== null && $meta['sort_order'] !== '') {
                $sortOrder = (int) $meta['sort_order'];
                $nextSort = max($nextSort, $sortOrder);
            } else {
                $sortOrder = ++$nextSort;
            }

            $setting->heroImages()->create([
                'image_path' => $this->storeImage($file, 'settings'),
                'alt_text' => $meta['alt_text'] ?? null,
                'sort_order' => $sortOrder,
                'is_published' => array_key_exists('is_published', $meta)
                    ? filter_var($meta['is_published'], FILTER_VALIDATE_BOOLEAN)
                    : true,
            ]);
        }

        return redirect()->route('admin.settings.edit')->with('success', '店舗情報を更新しました。');
    }

    public function destroyHeroImage(HeroImage $heroImage): RedirectResponse
    {
        $setting = SalonSetting::current();

        if ($heroImage->salon_setting_id !== $setting->id) {
            abort(404);
        }

        $this->deleteImage($heroImage->image_path);
        $heroImage->delete();

        return redirect()->route('admin.settings.edit')->with('success', 'メインビジュアル画像を削除しました。');
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

    private function assertHeroImageLimit(SalonSetting $setting, int $newCount): void
    {
        if ($newCount <= 0) {
            return;
        }

        $currentCount = $setting->heroImages()->count();

        if ($currentCount + $newCount > HeroImage::MAX_COUNT) {
            throw ValidationException::withMessages([
                'new_hero_images' => 'メインビジュアル画像は最大'.HeroImage::MAX_COUNT.'枚まで登録できます。（現在'.$currentCount.'枚）',
            ]);
        }
    }
}
