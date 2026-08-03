@extends('layouts.public')

@section('title', $news->title)

@section('content')
    <article class="site-section">
        <div class="mx-auto max-w-3xl px-4 md:px-6">
            <x-back-link :href="route('news.index')">お知らせ一覧へ戻る</x-back-link>
            <header class="mt-6 border-b border-salon-line pb-6">
                <time class="text-sm text-salon-muted">{{ $news->published_at?->format('Y年n月j日') }}</time>
                <h1 class="mt-3 font-serif text-3xl">{{ $news->title }}</h1>
            </header>
            <div class="prose prose-neutral mt-8 max-w-none leading-8 whitespace-pre-line">{{ $news->body }}</div>
        </div>
    </article>
@endsection
