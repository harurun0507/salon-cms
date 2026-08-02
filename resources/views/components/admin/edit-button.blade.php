@props([
    'href' => null,
])

@php
    $label = $slot->isEmpty() ? '編集' : (string) $slot;
    $icon = '<svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M13.586 3.586a2 2 0 1 1 2.828 2.828l-.793.793-2.828-2.828.793-.793ZM11.379 5.793 3 14.172V17h2.828l8.38-8.379-2.829-2.828Z"/></svg>';
@endphp

@if ($href)
    <a
        href="{{ $href }}"
        {{ $attributes->class(['admin-icon-btn', 'admin-icon-btn-edit'])->merge(['aria-label' => $label, 'title' => $label]) }}
    >
        {!! $icon !!}
    </a>
@else
    <button
        type="button"
        {{ $attributes->class(['admin-icon-btn', 'admin-icon-btn-edit'])->merge(['aria-label' => $label, 'title' => $label]) }}
    >
        {!! $icon !!}
    </button>
@endif
