@props(['name'])

@php
    $class = 'h-[18px] w-[18px] shrink-0';
    $svgAttrs = 'viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"';
@endphp

@switch($name)
    @case('dashboard')
        {{-- Lucide: Home --}}
        <svg {{ $attributes->merge(['class' => $class]) }} {!! $svgAttrs !!}>
            <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
            <polyline points="9 22 9 12 15 12 15 22" />
        </svg>
        @break

    @case('news')
        {{-- Lucide: Bell --}}
        <svg {{ $attributes->merge(['class' => $class]) }} {!! $svgAttrs !!}>
            <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9" />
            <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0" />
        </svg>
        @break

    @case('gallery')
        {{-- Lucide: Image --}}
        <svg {{ $attributes->merge(['class' => $class]) }} {!! $svgAttrs !!}>
            <rect width="18" height="18" x="3" y="3" rx="2" ry="2" />
            <circle cx="9" cy="9" r="2" />
            <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21" />
        </svg>
        @break

    @case('menus')
        {{-- Lucide: Scissors --}}
        <svg {{ $attributes->merge(['class' => $class]) }} {!! $svgAttrs !!}>
            <circle cx="6" cy="6" r="3" />
            <circle cx="6" cy="18" r="3" />
            <line x1="20" x2="8.12" y1="4" y2="15.88" />
            <line x1="14.47" x2="20" y1="14.48" y2="20" />
            <line x1="8.12" x2="12" y1="8.12" y2="12" />
        </svg>
        @break

    @case('staff')
        {{-- Lucide: Users --}}
        <svg {{ $attributes->merge(['class' => $class]) }} {!! $svgAttrs !!}>
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
            <circle cx="9" cy="7" r="4" />
            <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
        </svg>
        @break

    @case('settings')
        {{-- Lucide: Store --}}
        <svg {{ $attributes->merge(['class' => $class]) }} {!! $svgAttrs !!}>
            <path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7" />
            <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8" />
            <path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4" />
            <path d="M2 7h20" />
            <path d="M22 7v3a2 2 0 0 1-2 2 2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 16 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 12 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 8 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 4 12a2 2 0 0 1-2-2V7" />
        </svg>
        @break

    @case('external')
        {{-- Lucide: ExternalLink --}}
        <svg {{ $attributes->merge(['class' => $class]) }} {!! $svgAttrs !!}>
            <path d="M15 3h6v6" />
            <path d="M10 14 21 3" />
            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />
        </svg>
        @break
@endswitch
