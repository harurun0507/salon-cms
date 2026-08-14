{{-- Combined NEWS & BLOG block for the public home page. --}}
@php
    $homeNewsList = $newsList ?? collect();
    $homeBlogList = $blogList ?? collect();
    $showNewsBlock = $homeNewsList->isNotEmpty();
    $showBlogBlock = $homeBlogList->isNotEmpty();
    $design = $design ?? \App\Models\DesignSetting::current();
    $useNewsModal = $design->usesNewsDetailModal();
    $useBlogModal = $design->usesBlogDetailModal();
@endphp

@if($showNewsBlock || $showBlogBlock)
    <section id="{{ $showNewsBlock ? 'news' : 'blog' }}" class="site-section">
        <div class="mx-auto max-w-6xl px-4 md:px-6">
            <div class="mb-10 text-center md:mb-12">
                <p class="mb-2 text-sm tracking-widest text-salon-accent">NEWS & BLOG</p>
                <h2 class="section-title">ニュース・ブログ</h2>
            </div>

            @if($showNewsBlock)
                <div id="news-list" class="mx-auto max-w-4xl">
                    <ul class="divide-y divide-salon-line border-y border-salon-line">
                        @foreach($homeNewsList as $news)
                            <li>
                                <a
                                    href="{{ route('news.show', $news->slug) }}"
                                    class="group flex flex-col gap-2 py-4 transition hover:text-salon-accent sm:flex-row sm:items-center sm:gap-6"
                                    @if($useNewsModal)
                                        data-news-modal-trigger
                                        data-news-id="{{ $news->id }}"
                                    @endif
                                >
                                    <span class="shrink-0 text-xs tracking-widest text-salon-accent">NEWS</span>
                                    <time class="shrink-0 text-sm tabular-nums text-salon-muted sm:w-24">
                                        {{ $news->published_at?->format('Y.m.d') }}
                                    </time>
                                    <span class="min-w-0 flex-1 font-medium leading-relaxed">{{ $news->title }}</span>
                                    <span aria-hidden="true" class="hidden shrink-0 text-salon-accent transition group-hover:translate-x-0.5 sm:inline">→</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    <div class="mt-10 text-center">
                        <x-section-more-link :href="route('news.index')">すべて見る →</x-section-more-link>
                    </div>
                </div>
            @endif

            @if($showBlogBlock)
                <div
                    @if($showNewsBlock) id="blog" @endif
                    class="{{ $showNewsBlock ? 'home-blog-after-news' : '' }}"
                >
                    <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
                        @foreach($homeBlogList as $blog)
                            @php
                                $blogUrl = route('blog.show', $blog->slug);
                            @endphp
                            <article
                                class="home-blog-card group"
                                data-home-blog-href="{{ $blogUrl }}"
                            >
                                <a
                                    href="{{ $blogUrl }}"
                                    class="home-blog-card-hit"
                                    tabindex="-1"
                                    aria-hidden="true"
                                    @if($useBlogModal)
                                        data-blog-modal-trigger
                                        data-blog-id="{{ $blog->id }}"
                                    @endif
                                ></a>
                                <a
                                    href="{{ $blogUrl }}"
                                    class="home-blog-card-more"
                                    aria-label="{{ $blog->title }}の詳細を見る"
                                    @if($useBlogModal)
                                        data-blog-modal-trigger
                                        data-blog-id="{{ $blog->id }}"
                                    @endif
                                >
                                    <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                        <path d="M7.5 4.5 13 10l-5.5 5.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </a>
                                <div class="home-blog-card-body">
                                    <x-public.blog-eyecatch :blog="$blog" />
                                    <p class="blog-card-title mt-3">{{ $blog->title }}</p>
                                    <time class="blog-card-date mt-1.5 block">
                                        {{ $blog->published_at?->format('Y.m.d') }}
                                    </time>
                                </div>
                            </article>
                        @endforeach
                    </div>
                    <div class="mt-10 text-center">
                        <x-section-more-link :href="route('blog.index')">すべて見る →</x-section-more-link>
                    </div>
                </div>
            @endif
        </div>
    </section>

    @if($showNewsBlock && $showBlogBlock)
        <style>
            .home-blog-after-news {
                margin-top: 1.75rem; /* 28px */
            }

            @media (min-width: 768px) {
                .home-blog-after-news {
                    margin-top: 3.25rem; /* 52px */
                }
            }
        </style>
    @endif
@endif
