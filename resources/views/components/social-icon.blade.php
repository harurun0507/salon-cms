@props([
    'service',
    'class' => 'h-5 w-5',
])

@php
    $service = (string) $service;
@endphp

@switch($service)
    @case('instagram')
        <svg {{ $attributes->class($class) }} viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <rect x="3.5" y="3.5" width="17" height="17" rx="5" stroke="currentColor" stroke-width="1.5"/>
            <circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="1.5"/>
            <circle cx="17.25" cy="6.75" r="1" fill="currentColor"/>
        </svg>
        @break

    @case('line')
        <svg {{ $attributes->class($class) }} viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M12 4.5c-4.7 0-8.5 3.05-8.5 6.8 0 3.36 2.98 6.18 7 6.7.27.06.64.18.73.42.08.22.05.56 0 .78l-.22.95c-.07.28-.3 1.1.96.6 1.26-.5 6.8-4.01 9.28-6.87C23.1 11.7 20.3 4.5 12 4.5Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
            <path d="M8.2 10.2h1.2v3.6H8.2v-3.6Zm2.35 0H13c.66 0 1.15.42 1.15 1.05S13.66 12.3 13 12.3h-1.25v1.5h-1.2v-3.6Zm3.55 0h1.2l1.35 1.95V10.2h1.15v3.6h-1.15l-1.4-2.05v2.05h-1.15v-3.6Zm-4.7 0h1.2v2.4h1.05v1.2H9.4v-3.6Z" fill="currentColor"/>
        </svg>
        @break

    @case('youtube')
        <svg {{ $attributes->class($class) }} viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <rect x="2.75" y="6.25" width="18.5" height="11.5" rx="3" stroke="currentColor" stroke-width="1.5"/>
            <path d="M10.5 9.75v4.5L15 12l-4.5-2.25Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
        </svg>
        @break

    @case('tiktok')
        <svg {{ $attributes->class($class) }} viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M14 5.5c.6 2.2 2.2 3.7 4.5 4.1v2.35c-1.55-.1-2.95-.65-4.1-1.55v5.35A4.75 4.75 0 1 1 9.7 10.9v2.45a2.35 2.35 0 1 0 1.95 2.32V5.5H14Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
        </svg>
        @break

    @case('facebook')
        <svg {{ $attributes->class($class) }} viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M14.5 8.5V6.8c0-.9.2-1.4 1.45-1.4H17.5V3h-2.2C12.3 3 11 4.55 11 6.95V8.5H9v2.7h2V21h3.5v-9.8h2.35l.35-2.7H14.5Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
        </svg>
        @break

    @case('x')
        <svg {{ $attributes->class($class) }} viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M5 5.5 10.8 12.2 5.2 18.5h1.85l4.55-5.2 3.7 5.2H19L12.95 11.5 18.3 5.5h-1.85l-4.25 4.85L8.75 5.5H5Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
        </svg>
        @break

    @case('threads')
        <svg {{ $attributes->class($class) }} viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M12.1 4.5c3.9 0 6.4 2.35 6.4 6.05 0 4.45-3.35 7.7-7.55 7.7-2.55 0-4.55-1.05-5.7-2.85" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            <path d="M9.2 12.15c.35-2.55 1.95-3.85 3.85-3.85 1.55 0 2.7.95 2.7 2.35 0 1.7-1.35 2.85-3.55 3.55-1.35.45-2.2.95-2.2 1.85 0 .85.8 1.45 2.05 1.45 1.7 0 3.05-.95 3.7-2.55" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
        @break

    @default
        <svg {{ $attributes->class($class) }} viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle cx="12" cy="12" r="8.25" stroke="currentColor" stroke-width="1.5"/>
            <path d="M4.5 12h15M12 4.5c2.4 2.4 3.6 4.9 3.6 7.5s-1.2 5.1-3.6 7.5M12 4.5C9.6 6.9 8.4 9.4 8.4 12s1.2 5.1 3.6 7.5" stroke="currentColor" stroke-width="1.5"/>
        </svg>
@endswitch
