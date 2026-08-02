<?php

namespace App\Http\Controllers\Admin;

use App\Models\News;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class NewsController extends AdminController
{
    public function index(): View
    {
        $newsList = News::query()->ordered()->get();

        return view('admin.news.index', compact('newsList'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'news' => ['nullable', 'array'],
            'news.*.title' => ['required', 'string', 'max:255'],
            'news.*.body' => ['required', 'string'],
            'news.*.published_at' => ['nullable', 'date'],
            'news.*.is_published' => ['nullable', 'in:0,1'],
            'news.*.display_order' => ['nullable', 'integer', 'min:0'],
            'new_news' => ['nullable', 'array'],
            'new_news.*.title' => ['required', 'string', 'max:255'],
            'new_news.*.body' => ['required', 'string'],
            'new_news.*.published_at' => ['nullable', 'date'],
            'new_news.*.is_published' => ['nullable', 'in:0,1'],
            'new_news.*.display_order' => ['nullable', 'integer', 'min:0'],
            'deleted_ids' => ['nullable', 'array'],
            'deleted_ids.*' => ['integer', 'exists:news,id'],
        ]);

        $validated = $validator->validate();

        $existingPayload = $validated['news'] ?? [];
        $newPayload = $validated['new_news'] ?? [];
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

        DB::transaction(function () use ($orderedItems, $deletedIds) {
            if ($deletedIds !== []) {
                News::query()->whereIn('id', $deletedIds)->delete();
            }

            $order = 1;
            foreach ($orderedItems as $item) {
                $data = $item['data'];
                $attrs = $this->newsAttributes($data, $order);

                if ($item['type'] === 'existing') {
                    $news = News::query()->find($item['id']);
                    if (! $news) {
                        continue;
                    }

                    $news->update(array_merge($attrs, [
                        'slug' => $this->uniqueSlug(News::class, $attrs['title'], $news->id),
                    ]));
                } else {
                    News::query()->create(array_merge($attrs, [
                        'slug' => $this->uniqueSlug(News::class, $attrs['title']),
                    ]));
                }

                $order++;
            }
        });

        return redirect()->route('admin.news.index')->with('success', 'お知らせを保存しました。');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function newsAttributes(array $data, int $displayOrder): array
    {
        return [
            'title' => $data['title'],
            'body' => $data['body'],
            'published_at' => $this->nullableDate($data['published_at'] ?? null),
            'is_published' => ($data['is_published'] ?? '0') === '1',
            'display_order' => $displayOrder,
        ];
    }

    private function nullableDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value);
    }
}
