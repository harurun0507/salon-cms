@props([
    'href' => null,
    'disabled' => false,
])

@php
    $icon = '<svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 3a1 1 0 0 1 1 1v5h5a1 1 0 1 1 0 2h-5v5a1 1 0 1 1-2 0v-5H4a1 1 0 1 1 0-2h5V4a1 1 0 0 1 1-1Z"/></svg>';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class('btn-admin-create') }} @if($disabled) aria-disabled="true" tabindex="-1" @endif>
        {!! $icon !!}
        <span>{{ $slot->isEmpty() ? '新規登録' : $slot }}</span>
    </a>
@else
    <button type="button" {{ $attributes->class('btn-admin-create') }} @disabled($disabled)>
        {!! $icon !!}
        <span>{{ $slot->isEmpty() ? '新規登録' : $slot }}</span>
    </button>
@endif
