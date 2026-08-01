@props([
    'title',
    'description' => null,
    'variant' => 'leaf',
])

@php
    $iconClass = 'h-14 w-14';
@endphp

<div {{ $attributes->class('admin-empty-state') }}>
    <div class="admin-empty-state-icon" aria-hidden="true">
        @switch($variant)
            @case('photo')
                {{-- Photo frame among thin leafy branches --}}
                <svg class="{{ $iconClass }}" viewBox="0 0 80 80" fill="none" stroke="#B8B09F" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 52c-4-8-6-16-4-24 3-10 12-16 20-14" stroke-width="1.3" opacity="0.7"/>
                    <path d="M18 34c-2 4-2 9 1 13" stroke-width="1.2" opacity="0.55"/>
                    <path d="M22 28c4-1 8 1 10 5" stroke-width="1.2" opacity="0.55"/>
                    <path d="M66 18c6 8 8 18 4 28-4 10-14 16-22 14" stroke-width="1.3" opacity="0.7"/>
                    <path d="M58 28c3 3 4 8 2 12" stroke-width="1.2" opacity="0.55"/>
                    <path d="M62 36c-4 2-7 6-8 10" stroke-width="1.2" opacity="0.55"/>
                    <rect x="22" y="22" width="36" height="36" rx="3.5" stroke-width="1.4"/>
                    <circle cx="32" cy="33" r="3.2" stroke-width="1.3"/>
                    <path d="M26 50l9-9a4 4 0 0 1 5.5 0l13.5 13.5" stroke-width="1.3"/>
                    <path d="M44 44l3.5-3.5a3.5 3.5 0 0 1 5 0L58 46" stroke-width="1.3"/>
                </svg>
                @break

            @case('users')
                {{-- Soft people / plant motif --}}
                <svg class="{{ $iconClass }}" viewBox="0 0 80 80" fill="none" stroke="#B8B09F" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M40 58V36" stroke-width="1.3"/>
                    <path d="M40 42c6-3 11-8 13-14" stroke-width="1.25" opacity="0.75"/>
                    <path d="M40 48c-6-2.5-10-7-12-12" stroke-width="1.25" opacity="0.75"/>
                    <path d="M40 36c-5-8-3-16 2-20 6 2 10 9 8 16-2 3-5 4-10 4Z" stroke-width="1.3" opacity="0.65"/>
                    <path d="M40 36c5-8 3-16-2-20-6 2-10 9-8 16 2 3 5 4 10 4Z" stroke-width="1.3" opacity="0.65"/>
                    <circle cx="32" cy="50" r="5.5" stroke-width="1.35"/>
                    <path d="M22 66c1.5-6 5-10 10-10s8.5 4 10 10" stroke-width="1.35"/>
                    <circle cx="52" cy="48" r="4.5" stroke-width="1.3" opacity="0.85"/>
                    <path d="M44 66c1-5 4-8.5 8-8.5s7 3.5 8 8.5" stroke-width="1.3" opacity="0.85"/>
                </svg>
                @break

            @case('menu')
                {{-- Botanical + scissors motif --}}
                <svg class="{{ $iconClass }}" viewBox="0 0 80 80" fill="none" stroke="#B8B09F" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 58c-3-10-2-22 6-30 8-8 20-10 28-6" stroke-width="1.25" opacity="0.55"/>
                    <path d="M28 36c4-6 10-10 16-10" stroke-width="1.2" opacity="0.5"/>
                    <path d="M24 46c6-4 14-6 22-4" stroke-width="1.2" opacity="0.5"/>
                    <circle cx="28" cy="28" r="6" stroke-width="1.4"/>
                    <circle cx="28" cy="52" r="6" stroke-width="1.4"/>
                    <path d="M33 32.5 56 52" stroke-width="1.4"/>
                    <path d="M33 47.5 56 28" stroke-width="1.4"/>
                    <path d="M40 40h.01" stroke-width="2"/>
                    <path d="M58 24c4 2 8 7 8 13 0 4-2 8-5 10" stroke-width="1.25" opacity="0.7"/>
                    <path d="M58 24c-1 5 1 10 5 13" stroke-width="1.2" opacity="0.55"/>
                </svg>
                @break

            @default
                {{-- Delicate plant sprig (news / leaf) --}}
                <svg class="{{ $iconClass }}" viewBox="0 0 80 80" fill="none" stroke="#B8B09F" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M40 64V28" stroke-width="1.4"/>
                    <path d="M40 34c10-4 18-12 20-22-12 1-22 8-26 18 2 2 4 3.5 6 4Z" stroke-width="1.35"/>
                    <path d="M40 42c-10-3.5-17-11-19-20 11-.5 20 6 24 15-1.5 2-3.5 3.5-5 5Z" stroke-width="1.35"/>
                    <path d="M40 50c8-3 14-9 16-16-8 0-14 4-17 10 0.5 2 1 4 1 6Z" stroke-width="1.3" opacity="0.85"/>
                    <path d="M40 54c-7-2.5-12-7.5-14-13 7 .2 12 3.5 15 8 .2 1.5.2 3.5-.1 5Z" stroke-width="1.3" opacity="0.85"/>
                    <path d="M40 28c1.5-6 5-11 10-14" stroke-width="1.2" opacity="0.6"/>
                </svg>
        @endswitch
    </div>

    <p class="admin-empty-state-title">{{ $title }}</p>

    @if ($description)
        <p class="admin-empty-state-desc">{{ $description }}</p>
    @endif

    @if (! $slot->isEmpty())
        <div class="admin-empty-state-actions">
            {{ $slot }}
        </div>
    @endif
</div>
