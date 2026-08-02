<?php

namespace App\Http\Controllers\Admin;

use App\Models\HeroImage;
use App\Models\SalonSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class HeroImageController extends AdminController
{
    public function edit(): View
    {
        $setting = SalonSetting::current()->load('heroImages');

        return view('admin.home.hero', compact('setting'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'hero_images' => ['nullable', 'array'],
            'hero_images.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'hero_images.*.alt_text' => ['nullable', 'string', 'max:255'],
            'hero_images.*.is_published' => ['nullable', 'in:0,1'],
            // Future per-image fields (catch_copy, link_url, …) can be validated here.
            'new_hero_images' => ['nullable', 'array'],
            'new_hero_images.*' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'new_hero_meta' => ['nullable', 'array'],
            'new_hero_meta.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'new_hero_meta.*.alt_text' => ['nullable', 'string', 'max:255'],
            'new_hero_meta.*.is_published' => ['nullable', 'in:0,1'],
            'deleted_ids' => ['nullable', 'array'],
            'deleted_ids.*' => ['integer', 'exists:hero_images,id'],
        ]);

        $setting = SalonSetting::current();
        $existingPayload = $validated['hero_images'] ?? [];
        $newMeta = $validated['new_hero_meta'] ?? [];
        $newFiles = collect($request->file('new_hero_images', []))->filter();
        $deletedIds = collect($validated['deleted_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->all();

        $this->assertHeroImageLimit($setting, $newFiles->count(), $deletedIds);

        $orderedItems = [];
        foreach ($existingPayload as $id => $data) {
            if (in_array((int) $id, $deletedIds, true)) {
                continue;
            }
            $orderedItems[] = [
                'type' => 'existing',
                'id' => (int) $id,
                'data' => $data,
                'order' => (int) ($data['sort_order'] ?? 0),
            ];
        }
        foreach ($newFiles as $key => $file) {
            $meta = $newMeta[$key] ?? [];
            $orderedItems[] = [
                'type' => 'new',
                'key' => (string) $key,
                'file' => $file,
                'data' => $meta,
                'order' => (int) ($meta['sort_order'] ?? 0),
            ];
        }

        usort($orderedItems, function (array $a, array $b) {
            if ($a['order'] === $b['order']) {
                return 0;
            }

            return $a['order'] < $b['order'] ? -1 : 1;
        });

        DB::transaction(function () use ($setting, $orderedItems, $deletedIds) {
            if ($deletedIds !== []) {
                $toDelete = $setting->heroImages()->whereIn('id', $deletedIds)->get();
                foreach ($toDelete as $heroImage) {
                    $this->deleteImage($heroImage->image_path);
                    $heroImage->delete();
                }
            }

            $order = 1;
            foreach ($orderedItems as $item) {
                $data = $item['data'];
                $attrs = $this->heroMetaAttributes($data, [
                    'sort_order' => $order,
                    'is_published' => true,
                ]);
                $attrs['sort_order'] = $order;

                if ($item['type'] === 'existing') {
                    $heroImage = $setting->heroImages()->whereKey($item['id'])->first();
                    if (! $heroImage) {
                        continue;
                    }
                    $heroImage->update($attrs);
                } else {
                    $setting->heroImages()->create([
                        'image_path' => $this->storeImage($item['file'], 'settings'),
                        ...$attrs,
                    ]);
                }

                $order++;
            }
        });

        return redirect()->route('admin.home.hero')->with('success', 'メインビジュアルを更新しました。');
    }

    public function destroy(HeroImage $heroImage): RedirectResponse
    {
        $setting = SalonSetting::current();

        if ($heroImage->salon_setting_id !== $setting->id) {
            abort(404);
        }

        $this->deleteImage($heroImage->image_path);
        $heroImage->delete();

        return redirect()->route('admin.home.hero')->with('success', 'メインビジュアル画像を削除しました。');
    }

    /**
     * Map request meta into HeroImage attributes.
     * Defaults apply for new uploads when a key is absent.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    private function heroMetaAttributes(array $data, array $defaults = []): array
    {
        $publishedDefault = array_key_exists('is_published', $defaults)
            ? (bool) $defaults['is_published']
            : false;

        $attributes = [
            'sort_order' => array_key_exists('sort_order', $data) && $data['sort_order'] !== null && $data['sort_order'] !== ''
                ? (int) $data['sort_order']
                : (int) ($defaults['sort_order'] ?? 0),
            'alt_text' => $data['alt_text'] ?? ($defaults['alt_text'] ?? null),
            'is_published' => array_key_exists('is_published', $data)
                ? ((string) $data['is_published'] === '1')
                : $publishedDefault,
        ];

        // Future: merge catch_copy / link_url / meta JSON from $data here.

        return $attributes;
    }

    /**
     * @param  list<int>  $deletedIds
     */
    private function assertHeroImageLimit(SalonSetting $setting, int $newCount, array $deletedIds = []): void
    {
        if ($newCount <= 0) {
            return;
        }

        $currentCount = $setting->heroImages()->count();
        $deletingCount = $deletedIds === []
            ? 0
            : $setting->heroImages()->whereIn('id', $deletedIds)->count();
        $effectiveCount = $currentCount - $deletingCount;

        if ($effectiveCount + $newCount > HeroImage::MAX_COUNT) {
            throw ValidationException::withMessages([
                'new_hero_images' => 'メインビジュアル画像は最大'.HeroImage::MAX_COUNT.'枚まで登録できます。（現在'.$effectiveCount.'枚）',
            ]);
        }
    }
}
