<?php

namespace App\Http\Controllers\Admin;

use App\Models\Banner;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BannerController extends AdminController
{
    private const PENDING_IMAGE_DIRECTORY = 'banners/tmp';

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
            'banners.*.is_published' => ['required', 'in:0,1'],
            'banners.*.published_from' => ['required', 'date'],
            'banners.*.published_until' => ['nullable', 'date'],
            'banners.*.pending_image_path' => ['nullable', 'string', 'max:255'],
            'banners.*.image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'new_banners' => ['nullable', 'array'],
            'new_banners.*.title' => ['required', 'string', 'max:255'],
            'new_banners.*.description' => ['nullable', 'string', 'max:2000'],
            'new_banners.*.alt_text' => ['nullable', 'string', 'max:255'],
            'new_banners.*.link_url' => ['nullable', 'string', 'max:2048', 'url'],
            'new_banners.*.link_target' => ['required', Rule::in(Banner::LINK_TARGETS)],
            'new_banners.*.display_location' => ['required', Rule::in(Banner::LOCATIONS)],
            'new_banners.*.display_order' => ['nullable', 'integer', 'min:0'],
            'new_banners.*.is_published' => ['required', 'in:0,1'],
            'new_banners.*.published_from' => ['required', 'date'],
            'new_banners.*.published_until' => ['nullable', 'date'],
            'new_banners.*.pending_image_path' => ['nullable', 'string', 'max:255'],
            'new_banners.*.image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'deleted_ids' => ['nullable', 'array'],
            'deleted_ids.*' => ['integer', 'exists:banners,id'],
        ], $this->validationMessages());

        $validator->after(function ($validator) use ($request) {
            $newPayload = $request->input('new_banners', []);
            if (is_array($newPayload)) {
                foreach ($newPayload as $key => $data) {
                    if (! is_array($data)) {
                        continue;
                    }

                    if (
                        ! $request->file("new_banners.{$key}.image")
                        && ! $this->isUsablePendingImage($data['pending_image_path'] ?? null)
                    ) {
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

                    $pending = $data['pending_image_path'] ?? null;
                    if (is_string($pending) && $pending !== '' && ! $this->isUsablePendingImage($pending)) {
                        $validator->errors()->add(
                            "{$group}.{$key}.pending_image_path",
                            '画像の一時データが無効です。もう一度選択してください。'
                        );
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

        if ($validator->fails()) {
            // Persist uploads only when validation fails so previews survive the redirect.
            $this->captureUploadedImagesAsPending($request);

            return redirect()
                ->route('admin.home.banners')
                ->withErrors($validator)
                ->withInput($request->input());
        }

        $validated = $validator->validated();

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

                    $attrs['image_path'] = $this->resolveExistingImagePath(
                        $request,
                        $item['id'],
                        $data,
                        $banner->image_path
                    );
                    $banner->update($attrs);
                } else {
                    $imagePath = $this->resolveNewImagePath($request, $item['key'], $data);
                    if (! $imagePath) {
                        continue;
                    }

                    Banner::query()->create(array_merge($attrs, [
                        'image_path' => $imagePath,
                    ]));
                }

                $order++;
            }
        });

        return redirect()->route('admin.home.banners')->with('success', 'キャンペーンを保存しました。');
    }

    /**
     * Store freshly uploaded files under banners/tmp so they survive validation redirects.
     */
    private function captureUploadedImagesAsPending(Request $request): void
    {
        $input = $request->all();

        foreach (['banners', 'new_banners'] as $group) {
            $items = $request->input($group, []);
            if (! is_array($items)) {
                continue;
            }

            foreach ($items as $key => $data) {
                if (! is_array($data)) {
                    continue;
                }

                $file = $request->file("{$group}.{$key}.image");
                if (! $file) {
                    continue;
                }

                $previousPending = is_string($data['pending_image_path'] ?? null)
                    ? $data['pending_image_path']
                    : null;

                $path = $file->store(self::PENDING_IMAGE_DIRECTORY, 'public');
                $input[$group][$key]['pending_image_path'] = $path;

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
    private function resolveExistingImagePath(
        Request $request,
        int|string $id,
        array $data,
        ?string $currentPath
    ): ?string {
        $file = $request->file("banners.{$id}.image");
        $pending = is_string($data['pending_image_path'] ?? null) ? $data['pending_image_path'] : null;

        if ($file) {
            if ($this->isUsablePendingImage($pending)) {
                // Fresh upload was also captured to pending; prefer the UploadedFile and drop pending.
                $this->deleteImage($pending);
            }

            return $this->storeImage($file, 'banners', $currentPath);
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
    private function resolveNewImagePath(Request $request, string $key, array $data): ?string
    {
        $file = $request->file("new_banners.{$key}.image");
        $pending = is_string($data['pending_image_path'] ?? null) ? $data['pending_image_path'] : null;

        if ($file) {
            if ($this->isUsablePendingImage($pending)) {
                $this->deleteImage($pending);
            }

            return $this->storeImage($file, 'banners');
        }

        return $this->promotePendingImage($pending);
    }

    private function promotePendingImage(?string $pendingPath): ?string
    {
        if (! $this->isUsablePendingImage($pendingPath)) {
            return null;
        }

        $extension = pathinfo($pendingPath, PATHINFO_EXTENSION);
        $destination = 'banners/'.uniqid('banner_', true).($extension !== '' ? '.'.$extension : '');

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

        foreach (['banners', 'new_banners'] as $group) {
            $messages["{$group}.*.title.required"] = 'タイトルは必須です。';
            $messages["{$group}.*.published_from.required"] = '公開開始は必須です。';
            $messages["{$group}.*.published_from.date"] = '公開開始の形式が正しくありません。';
            $messages["{$group}.*.is_published.required"] = '公開状態を選択してください。';
            $messages["{$group}.*.is_published.in"] = '公開状態を選択してください。';
            $messages["{$group}.*.image.image"] = '画像ファイルを選択してください。';
            $messages["{$group}.*.image.mimes"] = 'JPEG / PNG / WebP形式の画像を選択してください。';
            $messages["{$group}.*.image.max"] = '画像サイズは5MB以下にしてください。';
        }

        return $messages;
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
