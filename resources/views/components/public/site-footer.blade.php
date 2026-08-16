@props([
    'setting',
])

@php
    $isVerticalIndicator = ($design ?? \App\Models\DesignSetting::current())->usesVerticalScrollIndicator();
@endphp

{{-- Shared public site footer. VI hides SNS (already in header) and stays compact. --}}
<footer @class([
    'site-footer',
    'border-t border-salon-line bg-white/50' => ! $isVerticalIndicator,
    'site-footer--vi' => $isVerticalIndicator,
])>
    <div @class([
        'mx-auto max-w-6xl px-4 md:px-6',
        'py-5' => $isVerticalIndicator,
        'py-12' => ! $isVerticalIndicator,
    ])>
        @if($isVerticalIndicator)
            <div class="site-footer__vi-bar text-sm">
                <p class="site-footer__vi-copy site-footer__muted">&copy; {{ date('Y') }} {{ $setting->shop_name }}</p>
                <a href="{{ route('privacy') }}" class="site-footer__vi-privacy site-footer__link">Privacy Policy</a>
            </div>
        @else
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
        @endif
    </div>
</footer>
