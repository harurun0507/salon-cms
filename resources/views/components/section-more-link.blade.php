@props([
    'href',
])

<a href="{{ $href }}" {{ $attributes->class('btn-outline') }}>{{ $slot }}</a>
