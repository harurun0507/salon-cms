@props([
    'name',
])

@php
    $iconKey = match (trim((string) $name)) {
        'カット' => 'scissors',
        'カラー' => 'droplet',
        'パーマ' => 'waves',
        '縮毛矯正', 'ストレート' => 'straight',
        'トリートメント' => 'sparkles',
        'ヘッドスパ' => 'user',
        default => 'star',
    };
@endphp

<li {{ $attributes->class(['menu-price-tag']) }}>
    <span class="menu-price-tag-icon" aria-hidden="true">
        @switch($iconKey)
            @case('scissors')
                {{-- Lucide: scissors --}}
                <svg viewBox="0 0 24 24" fill="none">
                    <circle cx="6" cy="6" r="3" stroke="currentColor" stroke-width="1.75"/>
                    <circle cx="6" cy="18" r="3" stroke="currentColor" stroke-width="1.75"/>
                    <path d="M20 4 8.12 15.88M14.47 14.48 20 20M8.12 8.12 12 12" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                @break

            @case('droplet')
                {{-- Lucide: droplet --}}
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M12 2.69 17.66 8.35a8 8 0 1 1-11.32 0L12 2.69Z" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                @break

            @case('waves')
                {{-- Lucide: waves --}}
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M2 6c.6.5 1.2 1 2.5 1C7 7 7 5 9.5 5c1.3 0 1.9.5 2.5 1" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M2 12c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M2 18c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M14.5 6c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 1.1 0 1.7.3 2.2.7" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M14.5 12c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 1.1 0 1.7.3 2.2.7" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M14.5 18c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 1.1 0 1.7.3 2.2.7" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                @break

            @case('straight')
                {{-- Straight hair / straighten (Lucide-style) --}}
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M6 5v14" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                    <path d="M12 4v16" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                    <path d="M18 5v14" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                </svg>
                @break

            @case('sparkles')
                {{-- Lucide: sparkles --}}
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="m12 3 1.6 4.4L18 9l-4.4 1.6L12 15l-1.6-4.4L6 9l4.4-1.6L12 3Z" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="m19 13 .8 2.2L22 16l-2.2.8L19 19l-.8-2.2L16 16l2.2-.8L19 13Z" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="m5 15 .7 1.8L7.5 17.5 5.7 18.2 5 20l-.7-1.8L2.5 17.5l1.8-.7L5 15Z" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                @break

            @case('user')
                {{-- Lucide: user (head / spa) --}}
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="1.75"/>
                </svg>
                @break

            @default
                {{-- Lucide: star --}}
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="m12 3 2.6 5.3 5.9.9-4.2 4.1 1 5.8L12 16.4 6.7 19.1l1-5.8L3.5 9.2l5.9-.9L12 3Z" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
        @endswitch
    </span>
    <span class="menu-price-tag-label">{{ $name }}</span>
</li>
