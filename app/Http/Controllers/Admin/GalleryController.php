<?php

namespace App\Http\Controllers\Admin;

use App\Models\Gallery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GalleryController extends AdminController
{
    public function index(): View
    {
        $galleries = Gallery::query()->orderBy('sort_order')->orderByDesc('id')->paginate(20);

        return view('admin.galleries.index', compact('galleries'));
    }

    public function create(): View
    {
        return view('admin.galleries.create');
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'caption' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['sometimes', 'boolean'],
        ]);

        $gallery = Gallery::create([
            'image_path' => $this->storeImage($request->file('image'), 'galleries'),
            'caption' => $validated['caption'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_published' => $request->boolean('is_published', true),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'ギャラリーを登録しました。',
                'gallery' => [
                    'id' => $gallery->id,
                    'caption' => $gallery->caption,
                    'sort_order' => (int) $gallery->sort_order,
                    'is_published' => (bool) $gallery->is_published,
                    'image_url' => asset('storage/'.$gallery->image_path),
                    'image_path' => $gallery->image_path,
                    'update_url' => route('admin.galleries.update', $gallery),
                    'destroy_url' => route('admin.galleries.destroy', $gallery),
                ],
            ]);
        }

        return redirect()->route('admin.galleries.index')->with('success', 'ギャラリー画像を登録しました。');
    }

    public function edit(Gallery $gallery): View
    {
        return view('admin.galleries.edit', compact('gallery'));
    }

    public function update(Request $request, Gallery $gallery): RedirectResponse|JsonResponse
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

        if ($request->wantsJson()) {
            $gallery->refresh();

            return response()->json([
                'message' => 'ギャラリーを更新しました。',
                'gallery' => [
                    'id' => $gallery->id,
                    'caption' => $gallery->caption,
                    'sort_order' => (int) $gallery->sort_order,
                    'is_published' => (bool) $gallery->is_published,
                    'image_url' => asset('storage/'.$gallery->image_path),
                    'image_path' => $gallery->image_path,
                ],
            ]);
        }

        return redirect()->route('admin.galleries.index')->with('success', 'ギャラリー画像を更新しました。');
    }

    public function destroy(Gallery $gallery): RedirectResponse
    {
        $this->deleteImage($gallery->image_path);
        $gallery->delete();

        return redirect()->route('admin.galleries.index')->with('success', 'ギャラリー画像を削除しました。');
    }
}
