@extends('layouts.public')

@section('title', $setting->shop_name)

@section('content')
    @php
        $heroImages = $heroImages ?? collect();
        $heroCount = $heroImages->count();
        $heroSliderEnabled = $heroCount > 1;
        $topSections = $topSections ?? collect();
        $design = $design ?? \App\Models\DesignSetting::current();
        $isVerticalIndicator = $design->usesVerticalScrollIndicator();
        $useGalleryModal = $design->usesGalleryDetailModal();
        $useNewsModal = $design->usesNewsDetailModal();
        $useBlogModal = $design->usesBlogDetailModal();
    @endphp

    {{-- Hero --}}
    <section
        class="hero-slider relative min-h-[70vh] overflow-hidden{{ $heroSliderEnabled ? ' hero-slider--draggable' : '' }}"
        id="hero-slider"
        data-hero-count="{{ $heroCount }}"
        @if($heroSliderEnabled) data-autoplay="5000" data-hero-drag @endif
    >
        @if($heroCount > 0)
            <div class="hero-slides" id="hero-slides" aria-live="polite">
                <div class="hero-slides-track" id="hero-slides-track">
                    @foreach($heroImages as $index => $heroImage)
                        <div
                            class="hero-slide"
                            data-hero-index="{{ $index }}"
                            @if($index !== 0) aria-hidden="true" @endif
                        >
                            <img
                                src="{{ asset('storage/'.$heroImage->image_path) }}"
                                alt="{{ $heroImage->alt_text ?? '' }}"
                                class="hero-slide-image"
                                draggable="false"
                            >
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="absolute inset-0 bg-gradient-to-br from-[#E8E4DC] to-[#C5D4BC]"></div>
        @endif

        <div class="absolute inset-0 z-10 bg-black/30 pointer-events-none"></div>

        <div class="hero-slider-panel relative z-20 mx-auto flex min-h-[70vh] max-w-6xl flex-col justify-center px-4 py-20 text-white md:px-6">
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
                const slidesEl = document.getElementById('hero-slides');
                const track = document.getElementById('hero-slides-track');
                if (!root || !slidesEl || !track) return;

                const realSlides = Array.from(track.querySelectorAll('.hero-slide'));
                const dots = Array.from(root.querySelectorAll('.hero-dot'));
                const prevBtn = document.getElementById('hero-prev');
                const nextBtn = document.getElementById('hero-next');
                const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                const intervalMs = parseInt(root.dataset.autoplay || '5000', 10);
                const DRAG_THRESHOLD_PX = 56;
                const SLIDE_MS = reduceMotion ? 0 : 450;
                const slideCount = realSlides.length;

                if (slideCount < 2) return;

                // 無限ループ用に前後クローン
                const firstClone = realSlides[0].cloneNode(true);
                const lastClone = realSlides[slideCount - 1].cloneNode(true);
                firstClone.removeAttribute('data-hero-index');
                lastClone.removeAttribute('data-hero-index');
                firstClone.setAttribute('aria-hidden', 'true');
                lastClone.setAttribute('aria-hidden', 'true');
                firstClone.classList.add('hero-slide--clone');
                lastClone.classList.add('hero-slide--clone');
                track.insertBefore(lastClone, realSlides[0]);
                track.appendChild(firstClone);

                let index = 0; // 論理インデックス 0..n-1
                let position = 1; // トラック位置（クローン込み）
                let timer = null;
                let isAnimating = false;
                let dragPointerId = null;
                let dragStartX = null;
                let dragDeltaX = 0;
                let dragActive = false;

                function viewportWidth() {
                    return slidesEl.clientWidth || root.clientWidth || 1;
                }

                function setTrackOffset(offsetPx, withTransition) {
                    if (withTransition && SLIDE_MS > 0) {
                        root.classList.add('is-animating');
                        track.style.transitionDuration = SLIDE_MS + 'ms';
                    } else {
                        root.classList.remove('is-animating');
                        track.style.transitionDuration = '0ms';
                    }
                    track.style.transform = 'translate3d(' + offsetPx + 'px, 0, 0)';
                }

                function offsetForPosition(pos, dragPx) {
                    return -pos * viewportWidth() + (dragPx || 0);
                }

                function updateDotsAndAria() {
                    realSlides.forEach(function (slide, i) {
                        const active = i === index;
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

                function normalizePosition() {
                    if (position === 0) {
                        position = slideCount;
                        index = slideCount - 1;
                        setTrackOffset(offsetForPosition(position, 0), false);
                    } else if (position === slideCount + 1) {
                        position = 1;
                        index = 0;
                        setTrackOffset(offsetForPosition(position, 0), false);
                    } else {
                        index = position - 1;
                    }
                    updateDotsAndAria();
                }

                function afterSlideTransition(callback) {
                    if (SLIDE_MS <= 0) {
                        callback();
                        return;
                    }
                    let done = false;
                    const finish = function () {
                        if (done) return;
                        done = true;
                        track.removeEventListener('transitionend', onEnd);
                        window.clearTimeout(fallback);
                        callback();
                    };
                    const onEnd = function (event) {
                        if (event.target !== track) return;
                        if (event.propertyName && event.propertyName !== 'transform') return;
                        finish();
                    };
                    track.addEventListener('transitionend', onEnd);
                    const fallback = window.setTimeout(finish, SLIDE_MS + 80);
                }

                function goToPosition(nextPosition, restart) {
                    if (isAnimating || dragActive) return;
                    if (nextPosition === position) {
                        if (restart) startTimer();
                        return;
                    }

                    isAnimating = true;
                    stopTimer();
                    position = nextPosition;
                    setTrackOffset(offsetForPosition(position, 0), true);

                    afterSlideTransition(function () {
                        normalizePosition();
                        isAnimating = false;
                        root.classList.remove('is-animating');
                        if (restart !== false) startTimer();
                    });
                }

                // 左右ボタン・ドット・自動・ドラッグ共通
                function goPrev() {
                    goToPosition(position - 1, true);
                }

                function goNext() {
                    goToPosition(position + 1, true);
                }

                function goToIndex(targetIndex) {
                    const next = ((targetIndex % slideCount) + slideCount) % slideCount;
                    if (next === index && !isAnimating) {
                        startTimer();
                        return;
                    }
                    goToPosition(next + 1, true);
                }

                function startTimer() {
                    stopTimer();
                    if (reduceMotion || slideCount < 2) return;
                    timer = window.setInterval(function () {
                        goNext();
                    }, intervalMs);
                }

                function stopTimer() {
                    if (timer) {
                        window.clearInterval(timer);
                        timer = null;
                    }
                }

                function isDragIgnoredTarget(target) {
                    if (!(target instanceof Element)) return true;
                    return Boolean(target.closest('a, button, input, textarea, select, label, [data-hero-controls]'));
                }

                function clearDragListeners() {
                    window.removeEventListener('pointermove', onWindowPointerMove, true);
                    window.removeEventListener('pointerup', onWindowPointerUp, true);
                    window.removeEventListener('pointercancel', onWindowPointerCancel, true);
                }

                function endDragState() {
                    dragPointerId = null;
                    dragStartX = null;
                    dragDeltaX = 0;
                    dragActive = false;
                    root.classList.remove('is-dragging');
                    clearDragListeners();
                }

                function onWindowPointerMove(event) {
                    if (!dragActive || event.pointerId !== dragPointerId) return;
                    dragDeltaX = event.clientX - dragStartX;
                    event.preventDefault();
                    setTrackOffset(offsetForPosition(position, dragDeltaX), false);
                }

                function onWindowPointerUp(event) {
                    if (!dragActive) return;
                    if (typeof event.pointerId === 'number' && event.pointerId !== dragPointerId) return;

                    const deltaX = event.clientX - dragStartX;
                    endDragState();

                    if (deltaX <= -DRAG_THRESHOLD_PX) {
                        goNext();
                        return;
                    }
                    if (deltaX >= DRAG_THRESHOLD_PX) {
                        goPrev();
                        return;
                    }

                    // 閾値未満: 現在位置へスナップバック
                    isAnimating = true;
                    setTrackOffset(offsetForPosition(position, 0), true);
                    afterSlideTransition(function () {
                        isAnimating = false;
                        root.classList.remove('is-animating');
                        startTimer();
                    });
                }

                function onWindowPointerCancel() {
                    if (!dragActive) return;
                    endDragState();
                    isAnimating = true;
                    setTrackOffset(offsetForPosition(position, 0), true);
                    afterSlideTransition(function () {
                        isAnimating = false;
                        root.classList.remove('is-animating');
                        startTimer();
                    });
                }

                function onPointerDown(event) {
                    if (isAnimating || dragActive) return;
                    if (event.pointerType === 'mouse' && event.button !== 0) return;
                    if (isDragIgnoredTarget(event.target)) return;

                    dragActive = true;
                    dragPointerId = event.pointerId;
                    dragStartX = event.clientX;
                    dragDeltaX = 0;
                    stopTimer();
                    root.classList.add('is-dragging');
                    setTrackOffset(offsetForPosition(position, 0), false);

                    window.addEventListener('pointermove', onWindowPointerMove, true);
                    window.addEventListener('pointerup', onWindowPointerUp, true);
                    window.addEventListener('pointercancel', onWindowPointerCancel, true);
                }

                prevBtn?.addEventListener('click', function () { goPrev(); });
                nextBtn?.addEventListener('click', function () { goNext(); });
                dots.forEach(function (dot) {
                    dot.addEventListener('click', function () {
                        goToIndex(parseInt(dot.dataset.heroDot || '0', 10));
                    });
                });

                root.addEventListener('pointerdown', onPointerDown);
                root.addEventListener('dragstart', function (event) {
                    event.preventDefault();
                });

                window.addEventListener('resize', function () {
                    setTrackOffset(offsetForPosition(position, 0), false);
                });

                // 初期位置（先頭クローンの次 = 実スライド0）
                setTrackOffset(offsetForPosition(position, 0), false);
                updateDotsAndAria();
                startTimer();
            })();
        </script>
    @endif

    @if($isVerticalIndicator)
        @include('public.partials.home-vi.concept')
    @else
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
    @endif

    @foreach($topSections as $section)
        @switch($section->section_key)
            @case('banner')
                @if($isVerticalIndicator)
                    @include('public.partials.home-vi.campaign')
                @elseif(($banners ?? collect())->isNotEmpty())
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
                    @include($isVerticalIndicator ? 'public.partials.home-vi.news-blog' : 'public.partials.home-news-blog')
                @endif
                @break

            @case('menu')
                @if($isVerticalIndicator)
                    @include('public.partials.home-vi.menu')
                @else
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
                @endif
                @break

            @case('gallery')
                @if($isVerticalIndicator)
                    @include('public.partials.home-vi.gallery')
                @elseif(($galleries ?? collect())->isNotEmpty())
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
                                                @if($useGalleryModal)
                                                    data-gallery-modal-trigger
                                                    data-gallery-id="{{ $gallery->id }}"
                                                @endif
                                            ></a>
                                            <a
                                                href="{{ $galleryUrl }}"
                                                class="home-gallery-card-more"
                                                aria-label="{{ $gallery->displayTitle() }}の詳細を見る"
                                                @if($useGalleryModal)
                                                    data-gallery-modal-trigger
                                                    data-gallery-id="{{ $gallery->id }}"
                                                @endif
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
                @if($isVerticalIndicator)
                    @include('public.partials.home-vi.staff')
                @else
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
                @endif
                @break

            @case('access')
                @if($isVerticalIndicator)
                    @include('public.partials.home-vi.access')
                @else
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
                @endif
                @break
        @endswitch
    @endforeach

    @if($useGalleryModal && ($galleries ?? collect())->isNotEmpty())
        <x-public.gallery-modal
            :galleries="$galleries"
            :reserve-url="$setting->hot_pepper_url ?? null"
        />
    @endif

    @if($useNewsModal && ($newsList ?? collect())->isNotEmpty())
        <x-public.news-modal :news-items="$newsList" />
    @endif

    @if($useBlogModal && ($blogList ?? collect())->isNotEmpty())
        <x-public.blog-modal :blogs="$blogList" />
    @endif

    @if($isVerticalIndicator && ($menuModalCategories ?? collect())->isNotEmpty())
        <x-public.menu-modal :categories="$menuModalCategories" />
    @endif
@endsection
