@extends('layouts.public')

@section('title', 'メニュー・料金')

@section('content')
    <section class="site-section">
        <div class="mx-auto max-w-5xl px-4 md:px-6">
            <p class="mb-2 text-sm tracking-widest text-salon-accent">Menu</p>
            <h1 class="section-title mb-10">メニュー・料金</h1>

            @if($categories->isNotEmpty())
                <div class="menu-category-nav-bar" data-menu-category-nav>
                    <nav class="menu-category-nav" aria-label="メニューカテゴリ">
                        @foreach($categories as $category)
                            <a
                                href="#menu-category-{{ $category->id }}"
                                class="menu-category-nav-link"
                                data-menu-category-link
                            >{{ $category->name }}</a>
                        @endforeach
                    </nav>
                </div>
            @endif

            @forelse($categories as $category)
                @php
                    $englishName = $category->englishName();
                @endphp
                <section id="menu-category-{{ $category->id }}" class="menu-category-block" data-menu-category-section>
                    <header class="menu-category-heading">
                        @if($englishName)
                            <p class="menu-category-heading-en">{{ $englishName }}</p>
                        @endif
                        <h2 class="menu-category-heading-ja">{{ $category->name }}</h2>
                    </header>
                    <ul class="menu-price-list">
                        @foreach($category->publishedMenus as $menu)
                            <li class="menu-price-item">
                                <div class="menu-price-row">
                                    <span class="menu-price-name">{{ $menu->name }}</span>
                                    <span class="menu-price-leader" aria-hidden="true"></span>
                                    @if($menu->isInquiryPrice())
                                        <span class="menu-price-inquiry">{{ $menu->price }}</span>
                                    @elseif(filled($menu->price))
                                        <span class="menu-price-value">{{ $menu->price }}</span>
                                    @endif
                                </div>
                                @if($menu->description)
                                    <p class="menu-price-desc">{{ $menu->description }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>
            @empty
                <p class="text-salon-muted">メニュー情報を準備中です。</p>
            @endforelse
        </div>
    </section>

    @if($categories->isNotEmpty())
        <script>
            (function () {
                const root = document.documentElement;
                const header = document.querySelector('body > header');
                const navBar = document.querySelector('[data-menu-category-nav]');
                const nav = navBar ? navBar.querySelector('.menu-category-nav') : null;
                const links = Array.from(document.querySelectorAll('[data-menu-category-link]'));
                if (!links.length || !navBar) return;

                function updateStickyOffsets() {
                    const headerHeight = header ? header.offsetHeight : 0;
                    const navHeight = navBar.offsetHeight || 0;
                    root.style.setProperty('--site-header-offset', headerHeight + 'px');
                    root.style.setProperty('--menu-category-nav-height', navHeight + 'px');
                    root.style.setProperty(
                        '--menu-category-scroll-margin',
                        (headerHeight + navHeight + 12) + 'px'
                    );
                }

                function scrollTabIntoView(link) {
                    if (!nav || !link) return;
                    const navRect = nav.getBoundingClientRect();
                    const linkRect = link.getBoundingClientRect();
                    const delta = ((linkRect.left + linkRect.right) / 2) - ((navRect.left + navRect.right) / 2);
                    nav.scrollLeft += delta;
                }

                function setActive(hash, options) {
                    const shouldScrollTab = !!(options && options.scrollTab);
                    let activeLink = null;

                    links.forEach(function (link) {
                        const active = link.getAttribute('href') === hash;
                        link.classList.toggle('is-active', active);
                        if (active) {
                            link.setAttribute('aria-current', 'true');
                            activeLink = link;
                        } else {
                            link.removeAttribute('aria-current');
                        }
                    });

                    if (shouldScrollTab && activeLink) {
                        scrollTabIntoView(activeLink);
                    }
                }

                function resolveHash() {
                    const hash = window.location.hash;
                    if (hash && document.querySelector(hash + '[data-menu-category-section]')) {
                        return hash;
                    }
                    return null;
                }

                function syncFromLocation(options) {
                    const hash = resolveHash();
                    if (hash) {
                        setActive(hash, options);
                        return true;
                    }
                    return false;
                }

                links.forEach(function (link) {
                    link.addEventListener('click', function () {
                        // Keep native anchor smooth-scroll; only lock the active tab to the click target.
                        setActive(link.getAttribute('href'), { scrollTab: true });
                    });
                });

                window.addEventListener('hashchange', function () {
                    syncFromLocation({ scrollTab: true });
                });
                window.addEventListener('popstate', function () {
                    syncFromLocation({ scrollTab: true });
                });
                window.addEventListener('resize', updateStickyOffsets);
                if (window.ResizeObserver) {
                    if (header) new ResizeObserver(updateStickyOffsets).observe(header);
                    new ResizeObserver(updateStickyOffsets).observe(navBar);
                }

                updateStickyOffsets();

                if (!syncFromLocation({ scrollTab: !!window.location.hash }) && links[0]) {
                    setActive(links[0].getAttribute('href'));
                }
            })();
        </script>
    @endif
@endsection
