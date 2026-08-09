@extends('layouts.public')

@section('title', 'ブログ')

@section('content')
    <section class="site-section">
        <div class="mx-auto max-w-6xl px-4 md:px-6">
            <p class="mb-2 text-sm tracking-widest text-salon-accent">Blog</p>
            <h1 class="section-title mb-12">ブログ</h1>

            <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @forelse($blogs as $blog)
                    <a href="{{ route('blog.show', $blog->slug) }}" class="group block">
                        <div class="aspect-[16/10] overflow-hidden rounded-sm bg-salon-line">
                            @if($blog->hasEyeCatch())
                                <img
                                    src="{{ asset('storage/'.$blog->eye_catch_image_path) }}"
                                    alt=""
                                    class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]"
                                    loading="lazy"
                                >
                            @endif
                        </div>
                        <p class="mt-3 text-xs tracking-widest text-salon-accent">BLOG</p>
                        <time class="mt-1 block text-sm tabular-nums text-salon-muted">{{ $blog->published_at?->format('Y.m.d') }}</time>
                        <p class="mt-2 font-medium leading-relaxed transition group-hover:text-salon-accent">{{ $blog->title }}</p>
                    </a>
                @empty
                    <p class="col-span-full text-salon-muted">ブログ記事はまだありません。</p>
                @endforelse
            </div>

            <div class="mt-10">{{ $blogs->links() }}</div>
        </div>
    </section>
@endsection
