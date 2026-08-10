@extends('layouts.public')

@section('title', $setting->shop_name)

@section('content')
    @php
        $heroImages = $heroImages ?? collect();
        $heroCount = $heroImages->count();
        $heroSliderEnabled = $heroCount > 1;
        $topSections = $topSections ?? collect();
    @endphp

    {{-- Hero --}}
    <section class="relative min-h-[70vh] overflow-hidden" id="hero-slider" data-hero-count="{{ $heroCount }}" @if($heroSliderEnabled) data-autoplay="5000" @endif>
        @if($heroCount > 0)
            <div class="absolute inset-0" id="hero-slides" aria-live="polite">
                @foreach($heroImages as $index => $heroImage)
                    <img
                        src="{{ asset('storage/'.$heroImage->image_path) }}"
                        alt="{{ $heroImage->alt_text ?? '' }}"
                        class="hero-slide absolute inset-0 h-full w-full object-cover transition-opacity duration-700 ease-out {{ $index === 0 ? 'opacity-100' : 'opacity-0' }}"
                        data-hero-index="{{ $index }}"
                        @if($index !== 0) aria-hidden="true" @endif
                    >
                @endforeach
            </div>
        @else
            <div class="absolute inset-0 bg-gradient-to-br from-[#E8E4DC] to-[#C5D4BC]"></div>
        @endif

        <div class="absolute inset-0 z-10 bg-black/30 pointer-events-none"></div>

        <div class="relative z-20 mx-auto flex min-h-[70vh] max-w-6xl flex-col justify-center px-4 py-20 text-white md:px-6">
            @if(!empty($setting->hero_label))
                <p class="mb-4 text-sm tracking-[0.3em] uppercase opacity-90">{{ $setting->hero_label }}</p>
            @endif
            @if(!empty($setting->hero_title))
                <h1 class="max-w-2xl font-serif text-4xl leading-relaxed md:text-5xl">{!! nl2br(e($setting->hero_title)) !!}</h1>
            @endif
            <div class="mt-10 flex flex-wrap gap-4">
                @if($setting->hot_pepper_url)
                    <a href="{{ $setting->hot_pepper_url }}" target="_blank" class="btn-primary">空席確認・予約する</a>
                @endif
                <a href="{{ route('menu') }}" class="btn-outline">メニューを見る</a>
            </div>
        </div>

        @if($heroSliderEnabled)
            <div class="hero-slider-controls" data-hero-controls>
                <div id="hero-dots" class="flex items-center gap-2" role="tablist" aria-label="メインビジュアルの位置">
                    @foreach($heroImages as $index => $heroImage)
                        <button
                            type="button"
                            class="hero-dot site-carousel-dot {{ $index === 0 ? 'is-active' : '' }}"
                            data-hero-dot="{{ $index }}"
                            aria-label="画像{{ $index + 1 }}"
                            @if($index === 0) aria-current="true" @endif
                        ></button>
                    @endforeach
                </div>
                <div class="hero-slider-navs">
                    <button type="button" id="hero-prev" class="hero-slider-nav" aria-label="前の画像">
                        <svg class="hero-slider-nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <button type="button" id="hero-next" class="hero-slider-nav" aria-label="次の画像">
                        <svg class="hero-slider-nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </div>
        @endif
    </section>

    @if($heroSliderEnabled)
        <script>
            (function () {
                const root = document.getElementById('hero-slider');
                if (!root) return;

                const slides = Array.from(root.querySelectorAll('.hero-slide'));
                const dots = Array.from(root.querySelectorAll('.hero-dot'));
                const prevBtn = document.getElementById('hero-prev');
                const nextBtn = document.getElementById('hero-next');
                const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                const intervalMs = parseInt(root.dataset.autoplay || '5000', 10);
                let index = 0;
                let timer = null;

                if (reduceMotion) {
                    slides.forEach(function (slide) {
                        slide.classList.remove('duration-700', 'transition-opacity');
                    });
                }

                function show(nextIndex) {
                    index = (nextIndex + slides.length) % slides.length;
                    slides.forEach(function (slide, i) {
                        const active = i === index;
                        slide.classList.toggle('opacity-100', active);
                        slide.classList.toggle('opacity-0', !active);
                        slide.setAttribute('aria-hidden', active ? 'false' : 'true');
                    });
                    dots.forEach(function (dot, i) {
                        const active = i === index;
                        dot.classList.toggle('is-active', active);
                        if (active) {
                            dot.setAttribute('aria-current', 'true');
                        } else {
                            dot.removeAttribute('aria-current');
                        }
                    });
                }

                function startTimer() {
                    stopTimer();
                    if (reduceMotion || slides.length < 2) return;
                    timer = window.setInterval(function () {
                        show(index + 1);
                    }, intervalMs);
                }

                function stopTimer() {
                    if (timer) {
                        window.clearInterval(timer);
                        timer = null;
                    }
                }

                function go(nextIndex) {
                    show(nextIndex);
                    startTimer();
                }

                prevBtn?.addEventListener('click', function () { go(index - 1); });
                nextBtn?.addEventListener('click', function () { go(index + 1); });
                dots.forEach(function (dot) {
                    dot.addEventListener('click', function () {
                        go(parseInt(dot.dataset.heroDot || '0', 10));
                    });
                });

                let touchStartX = null;
                root.addEventListener('touchstart', function (e) {
                    touchStartX = e.changedTouches[0]?.clientX ?? null;
                }, { passive: true });
                root.addEventListener('touchend', function (e) {
                    if (touchStartX === null) return;
                    const delta = (e.changedTouches[0]?.clientX ?? touchStartX) - touchStartX;
                    touchStartX = null;
                    if (Math.abs(delta) < 40) return;
                    go(delta < 0 ? index + 1 : index - 1);
                }, { passive: true });

                startTimer();
            })();
        </script>
    @endif

    {{-- Concept --}}
    <section id="concept" class="site-section">
        <div class="mx-auto max-w-3xl px-4 text-center md:px-6">
            <p class="mb-3 text-sm tracking-widest text-salon-accent">Concept</p>
            @if(!empty($setting->concept_title))
                <h2 class="section-title mb-8">{{ $setting->concept_title }}</h2>
            @endif
            <p class="leading-8 text-salon-muted whitespace-pre-line">{{ $setting->concept }}</p>
        </div>
    </section>

    @foreach($topSections as $section)
        @switch($section->section_key)
            @case('banner')
                @if(($banners ?? collect())->isNotEmpty())
                    <section id="banners" class="site-section">
                        <div class="mx-auto max-w-6xl px-4 md:px-6">
                            <div class="mb-12 text-center">
                                <p class="mb-2 text-sm tracking-widest text-salon-accent">Campaign</p>
                                <h2 class="section-title">キャンペーン</h2>
                            </div>
                            @include('public.partials.campaign-cards', ['banners' => $banners])
                            <div class="mt-10 text-center">
                                <x-section-more-link :href="route('campaign')">すべて見る →</x-section-more-link>
                            </div>
                        </div>
                    </section>
                @endif
                @break

            @case('news')
            @case('blog')
                @php
                    $newsBlogKeys = ($topSections ?? collect())
                        ->pluck('section_key')
                        ->filter(fn ($key) => in_array($key, ['news', 'blog'], true))
                        ->values();
                    $shouldRenderNewsBlog = $newsBlogKeys->first() === $section->section_key;
                @endphp
                @if($shouldRenderNewsBlog)
                    @include('public.partials.home-news-blog')
                @endif
                @break

            @case('menu')
                <section id="menu" class="site-section">
                    <div class="home-menu-section mx-auto max-w-6xl px-4 md:px-6">
                        <div class="mb-12">
                            <p class="mb-2 text-sm tracking-widest text-salon-accent">Menu</p>
                            <h2 class="section-title">メニュー・料金</h2>
                        </div>
                        <div class="home-menu-categories">
                            @foreach($categories as $category)
                                @php
                                    $englishName = $category->englishName();
                                    $menuCategoryUrl = route('menu').'#'.$category->publicAnchorSlug();
                                @endphp
                                <article
                                    @class([
                                        'home-menu-category',
                                        'home-menu-category--combination' => $category->isCombination(),
                                    ])
                                    data-home-menu-href="{{ $menuCategoryUrl }}"
                                >
                                    <a
                                        href="{{ $menuCategoryUrl }}"
                                        class="home-menu-category-hit"
                                        tabindex="-1"
                                        aria-hidden="true"
                                    ></a>
                                    <a
                                        href="{{ $menuCategoryUrl }}"
                                        class="home-menu-category-more"
                                        aria-label="{{ $category->name }}の詳細を見る"
                                    >
                                        <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                            <path d="M7.5 4.5 13 10l-5.5 5.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </a>
                                    <header class="home-menu-category-heading">
                                        @if($englishName)
                                            <p class="home-menu-category-en">{{ $englishName }}</p>
                                        @endif
                                        <h3 class="home-menu-category-title">{{ $category->name }}</h3>
                                    </header>
                                    <ul class="home-menu-list">
                                        @foreach($category->publishedMenus as $menu)
                                            @php
                                                $constituentCategories = $category->isCombination()
                                                    ? $menu->constituentCategoriesForDisplay()
                                                    : collect();
                                            @endphp
                                            <li class="home-menu-item">
                                                @if($constituentCategories->isNotEmpty())
                                                    <ul class="menu-price-tags" aria-label="含まれるカテゴリ">
                                                        @foreach($constituentCategories as $tagCategory)
                                                            <x-public.menu-category-tag :name="$tagCategory->name" />
                                                        @endforeach
                                                    </ul>
                                                @endif
                                                <div class="menu-price-row">
                                                    <span class="menu-price-name">{{ $menu->name }}</span>
                                                    <span class="menu-price-leader" aria-hidden="true"></span>
                                                    @if($menu->isInquiryPrice())
                                                        <span class="menu-price-inquiry">{{ $menu->price }}</span>
                                                    @elseif(filled($menu->price))
                                                        <span class="menu-price-value">{{ $menu->price }}</span>
                                                    @endif
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </article>
                            @endforeach
                        </div>
                        <div class="mt-12 text-center">
                            <x-section-more-link :href="route('menu')">すべて見る →</x-section-more-link>
                        </div>
                    </div>
                </section>
                @break

            @case('gallery')
                @if(($galleries ?? collect())->isNotEmpty())
                    <section id="gallery" class="site-section">
                        <div class="mx-auto max-w-6xl px-4 md:px-6">
                            <div class="mb-12 text-center">
                                <p class="mb-2 text-sm tracking-widest text-salon-accent">Gallery</p>
                                <h2 class="section-title">ヘアギャラリー</h2>
                            </div>
                            <div class="grid grid-cols-2 gap-3 md:grid-cols-3 md:gap-4">
                                @foreach($galleries as $gallery)
                                    @php
                                        $cover = $gallery->coverImagePath();
                                        $coverImage = $gallery->coverImage();
                                        $alt = $coverImage?->alt_text ?: $gallery->displayTitle();
                                        $galleryTitle = trim((string) ($gallery->title ?? ''));
                                        $showGalleryTitle = $galleryTitle !== '';
                                    @endphp
                                    @if($cover)
                                        @php
                                            $galleryUrl = route('gallery.show', $gallery);
                                        @endphp
                                        <article
                                            @class([
                                                'gallery-media-card gallery-media-card--home home-gallery-card group',
                                                'gallery-media-card--home-titled' => $showGalleryTitle,
                                            ])
                                            data-home-gallery-href="{{ $galleryUrl }}"
                                        >
                                            <a
                                                href="{{ $galleryUrl }}"
                                                class="home-gallery-card-hit"
                                                tabindex="-1"
                                                aria-hidden="true"
                                            ></a>
                                            <a
                                                href="{{ $galleryUrl }}"
                                                class="home-gallery-card-more"
                                                aria-label="{{ $gallery->displayTitle() }}の詳細を見る"
                                            >
                                                <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                                    <path d="M7.5 4.5 13 10l-5.5 5.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                            </a>
                                            @if($showGalleryTitle)
                                                <span class="gallery-media-top">
                                                    <span class="gallery-media-tab">
                                                        <span class="gallery-media-tab-label">{{ $galleryTitle }}</span>
                                                    </span>
                                                    <span class="gallery-media-ledge" aria-hidden="true"></span>
                                                </span>
                                            @endif
                                            <span class="gallery-media-frame">
                                                <img
                                                    src="{{ asset('storage/'.$cover) }}"
                                                    alt="{{ $alt }}"
                                                    class="gallery-media-image gallery-media-image--hover"
                                                >
                                            </span>
                                        </article>
                                    @endif
                                @endforeach
                            </div>
                            <div class="mt-10 text-center">
                                <x-section-more-link :href="route('gallery')">すべて見る →</x-section-more-link>
                            </div>
                        </div>
                    </section>
                @endif
                @break

            @case('staff')
                <section id="staff" class="site-section border-t border-salon-line">
                    <div class="mx-auto max-w-6xl px-4 md:px-6">
                        <div class="mb-12 text-center">
                            <p class="mb-2 text-sm tracking-widest text-salon-accent">Staff</p>
                            <h2 class="section-title">スタッフ紹介</h2>
                        </div>
                        <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                            @forelse($staffMembers as $member)
                                <article class="text-center">
                                    <div class="mx-auto mb-4 aspect-square w-40 overflow-hidden rounded-full bg-salon-line">
                                        @if($member->photo_path)
                                            <img src="{{ asset('storage/'.$member->photo_path) }}" alt="{{ $member->name }}" class="h-full w-full object-cover">
                                        @endif
                                    </div>
                                    <h3 class="font-medium">{{ $member->name }}</h3>
                                    @if($member->role)
                                        <p class="mt-1 text-sm text-salon-accent">{{ $member->role }}</p>
                                    @endif
                                </article>
                            @empty
                                <p class="col-span-full text-center text-salon-muted">スタッフ情報準備中です。</p>
                            @endforelse
                        </div>
                        <div class="mt-10 text-center">
                            <x-section-more-link :href="route('staff')">すべて見る →</x-section-more-link>
                        </div>
                    </div>
                </section>
                @break

            @case('access')
                <section id="access" class="site-section border-t border-salon-line bg-white/60">
                    <div class="mx-auto max-w-6xl px-4 md:px-6">
                        <div class="mb-10 text-center md:mb-12">
                            <p class="mb-2 text-sm tracking-widest text-salon-accent">Access</p>
                            <h2 class="section-title">店舗情報</h2>
                        </div>
                        <x-public.store-info :setting="$setting" />
                        <div class="mt-10 text-center">
                            <x-section-more-link :href="route('access')">店舗情報を見る →</x-section-more-link>
                        </div>
                    </div>
                </section>
                @break
        @endswitch
    @endforeach
@endsection
