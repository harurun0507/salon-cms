@props([
    'blog',
])

@php
    $salon = $setting ?? \App\Models\SalonSetting::current();
    $hasEyeCatch = $blog->hasEyeCatch();
    $hasLogo = filled($salon->logo_image);
    $shopName = trim((string) $salon->shop_name);
@endphp

<div {{ $attributes->class([
    'blog-eyecatch',
    'blog-eyecatch--has-image' => $hasEyeCatch,
    'blog-eyecatch--placeholder' => ! $hasEyeCatch,
]) }}>
    <div class="blog-eyecatch-top">
        <div class="blog-eyecatch-tab">
            <p class="blog-card-label">BLOG</p>
        </div>
        <div class="blog-eyecatch-ledge" aria-hidden="true"></div>
    </div>
    <div class="blog-eyecatch-media">
        @if ($hasEyeCatch)
            <img
                src="{{ asset('storage/'.$blog->eye_catch_image_path) }}"
                alt=""
                class="blog-eyecatch-image"
                loading="lazy"
            >
        @else
            <div class="blog-eyecatch-placeholder" aria-hidden="true">
                @if ($hasLogo)
                    <img
                        src="{{ asset('storage/'.$salon->logo_image) }}"
                        alt=""
                        class="blog-eyecatch-placeholder-logo"
                    >
                @elseif ($shopName !== '')
                    <p class="blog-eyecatch-placeholder-shop">{{ $shopName }}</p>
                @endif
            </div>
        @endif
    </div>
</div>
