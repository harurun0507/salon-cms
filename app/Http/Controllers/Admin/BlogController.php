<?php

namespace App\Http\Controllers\Admin;

use App\Models\Blog;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class BlogController extends AdminController
{
    public function index(): View
    {
        $blogs = Blog::query()->ordered()->get();

        return view('admin.blog.index', compact('blogs'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'blogs' => ['nullable', 'array'],
            'blogs.*.title' => ['required', 'string', 'max:255'],
            'blogs.*.body' => ['required', 'string'],
            'blogs.*.published_at' => ['required', 'date'],
            'blogs.*.is_published' => ['nullable', 'in:0,1'],
            'blogs.*.display_order' => ['nullable', 'integer', 'min:0'],
            'blogs.*.eye_catch' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'blogs.*.remove_eye_catch' => ['nullable', 'in:0,1'],
            'new_blogs' => ['nullable', 'array'],
            'new_blogs.*.title' => ['required', 'string', 'max:255'],
            'new_blogs.*.body' => ['required', 'string'],
            'new_blogs.*.published_at' => ['required', 'date'],
            'new_blogs.*.is_published' => ['nullable', 'in:0,1'],
            'new_blogs.*.display_order' => ['nullable', 'integer', 'min:0'],
            'new_blogs.*.eye_catch' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'deleted_ids' => ['nullable', 'array'],
            'deleted_ids.*' => ['integer', 'exists:blogs,id'],
        ], [
            'blogs.*.published_at.required' => '投稿日は必須です。',
            'new_blogs.*.published_at.required' => '投稿日は必須です。',
        ]);

        $validated = $validator->validate();

        $existingPayload = $validated['blogs'] ?? [];
        $newPayload = $validated['new_blogs'] ?? [];
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
                $toDelete = Blog::query()->whereIn('id', $deletedIds)->get();
                foreach ($toDelete as $blog) {
                    $this->deleteImage($blog->eye_catch_image_path);
                    $blog->delete();
                }
            }

            $order = 1;
            foreach ($orderedItems as $item) {
                $data = $item['data'];
                $attrs = $this->blogAttributes($data, $order);

                if ($item['type'] === 'existing') {
                    $blog = Blog::query()->find($item['id']);
                    if (! $blog) {
                        continue;
                    }

                    $removeEyeCatch = ($data['remove_eye_catch'] ?? '0') === '1';
                    $eyeCatchFile = $request->file("blogs.{$item['id']}.eye_catch");

                    if ($eyeCatchFile) {
                        $attrs['eye_catch_image_path'] = $this->storeImage(
                            $eyeCatchFile,
                            'blogs',
                            $blog->eye_catch_image_path
                        );
                    } elseif ($removeEyeCatch) {
                        $this->deleteImage($blog->eye_catch_image_path);
                        $attrs['eye_catch_image_path'] = null;
                    }

                    $blog->update(array_merge($attrs, [
                        'slug' => $this->uniqueSlug(Blog::class, $attrs['title'], $blog->id),
                    ]));
                } else {
                    $eyeCatchFile = $request->file("new_blogs.{$item['key']}.eye_catch");
                    Blog::query()->create(array_merge($attrs, [
                        'slug' => $this->uniqueSlug(Blog::class, $attrs['title']),
                        'eye_catch_image_path' => $this->storeImage($eyeCatchFile, 'blogs'),
                        'body_format' => Blog::BODY_FORMAT_PLAIN,
                    ]));
                }

                $order++;
            }
        });

        return redirect()->route('admin.blog.index')->with('success', 'ブログを保存しました。');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function blogAttributes(array $data, int $displayOrder): array
    {
        return [
            'title' => $data['title'],
            'body' => $data['body'],
            'published_at' => Carbon::parse($data['published_at']),
            'is_published' => ($data['is_published'] ?? '0') === '1',
            'display_order' => $displayOrder,
            'body_format' => Blog::BODY_FORMAT_PLAIN,
        ];
    }
}
