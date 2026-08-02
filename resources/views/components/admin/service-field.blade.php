@props([
    'name',
    'field',
    'fieldLabel' => 'URL',
    'value' => '',
    'placeholder' => null,
    'help' => null,
])

{{-- Shared admin service block (SNS / 予約サービス / …). Icon + name must stay in one flex parent. --}}
<div {{ $attributes->class('admin-service') }}>
    <div class="admin-service-heading">
        {{ $icon }}
        <span class="admin-service-name">{{ $name }}</span>
    </div>

    <label for="{{ $field }}" class="admin-label">{{ $fieldLabel }}</label>
    <input
        type="url"
        name="{{ $field }}"
        id="{{ $field }}"
        value="{{ $value }}"
        class="admin-input"
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
    >
    @error($field)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
    @if ($help)
        <p class="admin-service-help">{{ $help }}</p>
    @endif
</div>
