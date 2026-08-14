@extends('layouts.public')

@section('title', 'お知らせ')

@section('content')
    @php
        $design = $design ?? \App\Models\DesignSetting::current();
        $useNewsModal = $design->usesNewsDetailModal();
    @endphp
    <section class="site-section">
        <div class="mx-auto max-w-3xl px-4 md:px-6">
            <x-public.list-page-header
                eyebrow="News"
                title="お知らせ"
                :back-href="\App\Models\TopPageSection::listPageBackHref('news')"
            />

            <ul class="divide-y divide-salon-line">
                @forelse($newsList as $news)
                    <li>
                        <a
                            href="{{ route('news.show', $news->slug) }}"
                            class="flex flex-col gap-2 py-5 transition hover:text-salon-accent md:flex-row md:items-center md:justify-between"
                            @if($useNewsModal)
                                data-news-modal-trigger
                                data-news-id="{{ $news->id }}"
                            @endif
                        >
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

    @if($useNewsModal && $newsList->isNotEmpty())
        <x-public.news-modal :news-items="$newsList" />
    @endif
@endsection
