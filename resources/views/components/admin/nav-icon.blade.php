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

    @case('home')
        {{-- Lucide: Home --}}
        <svg {{ $attributes->merge(['class' => $class]) }} {!! $svgAttrs !!}>
            <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
            <polyline points="9 22 9 12 15 12 15 22" />
        </svg>
        @break

    @case('news')
    @case('bell')
        {{-- Lucide: Bell --}}
        <svg {{ $attributes->merge(['class' => $class]) }} {!! $svgAttrs !!}>
            <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9" />
            <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0" />
        </svg>
        @break

    @case('gallery')
    @case('image')
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

    @case('list')
        {{-- Lucide: List --}}
        <svg {{ $attributes->merge(['class' => $class]) }} {!! $svgAttrs !!}>
            <line x1="8" x2="21" y1="6" y2="6" />
            <line x1="8" x2="21" y1="12" y2="12" />
            <line x1="8" x2="21" y1="18" y2="18" />
            <line x1="3" x2="3.01" y1="6" y2="6" />
            <line x1="3" x2="3.01" y1="12" y2="12" />
            <line x1="3" x2="3.01" y1="18" y2="18" />
        </svg>
        @break

    @case('staff')
    @case('users')
        {{-- Lucide: Users --}}
        <svg {{ $attributes->merge(['class' => $class]) }} {!! $svgAttrs !!}>
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
            <circle cx="9" cy="7" r="4" />
            <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
        </svg>
        @break

    @case('user')
        {{-- Lucide: User --}}
        <svg {{ $attributes->merge(['class' => $class]) }} {!! $svgAttrs !!}>
            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
            <circle cx="12" cy="7" r="4" />
        </svg>
        @break

    @case('home-page')
        {{-- Lucide: LayoutTemplate --}}
        <svg {{ $attributes->merge(['class' => $class]) }} {!! $svgAttrs !!}>
            <rect width="18" height="7" x="3" y="3" rx="1" />
            <rect width="9" height="9" x="3" y="14" rx="1" />
            <rect width="5" height="9" x="16" y="14" rx="1" />
        </svg>
        @break

    @case('document')
        {{-- Lucide: FileText --}}
        <svg {{ $attributes->merge(['class' => $class]) }} {!! $svgAttrs !!}>
            <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z" />
            <path d="M14 2v4a2 2 0 0 0 2 2h4" />
            <path d="M10 9H8" />
            <path d="M16 13H8" />
            <path d="M16 17H8" />
        </svg>
        @break

    @case('megaphone')
        {{-- Lucide: Megaphone --}}
        <svg {{ $attributes->merge(['class' => $class]) }} {!! $svgAttrs !!}>
            <path d="m3 11 18-5v12L3 14v-3z" />
            <path d="M11.6 16.8a3 3 0 1 1-5.8-1.6" />
        </svg>
        @break

    @case('share')
        {{-- Lucide: Share2 --}}
        <svg {{ $attributes->merge(['class' => $class]) }} {!! $svgAttrs !!}>
            <circle cx="18" cy="5" r="3" />
            <circle cx="6" cy="12" r="3" />
            <circle cx="18" cy="19" r="3" />
            <line x1="8.59" x2="15.42" y1="13.51" y2="17.49" />
            <line x1="15.41" x2="8.59" y1="6.51" y2="10.49" />
        </svg>
        @break

    @case('calendar')
        {{-- Lucide: Calendar --}}
        <svg {{ $attributes->merge(['class' => $class]) }} {!! $svgAttrs !!}>
            <path d="M8 2v4" />
            <path d="M16 2v4" />
            <rect width="18" height="18" x="3" y="4" rx="2" />
            <path d="M3 10h18" />
        </svg>
        @break

    @case('search')
        {{-- Lucide: Search --}}
        <svg {{ $attributes->merge(['class' => $class]) }} {!! $svgAttrs !!}>
            <circle cx="11" cy="11" r="8" />
            <path d="m21 21-4.3-4.3" />
        </svg>
        @break

    @case('chart')
        {{-- Lucide: BarChart3 --}}
        <svg {{ $attributes->merge(['class' => $class]) }} {!! $svgAttrs !!}>
            <path d="M3 3v18h18" />
            <path d="M18 17V9" />
            <path d="M13 17V5" />
            <path d="M8 17v-3" />
        </svg>
        @break

    @case('palette')
        {{-- Lucide: Palette --}}
        <svg {{ $attributes->merge(['class' => $class]) }} {!! $svgAttrs !!}>
            <circle cx="13.5" cy="6.5" r=".5" fill="currentColor" />
            <circle cx="17.5" cy="10.5" r=".5" fill="currentColor" />
            <circle cx="8.5" cy="7.5" r=".5" fill="currentColor" />
            <circle cx="6.5" cy="12.5" r=".5" fill="currentColor" />
            <path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z" />
        </svg>
        @break

    @case('content')
        {{-- Lucide: Layers --}}
        <svg {{ $attributes->merge(['class' => $class]) }} {!! $svgAttrs !!}>
            <path d="m12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83Z" />
            <path d="m22 12.5-8.58 3.91a2 2 0 0 1-1.66 0L2.6 12.5" />
            <path d="m22 17.5-8.58 3.91a2 2 0 0 1-1.66 0L2.6 17.5" />
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

    @case('system')
        {{-- Lucide: Settings --}}
        <svg {{ $attributes->merge(['class' => $class]) }} {!! $svgAttrs !!}>
            <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z" />
            <circle cx="12" cy="12" r="3" />
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
