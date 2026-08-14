@extends('layouts.public')

@section('title', $news->title)

@section('content')
    @php
        $weekdayLabels = ['日', '月', '火', '水', '木', '金', '土'];
        $holidaySentence = $news->isHolidayAnnouncement()
            ? \App\Models\SalonSetting::current()->closedDaysAnnouncementSentence()
            : null;
        $showTemporaryDates = $news->isTemporaryClosureAnnouncement() && $news->closedDates->isNotEmpty();
    @endphp

    <article class="site-section">
        <div class="mx-auto max-w-3xl px-4 md:px-6">
            <x-back-link :href="route('news.index')">お知らせ一覧へ戻る</x-back-link>
            <header class="news-detail-header mt-6 border-b border-salon-line pb-6">
                <div class="news-detail-meta">
                    <time class="news-detail-date">
                        {{ $news->published_at?->format('Y年n月j日') }}
                    </time>
                    <span class="news-category-badge">
                        <svg class="news-category-badge-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                            <rect x="2.25" y="3.25" width="11.5" height="10.5" rx="1.5" stroke="currentColor" stroke-width="1.25"/>
                            <path d="M2.25 6.5h11.5M5.25 2.25v2M10.75 2.25v2" stroke="currentColor" stroke-width="1.25" stroke-linecap="round"/>
                        </svg>
                        {{ $news->categoryLabel() }}
                    </span>
                </div>
                <h1 class="mt-5 font-serif text-2xl leading-snug text-salon-text sm:mt-6">
                    {{ $news->title }}
                </h1>
            </header>

            @if ($holidaySentence)
                <section class="news-closed-dates border-b border-salon-line py-8 md:py-9">
                    <p class="text-[0.95rem] leading-relaxed text-salon-text md:text-base">{{ $holidaySentence }}</p>
                </section>
            @elseif ($showTemporaryDates)
                <section class="news-closed-dates border-b border-salon-line py-8 md:py-9">
                    <p class="text-sm leading-relaxed text-salon-muted">以下の日程は臨時休業となります。</p>
                    <ul class="mt-5 grid grid-cols-1 gap-y-2.5 sm:grid-cols-2 sm:gap-x-10 sm:gap-y-3">
                        @foreach ($news->closedDates as $closedDate)
                            <li class="text-[0.95rem] leading-relaxed text-salon-text md:text-base">
                                {{ $closedDate->closed_date->format('n月j日') }}（{{ $weekdayLabels[$closedDate->closed_date->dayOfWeek] }}）
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if (filled($news->body))
                <div class="prose prose-neutral mt-8 max-w-none leading-8 whitespace-pre-line">{{ $news->body }}</div>
            @endif
        </div>
    </article>

    <style>
        .news-detail-meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            column-gap: 2rem;
            row-gap: 0.625rem;
        }

        @media (min-width: 640px) {
            .news-detail-meta {
                column-gap: 2.5rem;
            }
        }

        .news-detail-date {
            flex-shrink: 0;
            font-size: 0.875rem;
            line-height: 1.25;
            letter-spacing: 0.04em;
            font-variant-numeric: tabular-nums;
            color: var(--color-salon-muted, #6B635C);
        }

        .news-category-badge {
            display: inline-flex;
            flex-shrink: 0;
            align-items: center;
            gap: 0.35rem;
            min-height: 1.75rem;
            padding: 0.35rem 0.85rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            line-height: 1;
            letter-spacing: 0.04em;
            font-weight: 500;
            background: color-mix(in srgb, var(--site-primary, #5F6F52) 22%, #F3EFE7);
            color: color-mix(in srgb, var(--site-primary, #5F6F52) 82%, #1f241c);
        }

        .news-category-badge-icon {
            width: 0.85rem;
            height: 0.85rem;
            flex-shrink: 0;
            opacity: 0.85;
        }
    </style>
@endsection
