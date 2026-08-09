{{-- Combined NEWS & BLOG block for the public home page. --}}
@php
    $homeNewsList = $newsList ?? collect();
    $homeBlogList = $blogList ?? collect();
    $showNewsBlock = $homeNewsList->isNotEmpty();
    $showBlogBlock = $homeBlogList->isNotEmpty();
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
                            <a href="{{ route('blog.show', $blog->slug) }}" class="group block">
                                <div class="relative aspect-[16/10] overflow-hidden rounded-sm bg-salon-line">
                                    @if($blog->hasEyeCatch())
                                        <img
                                            src="{{ asset('storage/'.$blog->eye_catch_image_path) }}"
                                            alt=""
                                            class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]"
                                            loading="lazy"
                                        >
                                    @endif
                                    <span class="absolute left-3 top-3 bg-white/90 px-2.5 py-1 text-[0.65rem] tracking-widest text-salon-text">
                                        BLOG
                                    </span>
                                </div>
                                <div class="mt-3 flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <time class="block text-sm tabular-nums text-salon-muted">
                                            {{ $blog->published_at?->format('Y.m.d') }}
                                        </time>
                                        <p class="mt-1 font-medium leading-relaxed transition group-hover:text-salon-accent">
                                            {{ $blog->title }}
                                        </p>
                                    </div>
                                    <span aria-hidden="true" class="mt-1 shrink-0 text-salon-accent transition group-hover:translate-x-0.5">→</span>
                                </div>
                            </a>
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
