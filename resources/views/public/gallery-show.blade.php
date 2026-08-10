@extends('layouts.public')

@section('title', $gallery->displayTitle().' | ヘアギャラリー')

@section('content')
    @php
        $images = $gallery->images;
        $hasCarousel = $images->count() > 1;
        $staff = $gallery->staffMember;
        $displayTitle = $gallery->displayTitle();
        $description = $gallery->displayDescription();
    @endphp
    <section class="site-section">
        <div class="mx-auto max-w-6xl px-6 md:px-10 lg:px-12">
            <div class="gallery-detail-layout">
                <div class="gallery-detail-copy min-w-0">
                    <x-back-link :href="route('gallery')">ギャラリー一覧へ戻る</x-back-link>

                    <h1 class="mt-6 text-3xl font-semibold tracking-wide text-salon-text md:text-[2rem]">
                        {{ $displayTitle }}
                    </h1>

                    @if(filled($description))
                        <p class="mt-8 whitespace-pre-line text-base leading-8 text-salon-muted">
                            {{ $description }}
                        </p>
                    @endif
                </div>

                <div
                    class="gallery-detail-media min-w-0"
                    @if($hasCarousel)
                        data-gallery-carousel
                        data-gallery-interval="5000"
                        tabindex="0"
                        role="region"
                        aria-roledescription="carousel"
                        aria-label="ギャラリー画像"
                    @endif
                >
                    <div class="gallery-detail-frame mx-auto w-full max-w-2xl lg:max-w-none">
                        <div class="gallery-detail-image-frame" data-gallery-stage>
                            @foreach($images as $index => $image)
                                <figure
                                    class="gallery-detail-slide {{ $index === 0 ? 'is-active' : '' }}"
                                    data-gallery-slide
                                    data-gallery-index="{{ $index }}"
                                    @if($index !== 0) aria-hidden="true" @endif
                                >
                                    <img
                                        src="{{ asset('storage/'.$image->image_path) }}"
                                        alt="{{ $image->alt_text ?: $displayTitle }}"
                                        class="gallery-media-image gallery-media-image--contain"
                                        @if($index > 0) loading="lazy" @endif
                                    >
                                </figure>
                            @endforeach
                        </div>

                        @if($hasCarousel)
                            <div class="mt-5 flex items-center justify-center gap-4">
                                <button
                                    type="button"
                                    class="gallery-detail-nav"
                                    data-gallery-prev
                                    aria-label="前の画像"
                                >
                                    <svg class="gallery-detail-nav-icon" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                        <path d="M12.5 4.5 7 10l5.5 5.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>

                                <div class="flex items-center gap-2" data-gallery-dots role="tablist" aria-label="画像選択">
                                    @foreach($images as $index => $image)
                                        <button
                                            type="button"
                                            class="site-carousel-dot {{ $index === 0 ? 'is-active' : '' }}"
                                            data-gallery-dot
                                            data-gallery-index="{{ $index }}"
                                            aria-label="画像{{ $index + 1 }}を表示"
                                            @if($index === 0) aria-current="true" @endif
                                        ></button>
                                    @endforeach
                                </div>

                                <button
                                    type="button"
                                    class="gallery-detail-nav"
                                    data-gallery-next
                                    aria-label="次の画像"
                                >
                                    <svg class="gallery-detail-nav-icon" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                        <path d="M7.5 4.5 13 10l-5.5 5.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                            </div>
                        @endif
                    </div>
                </div>

                @if($staff)
                    <div class="gallery-detail-staff min-w-0">
                        <div class="gallery-detail-staff-inner">
                            <div class="flex items-start gap-5">
                                @if($staff->photo_path)
                                    <img
                                        src="{{ asset('storage/'.$staff->photo_path) }}"
                                        alt="{{ $staff->name }}"
                                        class="h-24 w-24 shrink-0 rounded-full object-cover"
                                    >
                                @else
                                    <div
                                        class="flex h-24 w-24 shrink-0 items-center justify-center rounded-full bg-salon-line text-sm text-salon-muted"
                                        aria-hidden="true"
                                    >Staff</div>
                                @endif

                                <div class="min-w-0">
                                    <p class="text-xs tracking-wide text-salon-muted">担当スタイリスト</p>
                                    @if(filled($staff->role))
                                        <p class="mt-1 text-sm text-salon-muted">{{ $staff->role }}</p>
                                    @endif
                                    <p class="mt-2 text-xl font-medium text-salon-text">{{ $staff->name }}</p>

                                    @if(filled($setting->hot_pepper_url))
                                        <a
                                            href="{{ $setting->hot_pepper_url }}"
                                            target="_blank"
                                            rel="noopener"
                                            class="btn-primary mt-4"
                                        >予約する</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>

    @if($hasCarousel)
        <script>
            (function () {
                const root = document.querySelector('[data-gallery-carousel]');
                if (!root) {
                    return;
                }

                const slides = Array.prototype.slice.call(root.querySelectorAll('[data-gallery-slide]'));
                const dots = Array.prototype.slice.call(root.querySelectorAll('[data-gallery-dot]'));
                const prevBtn = root.querySelector('[data-gallery-prev]');
                const nextBtn = root.querySelector('[data-gallery-next]');
                const stage = root.querySelector('[data-gallery-stage]');
                const intervalMs = parseInt(root.getAttribute('data-gallery-interval') || '5000', 10);
                const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

                let index = 0;
                let timerId = null;
                let paused = false;
                let touchStartX = null;
                let touchStartY = null;

                function goTo(nextIndex, fromUser) {
                    if (!slides.length) {
                        return;
                    }
                    index = (nextIndex + slides.length) % slides.length;
                    slides.forEach(function (slide, i) {
                        const active = i === index;
                        slide.classList.toggle('is-active', active);
                        if (active) {
                            slide.removeAttribute('aria-hidden');
                        } else {
                            slide.setAttribute('aria-hidden', 'true');
                        }
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
                    if (fromUser) {
                        restartTimer();
                    }
                }

                function stopTimer() {
                    if (timerId !== null) {
                        window.clearInterval(timerId);
                        timerId = null;
                    }
                }

                function startTimer() {
                    stopTimer();
                    if (reduceMotion || paused || slides.length < 2 || document.hidden) {
                        return;
                    }
                    timerId = window.setInterval(function () {
                        goTo(index + 1, false);
                    }, intervalMs);
                }

                function restartTimer() {
                    stopTimer();
                    startTimer();
                }

                function setPaused(nextPaused) {
                    paused = !!nextPaused;
                    if (paused) {
                        stopTimer();
                    } else {
                        startTimer();
                    }
                }

                prevBtn?.addEventListener('click', function () { goTo(index - 1, true); });
                nextBtn?.addEventListener('click', function () { goTo(index + 1, true); });
                dots.forEach(function (dot) {
                    dot.addEventListener('click', function () {
                        goTo(parseInt(dot.getAttribute('data-gallery-index') || '0', 10), true);
                    });
                });

                root.addEventListener('keydown', function (e) {
                    if (e.key === 'ArrowLeft') {
                        e.preventDefault();
                        goTo(index - 1, true);
                    } else if (e.key === 'ArrowRight') {
                        e.preventDefault();
                        goTo(index + 1, true);
                    }
                });

                root.addEventListener('mouseenter', function () { setPaused(true); });
                root.addEventListener('mouseleave', function () {
                    if (!root.contains(document.activeElement)) {
                        setPaused(false);
                    }
                });
                root.addEventListener('focusin', function () { setPaused(true); });
                root.addEventListener('focusout', function (e) {
                    if (!root.contains(e.relatedTarget)) {
                        setPaused(false);
                    }
                });

                document.addEventListener('visibilitychange', function () {
                    if (document.hidden) {
                        stopTimer();
                    } else if (!paused) {
                        startTimer();
                    }
                });

                const swipeTarget = stage || root;
                swipeTarget.addEventListener('touchstart', function (e) {
                    if (!e.changedTouches || !e.changedTouches[0]) {
                        return;
                    }
                    touchStartX = e.changedTouches[0].clientX;
                    touchStartY = e.changedTouches[0].clientY;
                }, { passive: true });

                swipeTarget.addEventListener('touchend', function (e) {
                    if (touchStartX === null || !e.changedTouches || !e.changedTouches[0]) {
                        return;
                    }
                    const deltaX = e.changedTouches[0].clientX - touchStartX;
                    const deltaY = e.changedTouches[0].clientY - (touchStartY || 0);
                    touchStartX = null;
                    touchStartY = null;
                    if (Math.abs(deltaX) < 40 || Math.abs(deltaX) <= Math.abs(deltaY)) {
                        return;
                    }
                    if (deltaX > 0) {
                        goTo(index - 1, true);
                    } else {
                        goTo(index + 1, true);
                    }
                }, { passive: true });

                startTimer();
            })();
        </script>
    @endif
@endsection
