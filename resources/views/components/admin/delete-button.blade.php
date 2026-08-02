@props([
    'action' => null,
    'form' => null,
    'name' => null,
    'message' => null,
    'method' => 'DELETE',
])

@php
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
        {{ $attributes->class(['admin-icon-btn', 'admin-icon-btn-delete'])->merge(['aria-label' => $label, 'title' => $label]) }}
    >
        <span aria-hidden="true">&times;</span>
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
            {{ $attributes->class(['admin-icon-btn', 'admin-icon-btn-delete'])->merge(['aria-label' => $label, 'title' => $label]) }}
        >
            <span aria-hidden="true">&times;</span>
        </button>
    </form>
@endif
