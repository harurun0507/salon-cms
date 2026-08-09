<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use Illuminate\View\View;

class BlogPageController extends Controller
{
    public function index(): View
    {
        return view('public.blog.index', [
            'blogs' => Blog::published()->paginate(12),
        ]);
    }

    public function show(string $slug): View
    {
        $blog = Blog::published()->where('slug', $slug)->firstOrFail();

        return view('public.blog.show', compact('blog'));
    }
}
