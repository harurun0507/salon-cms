<?php

namespace App\Http\Controllers\Admin;

use App\Models\News;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsController extends AdminController
{
    public function index(): View
    {
        $newsList = News::query()->orderByDesc('published_at')->orderByDesc('id')->paginate(15);

        return view('admin.news.index', compact('newsList'));
    }

    public function create(): View
    {
        return view('admin.news.create');
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'published_at' => ['nullable', 'date'],
            'is_published' => ['sometimes', 'boolean'],
        ]);

        $news = News::create([
            ...$validated,
            'slug' => $this->uniqueSlug(News::class, $validated['title']),
            'is_published' => $request->boolean('is_published'),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'お知らせを登録しました。',
                'news' => [
                    'id' => $news->id,
                    'title' => $news->title,
                    'body' => $news->body,
                    'published_at' => $news->published_at?->format('Y-m-d\TH:i') ?? '',
                    'published_at_display' => $news->published_at?->format('Y/m/d') ?? '-',
                    'is_published' => (bool) $news->is_published,
                    'update_url' => route('admin.news.update', $news),
                    'destroy_url' => route('admin.news.destroy', $news),
                ],
            ]);
        }

        return redirect()->route('admin.news.index')->with('success', 'お知らせを登録しました。');
    }

    public function edit(News $news): View
    {
        return view('admin.news.edit', compact('news'));
    }

    public function update(Request $request, News $news): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'published_at' => ['nullable', 'date'],
            'is_published' => ['sometimes', 'boolean'],
        ]);

        $news->update([
            ...$validated,
            'slug' => $this->uniqueSlug(News::class, $validated['title'], $news->id),
            'is_published' => $request->boolean('is_published'),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'お知らせを更新しました。',
                'news' => [
                    'id' => $news->id,
                    'title' => $news->title,
                    'body' => $news->body,
                    'published_at' => $news->published_at?->format('Y-m-d\TH:i') ?? '',
                    'published_at_display' => $news->published_at?->format('Y/m/d') ?? '-',
                    'is_published' => (bool) $news->is_published,
                ],
            ]);
        }

        return redirect()->route('admin.news.index')->with('success', 'お知らせを更新しました。');
    }

    public function destroy(News $news): RedirectResponse
    {
        $news->delete();

        return redirect()->route('admin.news.index')->with('success', 'お知らせを削除しました。');
    }
}
