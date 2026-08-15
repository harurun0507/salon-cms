{{-- Vertical-indicator: magazine-style NEWS + featured BLOG (no inner scroll). --}}
@php
    $homeNewsList = collect($newsList ?? [])->take(3)->values();
    $featuredBlog = collect($blogList ?? [])->first();
    $showNewsBlock = $homeNewsList->isNotEmpty();
    $showBlogBlock = $featuredBlog !== null;
    $design = $design ?? \App\Models\DesignSetting::current();
    // Prefer parent home flags; VI always opens detail modals on the top page.
    $useNewsModal = $useNewsModal
        ?? ($design->usesVerticalScrollIndicator() || $design->usesNewsDetailModal());
    $useBlogModal = $useBlogModal
        ?? ($design->usesVerticalScrollIndicator() || $design->usesBlogDetailModal());
    $bothColumns = $showNewsBlock && $showBlogBlock;
    $salon = $setting ?? \App\Models\SalonSetting::current();
@endphp

@if($showNewsBlock || $showBlogBlock)
    <section
        id="{{ $showNewsBlock ? 'news' : 'blog' }}"
        @class([
            'home-vi-news',
            'home-vi-news--split' => $bothColumns,
            'home-vi-news--news-only' => $showNewsBlock && ! $showBlogBlock,
            'home-vi-news--blog-only' => $showBlogBlock && ! $showNewsBlock,
        ])
        aria-label="News and Blog"
    >
        <div class="home-vi-news__stage">
            @if($showNewsBlock)
                <div id="news-list" class="home-vi-news__news">
                    <header class="home-vi-news__head">
                        <p class="home-vi-kicker">News</p>
                        <h2 class="home-vi-news__heading">お知らせ</h2>
                    </header>

                    <ul class="home-vi-news__list">
                        @foreach($homeNewsList as $news)
                            <li>
                                <a
                                    href="{{ route('news.show', $news->slug) }}"
                                    class="home-vi-news__item group"
                                    @if($useNewsModal)
                                        data-news-modal-trigger
                                        data-news-id="{{ $news->id }}"
                                    @endif
                                >
                                    <span class="home-vi-news__item-label">NEWS</span>
                                    <time class="home-vi-news__item-date">
                                        {{ $news->published_at?->format('Y.m.d') }}
                                    </time>
                                    <span class="home-vi-news__item-title">{{ $news->title }}</span>
                                    <span aria-hidden="true" class="home-vi-news__item-arrow">→</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    <div class="home-vi-news__more">
                        <x-section-more-link :href="route('news.index')" class="home-vi-news__more-link">すべて見る →</x-section-more-link>
                    </div>
                </div>
            @endif

            @if($showBlogBlock)
                @php
                    $blogUrl = route('blog.show', $featuredBlog->slug);
                    $hasEyeCatch = $featuredBlog->hasEyeCatch();
                    $hasLogo = filled($salon->logo_image);
                    $shopName = trim((string) $salon->shop_name);
                @endphp
                <div
                    @if($showNewsBlock) id="blog" @endif
                    class="home-vi-news__blog"
                >
                    <header class="home-vi-news__head">
                        <p class="home-vi-kicker">Blog</p>
                        <h2 class="home-vi-news__heading">ブログ</h2>
                    </header>

                    <article
                        class="home-vi-news__feature group"
                        data-home-blog-href="{{ $blogUrl }}"
                    >
                        <a
                            href="{{ $blogUrl }}"
                            class="home-vi-news__feature-hit"
                            tabindex="-1"
                            aria-hidden="true"
                            @if($useBlogModal)
                                data-blog-modal-trigger
                                data-blog-id="{{ $featuredBlog->id }}"
                            @endif
                        ></a>

                        <div class="home-vi-news__visual" aria-hidden="{{ $hasEyeCatch ? 'false' : 'true' }}">
                            @if($hasEyeCatch)
                                <img
                                    src="{{ asset('storage/'.$featuredBlog->eye_catch_image_path) }}"
                                    alt=""
                                    class="home-vi-news__visual-image"
                                    loading="lazy"
                                >
                            @else
                                <div class="home-vi-news__visual-placeholder">
                                    @if($hasLogo)
                                        <img
                                            src="{{ asset('storage/'.$salon->logo_image) }}"
                                            alt=""
                                            class="home-vi-news__visual-placeholder-logo"
                                        >
                                    @elseif($shopName !== '')
                                        <p class="home-vi-news__visual-placeholder-shop">{{ $shopName }}</p>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <div class="home-vi-news__feature-copy">
                            <a
                                href="{{ $blogUrl }}"
                                class="home-vi-news__feature-title-link"
                                @if($useBlogModal)
                                    data-blog-modal-trigger
                                    data-blog-id="{{ $featuredBlog->id }}"
                                @endif
                            >{{ $featuredBlog->title }}</a>
                            <time class="home-vi-news__feature-date">
                                {{ $featuredBlog->published_at?->format('Y.m.d') }}
                            </time>
                        </div>
                    </article>

                    <div class="home-vi-news__more">
                        <x-section-more-link :href="route('blog.index')" class="home-vi-news__more-link">すべて見る →</x-section-more-link>
                    </div>
                </div>
            @endif
        </div>
    </section>
@endif
