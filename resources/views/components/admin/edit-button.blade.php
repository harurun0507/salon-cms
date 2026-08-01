@props([
    'href' => null,
])

@php
    $icon = '<svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M2.695 14.763l-1.262 3.154a.5.5 0 0 0 .65.65l3.155-1.262a4 4 0 0 0 1.343-.885L17.5 5.5a2.121 2.121 0 0 0-3-3L3.58 13.42a4 4 0 0 0-.885 1.343Z"/></svg>';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class('btn-admin-edit') }}>
        {!! $icon !!}
        <span>{{ $slot->isEmpty() ? '編集' : $slot }}</span>
    </a>
@else
    <button type="button" {{ $attributes->class('btn-admin-edit') }}>
        {!! $icon !!}
        <span>{{ $slot->isEmpty() ? '編集' : $slot }}</span>
    </button>
@endif
