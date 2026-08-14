@php
    $menuCategories = collect($categories ?? []);
    $setCategory = $menuCategories->first(fn ($category) => $category->isCombination());
    $singleCategories = $menuCategories
        ->filter(fn ($category) => ! $category->isCombination())
        ->values();
    $setMenus = $setCategory
        ? $setCategory->publishedMenus->take(3)->values()
        : collect();
    $setUrl = $setCategory
        ? route('menu').'#'.$setCategory->publicAnchorSlug()
        : route('menu');
@endphp

<section id="menu" class="home-vi-menu" aria-label="Menu">
    <div class="home-vi-menu__inner">
        <header class="home-vi-menu__head">
            <p class="home-vi-kicker">Menu</p>
            <h2 class="home-vi-menu__title">メニュー・料金</h2>
        </header>

        <div class="home-vi-menu__stage">
            @if($setCategory && $setMenus->isNotEmpty())
                <a
                    href="{{ $setUrl }}"
                    class="home-vi-menu__set"
                    data-menu-modal-trigger
                    data-menu-category-id="{{ $setCategory->id }}"
                    aria-label="{{ $setCategory->name }}の詳細を見る"
                >
                    <header class="home-vi-menu__set-head">
                        <div class="home-vi-menu__set-titles">
                            @if($setCategory->englishName())
                                <p class="home-vi-menu__set-en">{{ $setCategory->englishName() }}</p>
                            @endif
                            <h3 class="home-vi-menu__set-ja">{{ $setCategory->name }}</h3>
                        </div>
                        <span class="home-vi-menu__set-more" aria-hidden="true">
                            <svg viewBox="0 0 20 20" fill="none">
                                <path d="M7.5 4.5 13 10l-5.5 5.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                    </header>

                    <ul class="home-vi-menu__set-list">
                        @foreach($setMenus as $menu)
                            <li class="home-vi-menu__set-item">
                                <span class="home-vi-menu__set-name">{{ $menu->name }}</span>
                                @if($menu->isInquiryPrice())
                                    <span class="home-vi-menu__set-price home-vi-menu__set-price--inquiry">{{ $menu->price }}</span>
                                @elseif(filled($menu->price))
                                    <span class="home-vi-menu__set-price">{{ $menu->price }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </a>
            @endif

            @if($singleCategories->isNotEmpty())
                <nav class="home-vi-menu__cats" aria-label="メニューカテゴリ">
                    @foreach($singleCategories as $category)
                        @php
                            $englishName = $category->englishName();
                            $menuCategoryUrl = route('menu').'#'.$category->publicAnchorSlug();
                        @endphp
                        <a
                            href="{{ $menuCategoryUrl }}"
                            class="home-vi-menu__cat"
                            data-menu-modal-trigger
                            data-menu-category-id="{{ $category->id }}"
                        >
                            <span class="home-vi-menu__cat-text">
                                @if($englishName)
                                    <span class="home-vi-menu__cat-en">{{ $englishName }}</span>
                                @endif
                                <span class="home-vi-menu__cat-ja">{{ $category->name }}</span>
                            </span>
                            <span class="home-vi-menu__cat-arrow" aria-hidden="true">
                                <svg viewBox="0 0 20 20" fill="none">
                                    <path d="M7.5 4.5 13 10l-5.5 5.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                        </a>
                    @endforeach
                </nav>
            @endif
        </div>

        <div class="home-vi-menu__footer">
            <x-section-more-link
                :href="route('menu')"
                data-menu-modal-trigger
                data-menu-view="all"
            >すべて見る →</x-section-more-link>
        </div>
    </div>
</section>
