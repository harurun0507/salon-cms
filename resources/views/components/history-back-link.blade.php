@props([
    'href',
])

<a
    href="{{ $href }}"
    {{ $attributes->class('btn-outline site-back-link--history') }}
>
    <svg class="site-back-link-icon" viewBox="0 0 20 20" fill="none" aria-hidden="true">
        <path
            d="M12.5 4.5 7 10l5.5 5.5"
            stroke="currentColor"
            stroke-width="1.6"
            stroke-linecap="round"
            stroke-linejoin="round"
        />
    </svg>
    <span>{{ $slot->isEmpty() ? '戻る' : $slot }}</span>
</a>
