@props([
    'links' => null,
    'variant' => 'footer',
])

@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\SocialLink> $items */
    $items = $links instanceof \Illuminate\Support\Collection
        ? $links
        : \App\Models\SocialLink::visibleForPublic();
@endphp

@if($items->isNotEmpty())
    @if($variant === 'access')
        <div {{ $attributes->class('flex flex-wrap gap-3') }}>
            @foreach($items as $link)
                <a
                    href="{{ $link->url }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="btn-outline inline-flex items-center gap-2"
                >
                    <x-social-icon :service="$link->service_key" class="h-4 w-4" />
                    <span>{{ $link->label() }}</span>
                </a>
            @endforeach
        </div>
    @elseif($variant === 'icons')
        <div {{ $attributes->class('site-social-icons') }}>
            @foreach($items as $link)
                <a
                    href="{{ $link->url }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="site-social-icons__link"
                    aria-label="{{ $link->label() }}"
                    title="{{ $link->label() }}"
                >
                    <x-social-icon :service="$link->service_key" class="site-social-icons__icon" />
                </a>
            @endforeach
        </div>
    @else
        <div {{ $attributes->class('flex flex-wrap items-center gap-4 text-sm') }}>
            @foreach($items as $link)
                <a
                    href="{{ $link->url }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-1.5 hover:text-salon-accent"
                    aria-label="{{ $link->label() }}"
                >
                    <x-social-icon :service="$link->service_key" class="h-4 w-4" />
                    <span>{{ $link->label() }}</span>
                </a>
            @endforeach
        </div>
    @endif
@endif
