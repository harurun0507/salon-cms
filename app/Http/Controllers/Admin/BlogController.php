<?php

namespace App\Http\Controllers\Admin;

use App\Models\Blog;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class BlogController extends AdminController
{
    private const PENDING_IMAGE_DIRECTORY = 'blogs/tmp';

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
            'blogs.*.is_published' => ['required', 'in:0,1'],
            'blogs.*.display_order' => ['nullable', 'integer', 'min:0'],
            'blogs.*.pending_image_path' => ['nullable', 'string', 'max:255'],
            'blogs.*.eye_catch' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'blogs.*.remove_eye_catch' => ['nullable', 'in:0,1'],
            'new_blogs' => ['nullable', 'array'],
            'new_blogs.*.title' => ['required', 'string', 'max:255'],
            'new_blogs.*.body' => ['required', 'string'],
            'new_blogs.*.published_at' => ['required', 'date'],
            'new_blogs.*.is_published' => ['required', 'in:0,1'],
            'new_blogs.*.display_order' => ['nullable', 'integer', 'min:0'],
            'new_blogs.*.pending_image_path' => ['nullable', 'string', 'max:255'],
            'new_blogs.*.eye_catch' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'deleted_ids' => ['nullable', 'array'],
            'deleted_ids.*' => ['integer', 'exists:blogs,id'],
        ], $this->validationMessages());

        $validator->after(function ($validator) use ($request) {
            foreach (['blogs', 'new_blogs'] as $group) {
                $payload = $request->input($group, []);
                if (! is_array($payload)) {
                    continue;
                }

                foreach ($payload as $key => $data) {
                    if (! is_array($data)) {
                        continue;
                    }

                    $pending = $data['pending_image_path'] ?? null;
                    if (is_string($pending) && $pending !== '' && ! $this->isUsablePendingImage($pending)) {
                        $validator->errors()->add(
                            "{$group}.{$key}.pending_image_path",
                            '画像の一時データが無効です。もう一度選択してください。'
                        );
                    }
                }
            }
        });

        if ($validator->fails()) {
            // Persist uploads only when validation fails so previews survive the redirect.
            $this->captureUploadedImagesAsPending($request);

            return redirect()
                ->route('admin.blog.index')
                ->withErrors($validator)
                ->withInput($request->input());
        }

        $validated = $validator->validated();

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

                    $attrs['eye_catch_image_path'] = $this->resolveExistingEyeCatchPath(
                        $request,
                        $item['id'],
                        $data,
                        $blog->eye_catch_image_path
                    );

                    $blog->update(array_merge($attrs, [
                        'slug' => $this->uniqueSlug(Blog::class, $attrs['title'], $blog->id),
                    ]));
                } else {
                    Blog::query()->create(array_merge($attrs, [
                        'slug' => $this->uniqueSlug(Blog::class, $attrs['title']),
                        'eye_catch_image_path' => $this->resolveNewEyeCatchPath($request, $item['key'], $data),
                        'body_format' => Blog::BODY_FORMAT_PLAIN,
                    ]));
                }

                $order++;
            }
        });

        return redirect()->route('admin.blog.index')->with('success', 'ブログを保存しました。');
    }

    /**
     * Store freshly uploaded files under blogs/tmp so they survive validation redirects.
     */
    private function captureUploadedImagesAsPending(Request $request): void
    {
        $input = $request->all();

        foreach (['blogs', 'new_blogs'] as $group) {
            $items = $request->input($group, []);
            if (! is_array($items)) {
                continue;
            }

            foreach ($items as $key => $data) {
                if (! is_array($data)) {
                    continue;
                }

                $file = $request->file("{$group}.{$key}.eye_catch");
                if (! $file) {
                    continue;
                }

                $previousPending = is_string($data['pending_image_path'] ?? null)
                    ? $data['pending_image_path']
                    : null;

                $path = $file->store(self::PENDING_IMAGE_DIRECTORY, 'public');
                $input[$group][$key]['pending_image_path'] = $path;
                $input[$group][$key]['remove_eye_catch'] = '0';

                if (
                    $this->isUsablePendingImage($previousPending)
                    && $previousPending !== $path
                ) {
                    $this->deleteImage($previousPending);
                }
            }
        }

        $request->merge($input);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveExistingEyeCatchPath(
        Request $request,
        int|string $id,
        array $data,
        ?string $currentPath
    ): ?string {
        $file = $request->file("blogs.{$id}.eye_catch");
        $pending = is_string($data['pending_image_path'] ?? null) ? $data['pending_image_path'] : null;
        $removeEyeCatch = ($data['remove_eye_catch'] ?? '0') === '1';

        if ($file) {
            if ($this->isUsablePendingImage($pending)) {
                $this->deleteImage($pending);
            }

            return $this->storeImage($file, 'blogs', $currentPath);
        }

        if ($removeEyeCatch) {
            if ($this->isUsablePendingImage($pending)) {
                $this->deleteImage($pending);
            }
            $this->deleteImage($currentPath);

            return null;
        }

        if ($this->isUsablePendingImage($pending)) {
            $promoted = $this->promotePendingImage($pending);
            if ($promoted && $currentPath && $currentPath !== $promoted) {
                $this->deleteImage($currentPath);
            }

            return $promoted ?? $currentPath;
        }

        return $currentPath;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveNewEyeCatchPath(Request $request, string $key, array $data): ?string
    {
        $file = $request->file("new_blogs.{$key}.eye_catch");
        $pending = is_string($data['pending_image_path'] ?? null) ? $data['pending_image_path'] : null;

        if ($file) {
            if ($this->isUsablePendingImage($pending)) {
                $this->deleteImage($pending);
            }

            return $this->storeImage($file, 'blogs');
        }

        return $this->promotePendingImage($pending);
    }

    private function promotePendingImage(?string $pendingPath): ?string
    {
        if (! $this->isUsablePendingImage($pendingPath)) {
            return null;
        }

        $extension = pathinfo($pendingPath, PATHINFO_EXTENSION);
        $destination = 'blogs/'.uniqid('blog_', true).($extension !== '' ? '.'.$extension : '');

        Storage::disk('public')->move($pendingPath, $destination);

        return $destination;
    }

    private function isUsablePendingImage(mixed $path): bool
    {
        if (! is_string($path) || $path === '') {
            return false;
        }

        if (! str_starts_with($path, self::PENDING_IMAGE_DIRECTORY.'/')) {
            return false;
        }

        if (str_contains($path, '..')) {
            return false;
        }

        return Storage::disk('public')->exists($path);
    }

    /**
     * @return array<string, string>
     */
    private function validationMessages(): array
    {
        $messages = [];

        foreach (['blogs', 'new_blogs'] as $group) {
            $messages["{$group}.*.title.required"] = 'タイトルは必須です。';
            $messages["{$group}.*.body.required"] = '本文は必須です。';
            $messages["{$group}.*.published_at.required"] = '投稿日時は必須です。';
            $messages["{$group}.*.published_at.date"] = '投稿日時の形式が正しくありません。';
            $messages["{$group}.*.is_published.required"] = '公開状態を選択してください。';
            $messages["{$group}.*.is_published.in"] = '公開状態を選択してください。';
            $messages["{$group}.*.eye_catch.image"] = '画像ファイルを選択してください。';
            $messages["{$group}.*.eye_catch.mimes"] = 'JPEG / PNG / WebP形式の画像を選択してください。';
            $messages["{$group}.*.eye_catch.max"] = '画像サイズは5MB以下にしてください。';
        }

        return $messages;
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
