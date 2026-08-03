<!DOCTYPE html>
<html lang="ja" class="scroll-smooth">
<head>
    @php
        $setting = $setting ?? \App\Models\SalonSetting::current();
        $design = $design ?? \App\Models\DesignSetting::current();
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
    @endphp
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
        #concept, #menu, #gallery, #staff, #access, #news { scroll-margin-top: 5.5rem; }
        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
        }
    </style>
</head>
<body class="font-sans">
    <header class="sticky top-0 z-50 border-b border-salon-line bg-salon-bg/95 backdrop-blur">
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

            <nav class="hidden items-center gap-8 text-sm md:flex" aria-label="メインメニュー">
                <a href="{{ url('/#concept') }}" class="hover:text-salon-accent">Concept</a>
                <a href="{{ url('/#menu') }}" class="hover:text-salon-accent">Menu</a>
                <a href="{{ url('/#gallery') }}" class="hover:text-salon-accent">Gallery</a>
                <a href="{{ url('/#staff') }}" class="hover:text-salon-accent">Staff</a>
                <a href="{{ url('/#access') }}" class="hover:text-salon-accent">Access</a>
                <a href="{{ route('news.index') }}" class="hover:text-salon-accent">News</a>
            </nav>

            @if($setting->hot_pepper_url)
                <a href="{{ $setting->hot_pepper_url }}" target="_blank" rel="noopener" class="btn-primary hidden md:inline-flex">Reserve</a>
            @endif

            <button type="button" id="mobile-menu-btn" class="md:hidden text-salon-text" aria-label="メニュー">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
        </div>

        <div id="mobile-menu" class="hidden border-t border-salon-line md:hidden">
            <nav class="flex flex-col gap-4 px-4 py-4 text-sm" aria-label="モバイルメニュー">
                <a href="{{ url('/#concept') }}" data-nav-link>Concept</a>
                <a href="{{ url('/#menu') }}" data-nav-link>Menu</a>
                <a href="{{ url('/#gallery') }}" data-nav-link>Gallery</a>
                <a href="{{ url('/#staff') }}" data-nav-link>Staff</a>
                <a href="{{ url('/#access') }}" data-nav-link>Access</a>
                <a href="{{ route('news.index') }}" data-nav-link>News</a>
                @if($setting->hot_pepper_url)
                    <a href="{{ $setting->hot_pepper_url }}" target="_blank" class="btn-primary text-center">Reserve</a>
                @endif
            </nav>
        </div>
    </header>

    <main>@yield('content')</main>

    <footer class="border-t border-salon-line bg-white/50">
        <div class="mx-auto max-w-6xl px-4 py-12 md:px-6">
            <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="font-serif text-lg">{{ $setting->shop_name }}</p>
                    <p class="mt-2 text-sm text-salon-muted">{{ $setting->address }}</p>
                </div>
                <div class="flex flex-col items-start gap-4 text-sm sm:flex-row sm:items-center sm:gap-6">
                    <a href="{{ route('privacy') }}" class="hover:text-salon-accent">Privacy Policy</a>
                    <x-social-links variant="footer" />
                </div>
            </div>
            <p class="mt-8 text-center text-xs text-salon-muted">&copy; {{ date('Y') }} {{ $setting->shop_name }}</p>
        </div>
    </footer>

    <script>
        (function () {
            const mobileMenu = document.getElementById('mobile-menu');
            document.getElementById('mobile-menu-btn')?.addEventListener('click', function () {
                mobileMenu?.classList.toggle('hidden');
            });

            document.querySelectorAll('[data-nav-link]').forEach(function (link) {
                link.addEventListener('click', function () {
                    mobileMenu?.classList.add('hidden');
                });
            });

            function scrollToHashTarget() {
                const id = window.location.hash.replace(/^#/, '');
                if (!id) return;
                const target = document.getElementById(id);
                if (!target) return;
                target.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' });
            }

            window.addEventListener('load', scrollToHashTarget);
            window.addEventListener('hashchange', scrollToHashTarget);
        })();
    </script>
</body>
</html>
