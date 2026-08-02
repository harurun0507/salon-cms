@extends('layouts.public')

@section('title', 'お知らせ')

@section('content')
    <section class="site-section">
        <div class="mx-auto max-w-3xl px-4 md:px-6">
            <p class="mb-2 text-sm tracking-widest text-salon-accent">News</p>
            <h1 class="section-title mb-12">お知らせ</h1>

            <ul class="divide-y divide-salon-line">
                @forelse($newsList as $news)
                    <li>
                        <a href="{{ route('news.show', $news->slug) }}" class="flex flex-col gap-2 py-5 transition hover:text-salon-accent md:flex-row md:items-center md:justify-between">
                            <span>{{ $news->title }}</span>
                            <time class="text-sm text-salon-muted">{{ $news->published_at?->format('Y.m.d') }}</time>
                        </a>
                    </li>
                @empty
                    <li class="py-8 text-salon-muted">お知らせはありません。</li>
                @endforelse
            </ul>

            <div class="mt-8">{{ $newsList->links() }}</div>
        </div>
    </section>
@endsection
