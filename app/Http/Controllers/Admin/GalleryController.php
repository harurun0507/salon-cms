<?php

namespace App\Http\Controllers\Admin;

use App\Models\Gallery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GalleryController extends AdminController
{
    public function index(): View
    {
        $galleries = Gallery::query()->orderBy('sort_order')->orderByDesc('id')->get();

        return view('admin.galleries.index', compact('galleries'));
    }

    public function bulkUpdate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'galleries' => ['nullable', 'array'],
            'galleries.*.caption' => ['nullable', 'string', 'max:255'],
            'galleries.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'galleries.*.is_published' => ['nullable', 'in:0,1'],
            'galleries.*.image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'new_galleries' => ['nullable', 'array'],
            'new_galleries.*.caption' => ['nullable', 'string', 'max:255'],
            'new_galleries.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'new_galleries.*.is_published' => ['nullable', 'in:0,1'],
            'new_galleries.*.image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'deleted_ids' => ['nullable', 'array'],
            'deleted_ids.*' => ['integer', 'exists:galleries,id'],
        ]);

        $existingPayload = $validated['galleries'] ?? [];
        $newPayload = $validated['new_galleries'] ?? [];
        $deletedIds = collect($validated['deleted_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->all();

        foreach ($newPayload as $key => $data) {
            $file = $request->file("new_galleries.{$key}.image");
            if (! $file) {
                return back()
                    ->withErrors(["new_galleries.{$key}.image" => '画像は必須です。'])
                    ->withInput();
            }
        }

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
        foreach ($newPayload as $key => $data) {
            $orderedItems[] = [
                'type' => 'new',
                'key' => (string) $key,
                'data' => $data,
                'order' => (int) ($data['sort_order'] ?? 0),
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
                $toDelete = Gallery::query()->whereIn('id', $deletedIds)->get();
                foreach ($toDelete as $gallery) {
                    $this->deleteImage($gallery->image_path);
                    $gallery->delete();
                }
            }

            $order = 1;
            foreach ($orderedItems as $item) {
                $data = $item['data'];
                $attrs = [
                    'caption' => $data['caption'] ?? null,
                    'sort_order' => $order,
                    'is_published' => ($data['is_published'] ?? '0') === '1',
                ];

                if ($item['type'] === 'existing') {
                    $gallery = Gallery::query()->find($item['id']);
                    if (! $gallery) {
                        continue;
                    }

                    $imageFile = $request->file("galleries.{$item['id']}.image");
                    $attrs['image_path'] = $this->storeImage($imageFile, 'galleries', $gallery->image_path);
                    $gallery->update($attrs);
                } else {
                    $imageFile = $request->file("new_galleries.{$item['key']}.image");
                    if (! $imageFile) {
                        continue;
                    }

                    Gallery::query()->create(array_merge($attrs, [
                        'image_path' => $this->storeImage($imageFile, 'galleries'),
                    ]));
                }

                $order++;
            }
        });

        return redirect()->route('admin.galleries.index')->with('success', 'ギャラリーを一括保存しました。');
    }

    public function create(): View
    {
        return view('admin.galleries.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'caption' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['sometimes', 'boolean'],
        ]);

        Gallery::create([
            'image_path' => $this->storeImage($request->file('image'), 'galleries'),
            'caption' => $validated['caption'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_published' => $request->boolean('is_published', true),
        ]);

        return redirect()->route('admin.galleries.index')->with('success', 'ギャラリー画像を登録しました。');
    }

    public function edit(Gallery $gallery): View
    {
        return view('admin.galleries.edit', compact('gallery'));
    }

    public function update(Request $request, Gallery $gallery): RedirectResponse
    {
        $validated = $request->validate([
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'caption' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['sometimes', 'boolean'],
        ]);

        $gallery->update([
            'image_path' => $this->storeImage($request->file('image'), 'galleries', $gallery->image_path),
            'caption' => $validated['caption'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_published' => $request->boolean('is_published'),
        ]);

        return redirect()->route('admin.galleries.index')->with('success', 'ギャラリー画像を更新しました。');
    }

    public function destroy(Gallery $gallery): RedirectResponse
    {
        $this->deleteImage($gallery->image_path);
        $gallery->delete();

        return redirect()->route('admin.galleries.index')->with('success', 'ギャラリー画像を削除しました。');
    }
}
