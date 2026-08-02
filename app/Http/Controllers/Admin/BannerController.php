<?php

namespace App\Http\Controllers\Admin;

use App\Models\Banner;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BannerController extends AdminController
{
    public function edit(): View
    {
        $banners = Banner::query()->ordered()->get();

        return view('admin.home.banners', compact('banners'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'banners' => ['nullable', 'array'],
            'banners.*.title' => ['required', 'string', 'max:255'],
            'banners.*.description' => ['nullable', 'string', 'max:2000'],
            'banners.*.alt_text' => ['nullable', 'string', 'max:255'],
            'banners.*.link_url' => ['nullable', 'string', 'max:2048', 'url'],
            'banners.*.link_target' => ['required', Rule::in(Banner::LINK_TARGETS)],
            'banners.*.display_location' => ['required', Rule::in(Banner::LOCATIONS)],
            'banners.*.display_order' => ['nullable', 'integer', 'min:0'],
            'banners.*.is_published' => ['nullable', 'in:0,1'],
            'banners.*.published_from' => ['nullable', 'date'],
            'banners.*.published_until' => ['nullable', 'date'],
            'banners.*.image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'new_banners' => ['nullable', 'array'],
            'new_banners.*.title' => ['required', 'string', 'max:255'],
            'new_banners.*.description' => ['nullable', 'string', 'max:2000'],
            'new_banners.*.alt_text' => ['nullable', 'string', 'max:255'],
            'new_banners.*.link_url' => ['nullable', 'string', 'max:2048', 'url'],
            'new_banners.*.link_target' => ['required', Rule::in(Banner::LINK_TARGETS)],
            'new_banners.*.display_location' => ['required', Rule::in(Banner::LOCATIONS)],
            'new_banners.*.display_order' => ['nullable', 'integer', 'min:0'],
            'new_banners.*.is_published' => ['nullable', 'in:0,1'],
            'new_banners.*.published_from' => ['nullable', 'date'],
            'new_banners.*.published_until' => ['nullable', 'date'],
            'new_banners.*.image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'deleted_ids' => ['nullable', 'array'],
            'deleted_ids.*' => ['integer', 'exists:banners,id'],
        ]);

        $validator->after(function ($validator) use ($request) {
            $newPayload = $request->input('new_banners', []);
            if (is_array($newPayload)) {
                foreach ($newPayload as $key => $data) {
                    if (! $request->file("new_banners.{$key}.image")) {
                        $validator->errors()->add("new_banners.{$key}.image", '画像は必須です。');
                    }
                }
            }

            foreach (['banners', 'new_banners'] as $group) {
                $payload = $request->input($group, []);
                if (! is_array($payload)) {
                    continue;
                }
                foreach ($payload as $key => $data) {
                    if (! is_array($data)) {
                        continue;
                    }
                    try {
                        $from = $this->nullableDate($data['published_from'] ?? null);
                        $until = $this->nullableDate($data['published_until'] ?? null);
                    } catch (\Throwable) {
                        continue;
                    }
                    if ($from && $until && $until->lte($from)) {
                        $validator->errors()->add(
                            "{$group}.{$key}.published_until",
                            '公開終了は公開開始より後の日時にしてください。'
                        );
                    }
                }
            }
        });

        $validated = $validator->validate();

        $existingPayload = $validated['banners'] ?? [];
        $newPayload = $validated['new_banners'] ?? [];
        $deletedIds = collect($validated['deleted_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->all();

        $orderedItems = [];
        foreach ($existingPayload as $id => $data) {
            if (in_array((int) $id, $deletedIds, true)) {
                continue;
            }
            $orderedItems[] = [
                'type' => 'existing',
                'id' => (int) $id,
                'data' => $data,
                'order' => (int) ($data['display_order'] ?? 0),
            ];
        }
        foreach ($newPayload as $key => $data) {
            $orderedItems[] = [
                'type' => 'new',
                'key' => (string) $key,
                'data' => $data,
                'order' => (int) ($data['display_order'] ?? 0),
            ];
        }

        usort($orderedItems, function (array $a, array $b) {
            if ($a['order'] === $b['order']) {
                return 0;
            }

            return $a['order'] < $b['order'] ? -1 : 1;
        });

        DB::transaction(function () use ($request, $orderedItems, $deletedIds) {
            if ($deletedIds !== []) {
                $toDelete = Banner::query()->whereIn('id', $deletedIds)->get();
                foreach ($toDelete as $banner) {
                    $this->deleteImage($banner->image_path);
                    $banner->delete();
                }
            }

            $order = 1;
            foreach ($orderedItems as $item) {
                $data = $item['data'];
                $attrs = $this->bannerAttributes($data, $order);

                if ($item['type'] === 'existing') {
                    $banner = Banner::query()->find($item['id']);
                    if (! $banner) {
                        continue;
                    }

                    $imageFile = $request->file("banners.{$item['id']}.image");
                    $attrs['image_path'] = $this->storeImage($imageFile, 'banners', $banner->image_path);
                    $banner->update($attrs);
                } else {
                    $imageFile = $request->file("new_banners.{$item['key']}.image");
                    if (! $imageFile) {
                        continue;
                    }

                    Banner::query()->create(array_merge($attrs, [
                        'image_path' => $this->storeImage($imageFile, 'banners'),
                    ]));
                }

                $order++;
            }
        });

        return redirect()->route('admin.home.banners')->with('success', 'バナーを保存しました。');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function bannerAttributes(array $data, int $displayOrder): array
    {
        return [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'alt_text' => $data['alt_text'] ?? null,
            'link_url' => $this->nullableString($data['link_url'] ?? null),
            'link_target' => $data['link_target'] ?? '_self',
            'display_location' => $data['display_location'] ?? Banner::LOCATION_TOP,
            'display_order' => $displayOrder,
            'is_published' => ($data['is_published'] ?? '0') === '1',
            'published_from' => $this->nullableDate($data['published_from'] ?? null),
            'published_until' => $this->nullableDate($data['published_until'] ?? null),
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function nullableDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value);
    }
}
