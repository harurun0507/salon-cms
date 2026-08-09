<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\View\View;

class NewsPageController extends Controller
{
    public function index(): View
    {
        return view('public.news.index', [
            'newsList' => News::published()->paginate(10),
        ]);
    }

    public function show(string $slug): View
    {
        $news = News::published()
            ->with(['closedDates', 'closedWeekdays'])
            ->where('slug', $slug)
            ->firstOrFail();

        return view('public.news.show', compact('news'));
    }
}
