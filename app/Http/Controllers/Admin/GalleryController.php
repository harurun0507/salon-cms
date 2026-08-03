<?php

namespace App\Http\Controllers\Admin;

use App\Models\Gallery;
use App\Models\GalleryImage;
use App\Models\StaffMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GalleryController extends AdminController
{
    public function index(): View
    {
        $galleries = Gallery::query()
            ->with(['images', 'staffMember'])
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();
        $staffMembers = StaffMember::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'name']);

        return view('admin.galleries.index', compact('galleries', 'staffMembers'));
    }

    public function bulkUpdate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'galleries' => ['nullable', 'array'],
            'galleries.*.title' => ['nullable', 'string', 'max:255'],
            'galleries.*.caption' => ['nullable', 'string', 'max:2000'],
            'galleries.*.staff_id' => ['nullable', 'integer', 'exists:staff_members,id'],
            'galleries.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'galleries.*.is_published' => ['nullable', 'in:0,1'],
            'galleries.*.images' => ['nullable', 'array'],
            'galleries.*.images.*.display_order' => ['nullable', 'integer', 'min:0'],
            'galleries.*.images.*.alt_text' => ['nullable', 'string', 'max:255'],
            'galleries.*.images.*.image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'galleries.*.new_images' => ['nullable', 'array'],
            'galleries.*.new_images.*.display_order' => ['nullable', 'integer', 'min:0'],
            'galleries.*.new_images.*.alt_text' => ['nullable', 'string', 'max:255'],
            'galleries.*.new_images.*.image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'galleries.*.deleted_image_ids' => ['nullable', 'array'],
            'galleries.*.deleted_image_ids.*' => ['integer', 'exists:gallery_images,id'],
            'new_galleries' => ['nullable', 'array'],
            'new_galleries.*.title' => ['nullable', 'string', 'max:255'],
            'new_galleries.*.caption' => ['nullable', 'string', 'max:2000'],
            'new_galleries.*.staff_id' => ['nullable', 'integer', 'exists:staff_members,id'],
            'new_galleries.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'new_galleries.*.is_published' => ['nullable', 'in:0,1'],
            'new_galleries.*.new_images' => ['nullable', 'array'],
            'new_galleries.*.new_images.*.display_order' => ['nullable', 'integer', 'min:0'],
            'new_galleries.*.new_images.*.alt_text' => ['nullable', 'string', 'max:255'],
            'new_galleries.*.new_images.*.image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'deleted_ids' => ['nullable', 'array'],
            'deleted_ids.*' => ['integer', 'exists:galleries,id'],
        ]);

        $existingPayload = $validated['galleries'] ?? [];
        $newPayload = $validated['new_galleries'] ?? [];
        $deletedIds = collect($validated['deleted_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->all();

        foreach ($newPayload as $key => $data) {
            $imageCount = $this->countIncomingImages($request, "new_galleries.{$key}", $data, []);
            if ($imageCount < 1) {
                return back()
                    ->withErrors(["new_galleries.{$key}.new_images" => '画像は1枚以上必要です。'])
                    ->withInput();
            }
            if ($imageCount > Gallery::MAX_IMAGES) {
                return back()
                    ->withErrors(["new_galleries.{$key}.new_images" => '画像は最大'.Gallery::MAX_IMAGES.'枚までです。'])
                    ->withInput();
            }
        }

        foreach ($existingPayload as $id => $data) {
            if (in_array((int) $id, $deletedIds, true)) {
                continue;
            }
            $gallery = Gallery::query()->with('images')->find((int) $id);
            if (! $gallery) {
                continue;
            }
            $deletedImageIds = collect($data['deleted_image_ids'] ?? [])->map(fn ($v) => (int) $v)->all();
            $imageCount = $this->countIncomingImages($request, "galleries.{$id}", $data, $gallery->images->pluck('id')->all(), $deletedImageIds);
            if ($imageCount > Gallery::MAX_IMAGES) {
                return back()
                    ->withErrors(["galleries.{$id}.images" => '画像は最大'.Gallery::MAX_IMAGES.'枚までです。'])
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
                $toDelete = Gallery::query()->with('images')->whereIn('id', $deletedIds)->get();
                foreach ($toDelete as $gallery) {
                    foreach ($gallery->images as $image) {
                        $this->deleteImage($image->image_path);
                    }
                    $gallery->delete();
                }
            }

            $order = 1;
            foreach ($orderedItems as $item) {
                $data = $item['data'];
                $attrs = [
                    'title' => filled($data['title'] ?? null) ? trim((string) $data['title']) : null,
                    'caption' => $data['caption'] ?? null,
                    'staff_id' => filled($data['staff_id'] ?? null) ? (int) $data['staff_id'] : null,
                    'sort_order' => $order,
                    'is_published' => ($data['is_published'] ?? '0') === '1',
                ];

                if ($item['type'] === 'existing') {
                    $gallery = Gallery::query()->with('images')->find($item['id']);
                    if (! $gallery) {
                        continue;
                    }
                    $gallery->update($attrs);
                    $this->syncGalleryImages($request, $gallery, $data, "galleries.{$item['id']}");
                } else {
                    $gallery = Gallery::query()->create($attrs);
                    $this->syncGalleryImages($request, $gallery, $data, "new_galleries.{$item['key']}");
                }

                $order++;
            }
        });

        return redirect()->route('admin.galleries.index')->with('success', 'ギャラリーを一括保存しました。');
    }

    public function create(): View
    {
        $staffMembers = StaffMember::query()->orderBy('sort_order')->orderBy('id')->get(['id', 'name']);

        return view('admin.galleries.create', compact('staffMembers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'title' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:2000'],
            'staff_id' => ['nullable', 'integer', 'exists:staff_members,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['sometimes', 'boolean'],
        ]);

        DB::transaction(function () use ($request, $validated) {
            $gallery = Gallery::create([
                'title' => filled($validated['title'] ?? null) ? trim((string) $validated['title']) : null,
                'caption' => $validated['caption'] ?? null,
                'staff_id' => $validated['staff_id'] ?? null,
                'sort_order' => $validated['sort_order'] ?? 0,
                'is_published' => $request->boolean('is_published', true),
            ]);

            GalleryImage::query()->create([
                'gallery_id' => $gallery->id,
                'image_path' => $this->storeImage($request->file('image'), 'galleries'),
                'alt_text' => $validated['title'] ?? $validated['caption'] ?? null,
                'display_order' => 1,
            ]);
        });

        return redirect()->route('admin.galleries.index')->with('success', 'ギャラリー画像を登録しました。');
    }

    public function edit(Gallery $gallery): View
    {
        $gallery->load('images');
        $staffMembers = StaffMember::query()->orderBy('sort_order')->orderBy('id')->get(['id', 'name']);

        return view('admin.galleries.edit', compact('gallery', 'staffMembers'));
    }

    public function update(Request $request, Gallery $gallery): RedirectResponse
    {
        $validated = $request->validate([
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'title' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:2000'],
            'staff_id' => ['nullable', 'integer', 'exists:staff_members,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['sometimes', 'boolean'],
        ]);

        DB::transaction(function () use ($request, $gallery, $validated) {
            $gallery->update([
                'title' => filled($validated['title'] ?? null) ? trim((string) $validated['title']) : null,
                'caption' => $validated['caption'] ?? null,
                'staff_id' => $validated['staff_id'] ?? null,
                'sort_order' => $validated['sort_order'] ?? 0,
                'is_published' => $request->boolean('is_published'),
            ]);

            $imageFile = $request->file('image');
            if ($imageFile) {
                $cover = $gallery->images()->orderBy('display_order')->orderBy('id')->first();
                if ($cover) {
                    $cover->update([
                        'image_path' => $this->storeImage($imageFile, 'galleries', $cover->image_path),
                        'alt_text' => $validated['title'] ?? $validated['caption'] ?? $cover->alt_text,
                    ]);
                } else {
                    GalleryImage::query()->create([
                        'gallery_id' => $gallery->id,
                        'image_path' => $this->storeImage($imageFile, 'galleries'),
                        'alt_text' => $validated['title'] ?? $validated['caption'] ?? null,
                        'display_order' => 1,
                    ]);
                }
            }
        });

        return redirect()->route('admin.galleries.index')->with('success', 'ギャラリー画像を更新しました。');
    }

    public function destroy(Gallery $gallery): RedirectResponse
    {
        $gallery->load('images');
        foreach ($gallery->images as $image) {
            $this->deleteImage($image->image_path);
        }
        $gallery->delete();

        return redirect()->route('admin.galleries.index')->with('success', 'ギャラリー画像を削除しました。');
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<int>  $existingImageIds
     * @param  list<int>  $deletedImageIds
     */
    private function countIncomingImages(
        Request $request,
        string $prefix,
        array $data,
        array $existingImageIds,
        array $deletedImageIds = []
    ): int {
        $kept = collect($existingImageIds)
            ->reject(fn (int $id) => in_array($id, $deletedImageIds, true))
            ->filter(fn (int $id) => array_key_exists((string) $id, $data['images'] ?? []) || array_key_exists($id, $data['images'] ?? []))
            ->count();

        // Existing images present in payload (even without file).
        if (($data['images'] ?? []) !== []) {
            $kept = collect($data['images'] ?? [])
                ->keys()
                ->map(fn ($id) => (int) $id)
                ->reject(fn (int $id) => in_array($id, $deletedImageIds, true))
                ->count();
        } else {
            $kept = collect($existingImageIds)
                ->reject(fn (int $id) => in_array($id, $deletedImageIds, true))
                ->count();
        }

        $newCount = 0;
        foreach (array_keys($data['new_images'] ?? []) as $key) {
            $file = $request->file("{$prefix}.new_images.{$key}.image");
            if ($file instanceof UploadedFile) {
                $newCount++;
            }
        }

        return $kept + $newCount;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncGalleryImages(Request $request, Gallery $gallery, array $data, string $prefix): void
    {
        $deletedImageIds = collect($data['deleted_image_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->all();
        if ($deletedImageIds !== []) {
            $toDelete = $gallery->images()->whereIn('id', $deletedImageIds)->get();
            foreach ($toDelete as $image) {
                $this->deleteImage($image->image_path);
                $image->delete();
            }
        }

        $ordered = [];
        foreach ($data['images'] ?? [] as $imageId => $imageData) {
            if (in_array((int) $imageId, $deletedImageIds, true)) {
                continue;
            }
            $ordered[] = [
                'type' => 'existing',
                'id' => (int) $imageId,
                'data' => $imageData,
                'order' => (int) ($imageData['display_order'] ?? 0),
            ];
        }
        foreach ($data['new_images'] ?? [] as $key => $imageData) {
            $file = $request->file("{$prefix}.new_images.{$key}.image");
            if (! $file instanceof UploadedFile) {
                continue;
            }
            $ordered[] = [
                'type' => 'new',
                'key' => (string) $key,
                'data' => $imageData,
                'file' => $file,
                'order' => (int) ($imageData['display_order'] ?? 0),
            ];
        }

        usort($ordered, function (array $a, array $b) {
            if ($a['order'] === $b['order']) {
                return 0;
            }

            return $a['order'] < $b['order'] ? -1 : 1;
        });

        $displayOrder = 1;
        foreach ($ordered as $item) {
            if ($displayOrder > Gallery::MAX_IMAGES) {
                break;
            }

            if ($item['type'] === 'existing') {
                $image = $gallery->images()->whereKey($item['id'])->first();
                if (! $image) {
                    continue;
                }
                $replace = $request->file("{$prefix}.images.{$item['id']}.image");
                $attrs = [
                    'alt_text' => $item['data']['alt_text'] ?? null,
                    'display_order' => $displayOrder,
                ];
                if ($replace instanceof UploadedFile) {
                    $attrs['image_path'] = $this->storeImage($replace, 'galleries', $image->image_path);
                }
                $image->update($attrs);
            } else {
                GalleryImage::query()->create([
                    'gallery_id' => $gallery->id,
                    'image_path' => $this->storeImage($item['file'], 'galleries'),
                    'alt_text' => $item['data']['alt_text'] ?? null,
                    'display_order' => $displayOrder,
                ]);
            }

            $displayOrder++;
        }

        // Re-number any leftover images not in payload (keep them after ordered ones).
        $handledIds = collect($ordered)
            ->where('type', 'existing')
            ->pluck('id')
            ->all();
        $leftovers = $gallery->images()
            ->whereNotIn('id', array_merge($handledIds, $deletedImageIds))
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
        foreach ($leftovers as $image) {
            if ($displayOrder > Gallery::MAX_IMAGES) {
                $this->deleteImage($image->image_path);
                $image->delete();

                continue;
            }
            $image->update(['display_order' => $displayOrder]);
            $displayOrder++;
        }
    }
}
