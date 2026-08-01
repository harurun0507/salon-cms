@extends('layouts.public')

@section('title', $setting->shop_name)

@section('content')
    @php
        $heroImages = $heroImages ?? collect();
        $heroCount = $heroImages->count();
        $heroSliderEnabled = $heroCount > 1;
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
                <a href="{{ route('menu') }}" class="btn-outline border-white text-white hover:bg-white hover:text-salon-text">メニューを見る</a>
            </div>
        </div>

        @if($heroSliderEnabled)
            <button type="button" id="hero-prev" class="absolute left-3 top-1/2 z-30 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-black/35 text-white transition hover:bg-black/50 md:left-6" aria-label="前の画像">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <button type="button" id="hero-next" class="absolute right-3 top-1/2 z-30 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-black/35 text-white transition hover:bg-black/50 md:right-6" aria-label="次の画像">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7"/></svg>
            </button>
            <div id="hero-dots" class="absolute bottom-5 left-0 right-0 z-30 flex justify-center gap-2" role="tablist" aria-label="メインビジュアルの位置">
                @foreach($heroImages as $index => $heroImage)
                    <button type="button" class="hero-dot h-2.5 w-2.5 rounded-full bg-white/45 transition {{ $index === 0 ? 'bg-white' : '' }}" data-hero-dot="{{ $index }}" aria-label="画像{{ $index + 1 }}" aria-current="{{ $index === 0 ? 'true' : 'false' }}"></button>
                @endforeach
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
                        dot.classList.toggle('bg-white', active);
                        dot.classList.toggle('bg-white/45', !active);
                        dot.setAttribute('aria-current', active ? 'true' : 'false');
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
    <section id="concept" class="py-20 md:py-28">
        <div class="mx-auto max-w-3xl px-4 text-center md:px-6">
            <p class="mb-3 text-sm tracking-widest text-salon-accent">Concept</p>
            @if(!empty($setting->concept_title))
                <h2 class="section-title mb-8">{{ $setting->concept_title }}</h2>
            @endif
            <p class="leading-8 text-salon-muted whitespace-pre-line">{{ $setting->concept }}</p>
        </div>
    </section>

    {{-- News --}}
    <section id="news" class="border-y border-salon-line bg-white/60 py-16 md:py-20">
        <div class="mx-auto max-w-4xl px-4 md:px-6">
            <div class="mb-8 text-center md:mb-10">
                <p class="mb-2 text-sm tracking-widest text-salon-accent">News</p>
                <h2 class="section-title">お知らせ</h2>
            </div>
            @if($newsList->isNotEmpty())
                <ul class="divide-y divide-salon-line">
                    @foreach($newsList as $news)
                        <li>
                            <a href="{{ route('news.show', $news->slug) }}" class="flex flex-col gap-1 py-4 transition hover:text-salon-accent sm:flex-row sm:items-baseline sm:gap-6">
                                <time class="shrink-0 text-sm tabular-nums text-salon-muted">{{ $news->published_at?->format('Y.m.d') }}</time>
                                <span class="font-medium leading-relaxed">{{ $news->title }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
            <div class="mt-8 text-center">
                <x-section-more-link :href="route('news.index')">一覧を見る →</x-section-more-link>
            </div>
        </div>
    </section>

    {{-- Menu preview --}}
    <section id="menu" class="py-20">
        <div class="mx-auto max-w-6xl px-4 md:px-6">
            <div class="mb-12">
                <p class="mb-2 text-sm tracking-widest text-salon-accent">Menu</p>
                <h2 class="section-title">メニュー・料金</h2>
            </div>
            <div class="grid gap-10 md:grid-cols-2">
                @foreach($categories->take(2) as $category)
                    <div>
                        <h3 class="mb-4 border-b border-salon-line pb-2 font-medium">{{ $category->name }}</h3>
                        <ul class="space-y-4">
                            @foreach($category->publishedMenus->take(4) as $menu)
                                <li class="flex items-start justify-between gap-4">
                                    <div>
                                        <p>{{ $menu->name }}</p>
                                        @if($menu->description)
                                            <p class="mt-1 text-xs text-salon-muted whitespace-pre-line">{{ $menu->description }}</p>
                                        @endif
                                    </div>
                                    <p class="shrink-0 font-medium">¥{{ number_format($menu->price) }}</p>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
            <div class="mt-10 text-center">
                <x-section-more-link :href="route('menu')">すべて見る →</x-section-more-link>
            </div>
        </div>
    </section>

    {{-- Gallery --}}
    <section id="gallery" class="py-20 md:py-28">
        <div class="mx-auto max-w-6xl px-4 md:px-6">
            <div class="mb-12 text-center">
                <p class="mb-2 text-sm tracking-widest text-salon-accent">Gallery</p>
                <h2 class="section-title">ヘアギャラリー</h2>
            </div>
            <div class="grid grid-cols-2 gap-3 md:grid-cols-3 md:gap-4">
                @forelse($galleries as $gallery)
                    <figure class="aspect-[3/4] overflow-hidden rounded-sm">
                        <img src="{{ asset('storage/'.$gallery->image_path) }}" alt="{{ $gallery->caption }}" class="h-full w-full object-cover transition hover:scale-105">
                    </figure>
                @empty
                    <p class="col-span-full text-center text-salon-muted">ギャラリー準備中です。</p>
                @endforelse
            </div>
            <div class="mt-10 text-center">
                <x-section-more-link :href="route('gallery')">すべて見る →</x-section-more-link>
            </div>
        </div>
    </section>

    {{-- Staff --}}
    <section id="staff" class="border-t border-salon-line py-20 md:py-28">
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

    {{-- Access --}}
    <section id="access" class="border-t border-salon-line bg-white/60 py-20">
        <div class="mx-auto max-w-6xl px-4 md:px-6">
            <div class="mb-12 text-center">
                <p class="mb-2 text-sm tracking-widest text-salon-accent">Access</p>
                <h2 class="section-title">店舗情報</h2>
            </div>
            <div class="grid gap-10 md:grid-cols-2">
                <dl class="space-y-4 text-sm leading-7">
                    <div><dt class="font-medium">店名</dt><dd class="text-salon-muted">{{ $setting->shop_name }}</dd></div>
                    <div><dt class="font-medium">住所</dt><dd class="text-salon-muted">{{ $setting->address }}</dd></div>
                    <div><dt class="font-medium">営業時間</dt><dd class="whitespace-pre-line text-salon-muted">{{ $setting->business_hours }}</dd></div>
                    <div><dt class="font-medium">定休日</dt><dd class="text-salon-muted">{{ $setting->closed_days }}</dd></div>
                    <div><dt class="font-medium">電話</dt><dd class="text-salon-muted">{{ $setting->phone }}</dd></div>
                </dl>
                @if($setting->google_map_embed_url)
                    <div class="aspect-video overflow-hidden rounded-sm bg-salon-line">
                        <iframe src="{{ $setting->google_map_embed_url }}" class="h-full w-full border-0" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                @endif
            </div>
            <div class="mt-10 text-center">
                <a href="{{ route('access') }}" class="btn-primary">アクセス詳細</a>
            </div>
        </div>
    </section>
@endsection
