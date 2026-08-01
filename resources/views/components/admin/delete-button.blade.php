@props([
    'action' => null,
    'form' => null,
    'name' => null,
    'message' => null,
    'method' => 'DELETE',
])

@php
    $icon = '<svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8.5 3a1.5 1.5 0 0 0-1.415 1H4a1 1 0 0 0 0 2h.293l.72 9.364A2.5 2.5 0 0 0 7.505 18h4.99a2.5 2.5 0 0 0 2.492-2.636L15.707 6H16a1 1 0 1 0 0-2h-3.085A1.5 1.5 0 0 0 11.5 3h-3Zm1 1a.5.5 0 0 0-.5.5V5h2v-.5a.5.5 0 0 0-.5-.5h-1ZM7.3 6l.69 8.97a.5.5 0 0 0 .498.53h3.024a.5.5 0 0 0 .498-.53L12.7 6H7.3Z" clip-rule="evenodd"/></svg>';
    $label = $slot->isEmpty() ? '削除' : (string) $slot;

    if (filled($message)) {
        $deleteMessage = (string) $message;
    } elseif (filled($name)) {
        $deleteMessage = '「'.$name.'」を削除しますか？';
    } else {
        $deleteMessage = 'このデータを削除しますか？';
    }
@endphp

@if ($form)
    <button
        type="button"
        data-admin-delete-trigger
        data-delete-form="{{ $form }}"
        data-delete-message="{{ $deleteMessage }}"
        {{ $attributes->class('btn-admin-delete') }}
    >
        {!! $icon !!}
        <span>{{ $label }}</span>
    </button>
@else
    <form
        action="{{ $action }}"
        method="POST"
        class="inline"
        data-admin-delete-form
    >
        @csrf
        @method($method)
        <button
            type="button"
            data-admin-delete-trigger
            data-delete-message="{{ $deleteMessage }}"
            {{ $attributes->class('btn-admin-delete') }}
        >
            {!! $icon !!}
            <span>{{ $label }}</span>
        </button>
    </form>
@endif
