@php
    $setting = $setting ?? \App\Models\SalonSetting::current();
    $design = $design ?? \App\Models\DesignSetting::current();
    $scrollDisplayType = $design->resolvedScrollDisplayType();
    $usesVerticalScrollIndicator = $design->usesVerticalScrollIndicator();
    $siteTitle = $setting->seoSiteTitle();
    $pageTitle = trim($__env->yieldContent('title'));
    $documentTitle = $pageTitle !== '' && $pageTitle !== $siteTitle
        ? $pageTitle.' | '.$siteTitle
        : ($pageTitle !== '' ? $pageTitle : $siteTitle);
    $ogTitle = $setting->seoOgTitle();
    $ogDescription = $setting->seoOgDescription();
    $metaDescription = filled($setting->meta_description) ? (string) $setting->meta_description : null;
    $metaKeywords = filled($setting->meta_keywords) ? (string) $setting->meta_keywords : null;
    $ogImageUrl = $setting->seoOgImageUrl();
    $twitterCard = $setting->seoTwitterCard();
    $faviconUrl = $setting->faviconUrl();
    $hasCustomFavicon = $setting->hasCustomFavicon();
    $siteFaviconVersion = '2';
    $canonicalUrl = url()->current();
    $gaMeasurementId = $setting->hasGaMeasurementId() ? (string) $setting->ga_measurement_id : null;
    $showPublicGallery = $showPublicGallery
        ?? \App\Models\Gallery::query()
            ->where('is_published', true)
            ->whereHas('images')
            ->exists();
    $topSectionVisibility = $topSectionVisibility
        ?? \App\Models\TopPageSection::visibilityByKey();
    $headerNavItems = \App\Models\TopPageSection::publicHeaderNavItems($topSectionVisibility);
    $publicScrollSectionMeta = \App\Models\TopPageSection::publicScrollSectionMeta();
@endphp
<!DOCTYPE html>
<html
    lang="ja"
    @if(! $usesVerticalScrollIndicator) class="scroll-smooth" @endif
    data-scroll-display="{{ $scrollDisplayType }}"
>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $documentTitle }}</title>
    @if($hasCustomFavicon)
        <link rel="icon" href="{{ $faviconUrl }}">
    @else
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-site-32.png') }}?v={{ $siteFaviconVersion }}">
        <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/favicon-site-192.png') }}?v={{ $siteFaviconVersion }}">
        <link rel="icon" type="image/png" href="{{ asset('images/favicon-site.png') }}?v={{ $siteFaviconVersion }}">
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/favicon-site-180.png') }}?v={{ $siteFaviconVersion }}">
    @endif
    <link rel="canonical" href="{{ $canonicalUrl }}">
    @if($metaDescription)
        <meta name="description" content="{{ $metaDescription }}">
    @endif
    @if($metaKeywords)
        <meta name="keywords" content="{{ $metaKeywords }}">
    @endif
    <meta name="robots" content="{{ $setting->robotsMetaContent() }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $siteTitle }}">
    <meta property="og:title" content="{{ $ogTitle }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    @if($ogDescription)
        <meta property="og:description" content="{{ $ogDescription }}">
    @endif
    @if($ogImageUrl)
        <meta property="og:image" content="{{ $ogImageUrl }}">
    @endif
    <meta name="twitter:card" content="{{ $twitterCard }}">
    <meta name="twitter:title" content="{{ $ogTitle }}">
    @if($ogDescription)
        <meta name="twitter:description" content="{{ $ogDescription }}">
    @endif
    @if($ogImageUrl)
        <meta name="twitter:image" content="{{ $ogImageUrl }}">
    @endif
    @if($gaMeasurementId)
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaMeasurementId }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', @json($gaMeasurementId));
        </script>
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@300;400;500;700&family=Noto+Serif+JP:wght@400;600&display=swap" rel="stylesheet">
    @if (file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        fontFamily: {
                            sans: ['"Noto Sans JP"', 'Hiragino Sans', 'sans-serif'],
                            serif: ['"Noto Serif JP"', 'Hiragino Mincho ProN', 'serif'],
                        },
                        colors: {
                            'salon-bg': '#FAF7F1',
                            'salon-text': '#3A332E',
                            'salon-accent': '#7C8A6A',
                            'salon-button': '#5F6F52',
                            'salon-line': '#E3DDD2',
                            'salon-muted': '#6B635C',
                        }
                    }
                }
            }
        </script>
        <style type="text/tailwindcss">
            @layer components {
                .btn-primary {
                    display: inline-flex;
                    min-height: 44px;
                    align-items: center;
                    justify-content: center;
                    padding: 0.75rem 1.5rem;
                    border: 1px solid var(--site-primary, #5F6F52);
                    border-radius: var(--site-button-radius, 9999px);
                    background-color: var(--site-primary, #5F6F52);
                    color: #fff;
                    font-size: 0.875rem;
                    font-weight: 500;
                    line-height: 1.25;
                    text-decoration: none;
                    transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease, opacity 0.2s ease;
                }
                .btn-primary:hover { opacity: 0.9; }
                .btn-outline {
                    display: inline-flex;
                    min-height: 44px;
                    align-items: center;
                    justify-content: center;
                    padding: 0.75rem 1.5rem;
                    border: 1px solid var(--site-primary, #5F6F52);
                    border-radius: var(--site-button-radius, 9999px);
                    background-color: transparent;
                    color: var(--site-primary, #5F6F52);
                    font-size: 0.875rem;
                    font-weight: 500;
                    line-height: 1.25;
                    text-decoration: none;
                    transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
                }
                .btn-outline:hover {
                    background-color: var(--site-primary, #5F6F52);
                    border-color: var(--site-primary, #5F6F52);
                    color: #fff;
                }
                .btn-primary:focus-visible,
                .btn-outline:focus-visible {
                    outline: 2px solid color-mix(in srgb, var(--site-primary, #5F6F52) 45%, white);
                    outline-offset: 2px;
                }
                .section-title { @apply text-3xl md:text-4xl tracking-wide text-salon-text; font-family: var(--site-heading-font, var(--font-serif)); }
                .site-section { padding-block: var(--site-section-spacing, 5rem); }
                .site-card { border-radius: var(--site-card-radius, 0.5rem); padding: var(--site-card-padding, 1.5rem); }
            }
        </style>
    @endif
    <style id="site-design-vars">
        :root {
            {{ $design->cssVariablesStyleBlock() }}
        }
        body {
            font-family: var(--site-body-font, var(--font-sans));
            background-color: var(--site-background, var(--color-salon-bg, #FAF7F1));
            color: var(--site-text, var(--color-salon-text, #3A332E));
        }
        .font-serif,
        .section-title {
            font-family: var(--site-heading-font, var(--font-serif));
        }
        html { scroll-behavior: smooth; }
        /* VI: 初期hash着地を瞬時にし、先頭からのsmoothスクロールを出さない */
        html[data-scroll-display='vertical_indicator'] {
            scroll-behavior: auto;
        }
        #concept, #menu, #gallery, #staff, #access, #news, #blog { scroll-margin-top: 5.5rem; }
        html[data-scroll-display='vertical_indicator'] #concept,
        html[data-scroll-display='vertical_indicator'] #menu,
        html[data-scroll-display='vertical_indicator'] #gallery,
        html[data-scroll-display='vertical_indicator'] #staff,
        html[data-scroll-display='vertical_indicator'] #access,
        html[data-scroll-display='vertical_indicator'] #news,
        html[data-scroll-display='vertical_indicator'] #blog,
        html[data-scroll-display='vertical_indicator'] #banners {
            scroll-margin-top: var(--site-header-offset, 5.5rem);
        }
        [data-menu-category-section] {
            scroll-margin-top: var(
                --menu-category-scroll-margin,
                calc(var(--site-header-offset, 5.5rem) + var(--menu-category-nav-height, 4.5rem) + 0.75rem)
            );
        }
        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
        }
    </style>
</head>
<body class="font-sans has-mobile-bottom-bar">
    <header class="sticky top-0 z-50 border-b border-salon-line bg-salon-bg/95 backdrop-blur" data-site-header>
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 md:px-6 md:py-3.5">
            <a href="{{ route('home') }}" class="inline-flex min-h-[48px] max-w-[200px] shrink-0 items-center sm:max-w-[240px] md:min-h-[64px] md:max-w-[280px]">
                @if($setting->usesLogoInHeader())
                    <img
                        src="{{ asset('storage/'.$setting->logo_image) }}"
                        alt="{{ $setting->logoAlt() }}"
                        class="h-auto max-h-[56px] w-auto max-w-full object-contain object-left md:max-h-[72px]"
                        width="280"
                        height="72"
                    >
                @else
                    <span class="font-serif text-xl tracking-widest text-salon-text">{{ $setting->shop_name }}</span>
                @endif
            </a>

            <nav @class([
                'hidden items-center text-sm md:flex',
                'gap-5 lg:gap-7' => $usesVerticalScrollIndicator,
                'gap-8' => ! $usesVerticalScrollIndicator,
            ]) aria-label="メインメニュー">
                @foreach($headerNavItems as $navItem)
                    <a href="{{ $navItem['href'] }}" class="hover:text-salon-accent">{{ $navItem['label'] }}</a>
                @endforeach
            </nav>

            @if($usesVerticalScrollIndicator || $setting->hot_pepper_url)
                <div @class([
                    'hidden items-center md:flex',
                    'site-header-tools' => $usesVerticalScrollIndicator,
                ])>
                    @if($usesVerticalScrollIndicator)
                        <a href="{{ route('privacy') }}" class="site-header-tools__privacy">Privacy Policy</a>
                        <x-social-links variant="icons" class="site-header-tools__socials" />
                    @endif

                    @if($setting->hot_pepper_url)
                        <a href="{{ $setting->hot_pepper_url }}" target="_blank" rel="noopener" class="btn-primary">Reserve</a>
                    @endif
                </div>
            @endif

            <button
                type="button"
                id="mobile-menu-btn"
                class="inline-flex h-10 w-10 items-center justify-center text-salon-text md:hidden"
                aria-label="メニューを開く"
                aria-controls="mobile-menu"
                aria-expanded="false"
                data-mobile-menu-toggle
            >
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
        </div>
    </header>

    <div
        id="mobile-menu"
        class="site-mobile-nav hidden md:hidden"
        hidden
        data-mobile-menu
    >
        <div class="site-mobile-nav-bar">
            <a href="{{ route('home') }}" class="inline-flex min-h-[48px] max-w-[200px] shrink-0 items-center" data-nav-link>
                @if($setting->usesLogoInHeader())
                    <img
                        src="{{ asset('storage/'.$setting->logo_image) }}"
                        alt="{{ $setting->logoAlt() }}"
                        class="h-auto max-h-[56px] w-auto max-w-full object-contain object-left"
                        width="280"
                        height="72"
                    >
                @else
                    <span class="font-serif text-xl tracking-widest text-salon-text">{{ $setting->shop_name }}</span>
                @endif
            </a>
            <button
                type="button"
                class="inline-flex h-10 w-10 items-center justify-center text-salon-text"
                aria-label="メニューを閉じる"
                data-mobile-menu-close
            >
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 6l12 12M18 6L6 18"/>
                </svg>
            </button>
        </div>

        <nav class="site-mobile-nav-panel" aria-label="モバイルメニュー">
            <div class="site-mobile-nav-links">
                @foreach($headerNavItems as $navItem)
                    <a href="{{ $navItem['href'] }}" data-nav-link class="site-mobile-nav-link">
                        <span>{{ $navItem['label'] }}</span>
                        <svg class="site-mobile-nav-chevron" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M7.5 4.5 13 10l-5.5 5.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                @endforeach
            </div>
            @if($usesVerticalScrollIndicator)
                <div class="site-mobile-nav-meta">
                    <a href="{{ route('privacy') }}" class="site-mobile-nav-meta__privacy" data-nav-link>Privacy Policy</a>
                    <x-social-links variant="icons" class="site-mobile-nav-meta__socials" />
                </div>
            @endif
            @if($setting->hot_pepper_url)
                <div class="site-mobile-nav-cta">
                    <a href="{{ $setting->hot_pepper_url }}" target="_blank" rel="noopener" class="btn-primary w-full text-center" data-nav-link>Reserve</a>
                </div>
            @endif
        </nav>
    </div>

    <main>@yield('content')</main>

    <x-public.site-footer :setting="$setting" />

    <div class="site-mobile-bottom-bar" aria-hidden="true">
        <span class="site-mobile-bottom-bar__name">{{ $setting->shop_name }}</span>
    </div>

    @if($usesVerticalScrollIndicator)
        <nav class="site-section-dots" aria-label="セクションナビゲーション" data-site-section-dots hidden></nav>
    @endif

    <script>
        (function () {
            const mobileMenu = document.querySelector('[data-mobile-menu]');
            const openBtn = document.querySelector('[data-mobile-menu-toggle]');
            const closeBtns = document.querySelectorAll('[data-mobile-menu-close]');
            const siteHeader = document.querySelector('[data-site-header]');
            const mqDesktop = window.matchMedia('(min-width: 768px)');
            let lockedScrollY = 0;
            let isOpen = false;

            function syncSiteHeaderOffset() {
                if (!siteHeader) {
                    return;
                }
                const headerHeight = Math.round(siteHeader.getBoundingClientRect().height);
                document.documentElement.style.setProperty('--site-header-offset', headerHeight + 'px');
            }

            syncSiteHeaderOffset();
            window.addEventListener('resize', syncSiteHeaderOffset);
            if (typeof ResizeObserver !== 'undefined' && siteHeader) {
                new ResizeObserver(syncSiteHeaderOffset).observe(siteHeader);
            }

            function lockScroll() {
                lockedScrollY = window.scrollY || document.documentElement.scrollTop || 0;
                document.documentElement.classList.add('is-mobile-menu-open');
                document.body.classList.add('is-mobile-menu-open');
                document.body.style.top = '-' + lockedScrollY + 'px';
            }

            function unlockScroll() {
                document.documentElement.classList.remove('is-mobile-menu-open');
                document.body.classList.remove('is-mobile-menu-open');
                document.body.style.top = '';
                window.scrollTo(0, lockedScrollY);
            }

            function setMenuOpen(open) {
                if (!mobileMenu || !openBtn) return;
                if (mqDesktop.matches) {
                    open = false;
                }
                isOpen = Boolean(open);
                mobileMenu.classList.toggle('hidden', !isOpen);
                if (isOpen) {
                    mobileMenu.removeAttribute('hidden');
                    lockScroll();
                } else {
                    mobileMenu.setAttribute('hidden', '');
                    unlockScroll();
                }
                openBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                openBtn.setAttribute('aria-label', isOpen ? 'メニューを閉じる' : 'メニューを開く');
            }

            function closeMenu() {
                setMenuOpen(false);
            }

            openBtn?.addEventListener('click', function () {
                setMenuOpen(true);
            });

            closeBtns.forEach(function (btn) {
                btn.addEventListener('click', closeMenu);
            });

            document.querySelectorAll('[data-mobile-menu] [data-nav-link]').forEach(function (link) {
                link.addEventListener('click', closeMenu);
            });

            window.addEventListener('resize', function () {
                if (mqDesktop.matches && isOpen) {
                    closeMenu();
                }
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && isOpen) {
                    closeMenu();
                }
            });

            function isVerticalIndicatorMode() {
                return document.documentElement.getAttribute('data-scroll-display') === 'vertical_indicator';
            }

            function scrollToHashTarget() {
                // 縦インジケーターは専用スクリプトがhash初期表示を担当する
                if (isVerticalIndicatorMode()) {
                    return;
                }
                const id = window.location.hash.replace(/^#/, '');
                if (!id) return;
                const behavior = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth';
                // Top / hero must land at the true page origin (not section offset under sticky header).
                if (id === 'hero-slider') {
                    window.scrollTo({ top: 0, left: 0, behavior: behavior });
                    return;
                }
                const target = document.getElementById(id);
                if (!target) return;
                target.scrollIntoView({ behavior: behavior, block: 'start' });
            }

            window.addEventListener('load', scrollToHashTarget);
            window.addEventListener('hashchange', scrollToHashTarget);
        })();
    </script>
    @if($usesVerticalScrollIndicator)
        <script>
            (function () {
                const nav = document.querySelector('[data-site-section-dots]');
                if (!nav) {
                    return;
                }

                const SECTION_META = @json($publicScrollSectionMeta);
                const HOME_PATH = @json(parse_url(route('home'), PHP_URL_PATH) ?: '/');
                const reduceMotionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
                const SWIPE_THRESHOLD_PX = 64;
                const EDGE_EPSILON_PX = 12;
                const ANIMATION_MS = 900;
                const ANIMATION_MS_REDUCED = 80;
                /** Pixel delta (normalized) needed to commit one section snap. */
                const WHEEL_THRESHOLD_PX = 56;
                /** Ignore further wheel snaps after a section change (trackpad inertia). */
                const WHEEL_COOLDOWN_MS = 480;
                /** Reset unused wheel accumulation when the gesture pauses. */
                const WHEEL_IDLE_RESET_MS = 160;

                let sections = [];
                let buttons = [];
                let activeIndex = -1;
                let ticking = false;
                let isAnimating = false;
                let isWheelLocked = false;
                let animTimer = null;
                let animStartedAt = 0;
                let scrollEndHandler = null;
                let wheelDeltaAccum = 0;
                let wheelLastDir = 0;
                let wheelIdleTimer = null;
                let wheelCooldownTimer = null;
                let touchStartX = null;
                let touchStartY = null;
                let touchIntent = 0;

                function prefersReducedMotion() {
                    return reduceMotionQuery.matches;
                }

                function isMobileMenuOpen() {
                    return document.documentElement.classList.contains('is-mobile-menu-open');
                }

                function isPublicModalOpen() {
                    return document.documentElement.classList.contains('is-public-modal-open');
                }

                function currentScrollY() {
                    return window.scrollY
                        || window.pageYOffset
                        || document.documentElement.scrollTop
                        || 0;
                }

                function isTopSection(section) {
                    return Boolean(section && section.id === 'hero-slider');
                }

                function scrollBehavior() {
                    return prefersReducedMotion() ? 'auto' : 'smooth';
                }

                function scrollToPageTop() {
                    window.scrollTo({
                        top: 0,
                        left: 0,
                        behavior: scrollBehavior(),
                    });
                }

                function collectSections() {
                    const found = [];
                    SECTION_META.forEach(function (meta) {
                        const el = document.getElementById(meta.id);
                        if (!el) {
                            return;
                        }
                        if (found.some(function (item) { return item.el.contains(el); })) {
                            return;
                        }
                        found.push({ el: el, id: meta.id, label: meta.label });
                    });
                    return found;
                }

                function indexForHash() {
                    const raw = window.location.hash.replace(/^#/, '');
                    if (!raw) {
                        return -1;
                    }

                    for (let i = 0; i < sections.length; i += 1) {
                        if (sections[i].id === raw) {
                            return i;
                        }
                    }

                    const el = document.getElementById(raw);
                    if (!el) {
                        return -1;
                    }

                    for (let i = 0; i < sections.length; i += 1) {
                        if (sections[i].el === el || sections[i].el.contains(el)) {
                            return i;
                        }
                    }

                    return -1;
                }

                function jumpToSectionInstant(index) {
                    if (!sections.length || index < 0 || index >= sections.length) {
                        return false;
                    }

                    unlockAnimation();
                    isWheelLocked = false;
                    resetWheelAccum();
                    if (wheelCooldownTimer) {
                        window.clearTimeout(wheelCooldownTimer);
                        wheelCooldownTimer = null;
                    }

                    const section = sections[index];
                    setActive(index);
                    updateHash(section);

                    if (isTopSection(section)) {
                        window.scrollTo({ top: 0, left: 0, behavior: 'auto' });
                    } else {
                        section.el.scrollIntoView({ behavior: 'auto', block: 'start' });
                    }

                    return true;
                }

                function applyHashTarget(options) {
                    const instant = Boolean(options && options.instant);
                    const index = indexForHash();
                    if (index < 0) {
                        return false;
                    }
                    if (instant) {
                        return jumpToSectionInstant(index);
                    }
                    return goToSection(index);
                }

                function setActive(index) {
                    if (index < 0 || index >= buttons.length) {
                        return;
                    }
                    if (index === activeIndex) {
                        return;
                    }
                    activeIndex = index;
                    buttons.forEach(function (button, i) {
                        const active = i === index;
                        button.classList.toggle('is-active', active);
                        if (active) {
                            button.setAttribute('aria-current', 'true');
                        } else {
                            button.removeAttribute('aria-current');
                        }
                    });
                }

                function sectionIndexAt(focusY) {
                    for (let i = 0; i < sections.length; i += 1) {
                        const rect = sections[i].el.getBoundingClientRect();
                        if (focusY >= rect.top && focusY < rect.bottom) {
                            return i;
                        }
                    }

                    let best = 0;
                    let bestDist = Infinity;
                    for (let i = 0; i < sections.length; i += 1) {
                        const rect = sections[i].el.getBoundingClientRect();
                        const mid = (rect.top + rect.bottom) / 2;
                        const dist = Math.abs(mid - focusY);
                        if (dist < bestDist) {
                            bestDist = dist;
                            best = i;
                        }
                    }
                    return best;
                }

                function currentSectionIndex() {
                    if (activeIndex >= 0 && activeIndex < sections.length) {
                        return activeIndex;
                    }
                    const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
                    return sectionIndexAt(viewportHeight * 0.5);
                }

                function getSectionScrollState(index) {
                    const section = sections[index];
                    if (!section) {
                        return { isTall: false, atStart: true, atEnd: true };
                    }
                    // Top / hero uses true page origin; do not treat sticky-header offset as "inside" the section.
                    if (isTopSection(section)) {
                        const scrollY = currentScrollY();
                        return {
                            isTall: false,
                            atStart: scrollY <= EDGE_EPSILON_PX,
                            atEnd: scrollY <= EDGE_EPSILON_PX,
                        };
                    }
                    const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
                    const rect = section.el.getBoundingClientRect();
                    // Last section (Access): include site footer so Access+footer is the true page end.
                    let endBottom = rect.bottom;
                    if (index === sections.length - 1) {
                        const footer = document.querySelector('.site-footer');
                        if (footer) {
                            endBottom = Math.max(endBottom, footer.getBoundingClientRect().bottom);
                        }
                    }
                    const combinedHeight = endBottom - rect.top;
                    const isTall = combinedHeight > viewportHeight + EDGE_EPSILON_PX;
                    const atStart = rect.top >= -EDGE_EPSILON_PX;
                    const atEnd = endBottom <= viewportHeight + EDGE_EPSILON_PX;
                    return { isTall: isTall, atStart: atStart, atEnd: atEnd };
                }

                function maxScrollY() {
                    const footer = document.querySelector('.site-footer');
                    const doc = document.documentElement;
                    const viewportHeight = window.innerHeight || doc.clientHeight || 0;
                    if (footer) {
                        const footerBottom = footer.getBoundingClientRect().bottom + currentScrollY();
                        return Math.max(0, Math.ceil(footerBottom - viewportHeight));
                    }
                    return Math.max(0, (doc.scrollHeight || 0) - viewportHeight);
                }

                function clampScrollToFooterEnd() {
                    const max = maxScrollY();
                    if (currentScrollY() > max + 1) {
                        window.scrollTo({ top: max, left: 0, behavior: 'auto' });
                    }
                }

                function updateHash(section) {
                    if (!history.replaceState) {
                        return;
                    }
                    if (section.id && section.id !== 'hero-slider') {
                        history.replaceState(null, '', '#' + section.id);
                        return;
                    }
                    history.replaceState(null, '', window.location.pathname + window.location.search);
                }

                function clearWheelIdleTimer() {
                    if (wheelIdleTimer) {
                        window.clearTimeout(wheelIdleTimer);
                        wheelIdleTimer = null;
                    }
                }

                function resetWheelAccum() {
                    wheelDeltaAccum = 0;
                    wheelLastDir = 0;
                    clearWheelIdleTimer();
                }

                function scheduleWheelUnlock() {
                    if (wheelCooldownTimer) {
                        window.clearTimeout(wheelCooldownTimer);
                    }
                    wheelCooldownTimer = window.setTimeout(function () {
                        isWheelLocked = false;
                        resetWheelAccum();
                        wheelCooldownTimer = null;
                    }, WHEEL_COOLDOWN_MS);
                }

                function normalizeWheelDelta(event) {
                    let dy = event.deltaY;
                    if (event.deltaMode === 1) {
                        dy *= 16;
                    } else if (event.deltaMode === 2) {
                        dy *= window.innerHeight || 800;
                    }
                    return dy;
                }

                function unlockAnimation() {
                    const wasAnimating = isAnimating;
                    isAnimating = false;
                    if (animTimer) {
                        window.clearTimeout(animTimer);
                        animTimer = null;
                    }
                    if (scrollEndHandler) {
                        window.removeEventListener('scrollend', scrollEndHandler);
                        scrollEndHandler = null;
                    }
                    if (wasAnimating) {
                        scheduleWheelUnlock();
                    }
                }

                function goToSection(index) {
                    if (!sections.length || index < 0 || index >= sections.length) {
                        return false;
                    }
                    if (isAnimating) {
                        return false;
                    }

                    const section = sections[index];
                    isAnimating = true;
                    isWheelLocked = true;
                    resetWheelAccum();
                    animStartedAt = Date.now();
                    setActive(index);
                    updateHash(section);

                    // Top section: always scrollY = 0 (ignore section offset / sticky header).
                    if (isTopSection(section)) {
                        scrollToPageTop();
                    } else {
                        section.el.scrollIntoView({
                            behavior: scrollBehavior(),
                            block: 'start',
                        });
                    }

                    const duration = prefersReducedMotion() ? ANIMATION_MS_REDUCED : ANIMATION_MS;
                    if (animTimer) {
                        window.clearTimeout(animTimer);
                    }
                    animTimer = window.setTimeout(function () {
                        unlockAnimation();
                        updateActiveFromScroll();
                    }, duration);

                    if ('onscrollend' in window) {
                        if (scrollEndHandler) {
                            window.removeEventListener('scrollend', scrollEndHandler);
                        }
                        scrollEndHandler = function () {
                            // Ignore early scrollend from inertia / tiny scrolls; wait for min duration.
                            const minMs = prefersReducedMotion() ? ANIMATION_MS_REDUCED : Math.min(450, ANIMATION_MS);
                            const elapsed = Date.now() - animStartedAt;
                            if (elapsed < minMs) {
                                // once:true consumed this listener — re-arm until min duration elapses.
                                window.addEventListener('scrollend', scrollEndHandler, { once: true });
                                return;
                            }
                            unlockAnimation();
                            updateActiveFromScroll();
                        };
                        window.addEventListener('scrollend', scrollEndHandler, { once: true });
                    }

                    return true;
                }

                function canNavigate(direction) {
                    if (!sections.length || sections.length < 2 || isMobileMenuOpen() || isPublicModalOpen()) {
                        return null;
                    }

                    const current = currentSectionIndex();
                    const state = getSectionScrollState(current);

                    if (direction > 0) {
                        if (state.isTall && !state.atEnd) {
                            return null;
                        }
                        if (current >= sections.length - 1) {
                            return null;
                        }
                        return current + 1;
                    }

                    if (state.isTall && !state.atStart) {
                        return null;
                    }
                    if (current <= 0) {
                        // Re-snap to true page top if the top section is active but scrollY is not 0.
                        if (isTopSection(sections[0]) && currentScrollY() > EDGE_EPSILON_PX) {
                            return 0;
                        }
                        return null;
                    }
                    return current - 1;
                }

                function updateActiveFromScroll() {
                    if (!sections.length) {
                        ticking = false;
                        return;
                    }
                    if (isAnimating) {
                        ticking = false;
                        return;
                    }

                    const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
                    const focusY = viewportHeight * 0.5;
                    const hysteresis = Math.min(96, Math.max(48, viewportHeight * 0.1));
                    const candidate = sectionIndexAt(focusY);

                    if (activeIndex < 0) {
                        setActive(candidate);
                        ticking = false;
                        return;
                    }

                    if (candidate === activeIndex) {
                        ticking = false;
                        return;
                    }

                    const candidateRect = sections[candidate].el.getBoundingClientRect();
                    const depthIntoCandidate = candidate > activeIndex
                        ? focusY - candidateRect.top
                        : candidateRect.bottom - focusY;

                    if (depthIntoCandidate >= hysteresis) {
                        setActive(candidate);
                    }

                    ticking = false;
                }

                function requestUpdate() {
                    if (ticking) {
                        return;
                    }
                    ticking = true;
                    window.requestAnimationFrame(updateActiveFromScroll);
                }

                function onWheel(event) {
                    if (event.ctrlKey || !sections.length || sections.length < 2 || isPublicModalOpen()) {
                        return;
                    }

                    const dy = normalizeWheelDelta(event);
                    if (Math.abs(dy) < 1) {
                        return;
                    }

                    const direction = dy > 0 ? 1 : -1;

                    if (isAnimating || isWheelLocked) {
                        event.preventDefault();
                        return;
                    }

                    const targetIndex = canNavigate(direction);
                    if (targetIndex === null) {
                        resetWheelAccum();
                        // Already at true page top: further upward wheel must not move the page.
                        if (
                            direction < 0
                            && currentSectionIndex() === 0
                            && isTopSection(sections[0])
                            && currentScrollY() <= EDGE_EPSILON_PX
                        ) {
                            event.preventDefault();
                        }
                        // Past Access+footer: do not allow empty scroll below the footer.
                        if (direction > 0 && currentScrollY() >= maxScrollY() - EDGE_EPSILON_PX) {
                            event.preventDefault();
                            clampScrollToFooterEnd();
                        }
                        // Tall sections (free scroll mid-section): leave native wheel alone.
                        return;
                    }

                    // Snap candidate: consume wheel as a gesture (do not native-scroll past the edge).
                    event.preventDefault();

                    if (wheelLastDir && direction !== wheelLastDir) {
                        wheelDeltaAccum = 0;
                    }
                    wheelLastDir = direction;
                    wheelDeltaAccum += dy;

                    clearWheelIdleTimer();
                    wheelIdleTimer = window.setTimeout(function () {
                        resetWheelAccum();
                    }, WHEEL_IDLE_RESET_MS);

                    if (Math.abs(wheelDeltaAccum) < WHEEL_THRESHOLD_PX) {
                        return;
                    }

                    resetWheelAccum();
                    goToSection(targetIndex);
                }

                function onTouchStart(event) {
                    if (event.touches.length !== 1 || isMobileMenuOpen() || isPublicModalOpen()) {
                        touchStartX = null;
                        touchStartY = null;
                        touchIntent = 0;
                        return;
                    }
                    touchStartX = event.touches[0].clientX;
                    touchStartY = event.touches[0].clientY;
                    touchIntent = 0;
                }

                function onTouchMove(event) {
                    if (touchStartY === null || event.touches.length !== 1 || isPublicModalOpen()) {
                        return;
                    }

                    if (isAnimating) {
                        event.preventDefault();
                        return;
                    }

                    const currentX = event.touches[0].clientX;
                    const currentY = event.touches[0].clientY;
                    const dx = currentX - touchStartX;
                    const dy = touchStartY - currentY;

                    if (Math.abs(dy) < SWIPE_THRESHOLD_PX) {
                        touchIntent = 0;
                        return;
                    }

                    // 横スワイプ（ヒーローカルーセル等）は邪魔しない
                    if (Math.abs(dx) > Math.abs(dy) * 0.75) {
                        touchIntent = 0;
                        return;
                    }

                    const direction = dy > 0 ? 1 : -1;
                    const targetIndex = canNavigate(direction);
                    if (targetIndex === null) {
                        touchIntent = 0;
                        return;
                    }

                    touchIntent = direction;
                    event.preventDefault();
                }

                function onTouchEnd() {
                    if (touchIntent && !isAnimating) {
                        const targetIndex = canNavigate(touchIntent);
                        if (targetIndex !== null) {
                            goToSection(targetIndex);
                        }
                    }
                    touchStartX = null;
                    touchStartY = null;
                    touchIntent = 0;
                }

                function onTouchCancel() {
                    touchStartX = null;
                    touchStartY = null;
                    touchIntent = 0;
                }

                function buildNav() {
                    sections = collectSections();
                    nav.innerHTML = '';
                    buttons = [];
                    activeIndex = -1;

                    if (sections.length < 2) {
                        nav.hidden = true;
                        nav.classList.remove('is-ready');
                        return;
                    }

                    const list = document.createElement('ul');
                    list.className = 'site-section-dots__list';
                    list.setAttribute('role', 'list');

                    sections.forEach(function (section, index) {
                        const item = document.createElement('li');
                        item.className = 'site-section-dots__item';

                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'site-section-dots__button';
                        button.setAttribute('aria-label', section.label + 'へ移動');
                        button.dataset.sectionId = section.id;
                        button.dataset.sectionIndex = String(index);
                        button.innerHTML = '<span class="site-section-dots__dot" aria-hidden="true"></span>';
                        button.addEventListener('click', function () {
                            goToSection(index);
                        });

                        item.appendChild(button);
                        list.appendChild(item);
                        buttons.push(button);
                    });

                    nav.appendChild(list);
                    nav.hidden = false;
                    nav.classList.add('is-ready');

                    // 初期表示: URL hash のセクションを瞬時に表示（smooth遷移なし）
                    if (!applyHashTarget({ instant: true })) {
                        updateActiveFromScroll();
                    }
                }

                function onHomeLogoClick(event) {
                    const link = event.currentTarget;
                    if (!(link instanceof HTMLAnchorElement)) {
                        return;
                    }
                    let url;
                    try {
                        url = new URL(link.href, window.location.origin);
                    } catch (err) {
                        return;
                    }
                    if (url.pathname !== HOME_PATH) {
                        return;
                    }
                    if (url.hash && url.hash !== '#hero-slider') {
                        return;
                    }
                    if (window.location.pathname !== HOME_PATH) {
                        return;
                    }

                    event.preventDefault();
                    const topIndex = sections.findIndex(function (section) {
                        return isTopSection(section);
                    });
                    if (topIndex >= 0) {
                        goToSection(topIndex);
                        return;
                    }
                    scrollToPageTop();
                    if (history.replaceState) {
                        history.replaceState(null, '', HOME_PATH + url.search);
                    }
                }

                buildNav();

                document.querySelectorAll('[data-site-header] a[href], [data-mobile-menu] a[href]').forEach(function (link) {
                    link.addEventListener('click', onHomeLogoClick);
                });

                window.addEventListener('scroll', function () {
                    clampScrollToFooterEnd();
                    requestUpdate();
                }, { passive: true });
                window.addEventListener('resize', function () {
                    clampScrollToFooterEnd();
                    requestUpdate();
                });
                window.addEventListener('hashchange', function () {
                    if (!applyHashTarget({ instant: false })) {
                        requestUpdate();
                    }
                });
                window.addEventListener('wheel', onWheel, { passive: false });
                window.addEventListener('touchstart', onTouchStart, { passive: true });
                window.addEventListener('touchmove', onTouchMove, { passive: false });
                window.addEventListener('touchend', onTouchEnd, { passive: true });
                window.addEventListener('touchcancel', onTouchCancel, { passive: true });
            })();
        </script>
    @endif
</body>
</html>
