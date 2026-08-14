@props([
    'eyebrow',
    'title',
    'backHref',
])

<header {{ $attributes->class('site-list-page-header') }}>
    <div class="site-list-page-header__copy">
        <p class="site-list-page-header__eyebrow">{{ $eyebrow }}</p>
        <h1 class="site-list-page-header__title section-title">{{ $title }}</h1>
    </div>
    <div class="site-list-page-header__action">
        <x-history-back-link :href="$backHref" />
    </div>
</header>
