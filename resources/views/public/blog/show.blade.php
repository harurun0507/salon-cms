@extends('layouts.public')

@section('title', $blog->title)

@section('content')
    <article class="site-section">
        <div class="mx-auto max-w-3xl px-4 md:px-6">
            <x-back-link :href="route('blog.index')">ブログ一覧へ戻る</x-back-link>

            <header class="mt-6 border-b border-salon-line pb-6">
                <p class="text-xs tracking-widest text-salon-accent">BLOG</p>
                <time class="mt-2 block text-sm text-salon-muted">{{ $blog->published_at?->format('Y年n月j日') }}</time>
                <h1 class="mt-3 font-serif text-3xl">{{ $blog->title }}</h1>
            </header>

            @if($blog->hasEyeCatch())
                <div class="mt-8 overflow-hidden rounded-sm">
                    <img
                        src="{{ asset('storage/'.$blog->eye_catch_image_path) }}"
                        alt=""
                        class="w-full object-cover"
                    >
                </div>
            @endif

            <div class="prose prose-neutral mt-8 max-w-none leading-8 whitespace-pre-line">{{ $blog->renderedBody() }}</div>
        </div>
    </article>
@endsection
