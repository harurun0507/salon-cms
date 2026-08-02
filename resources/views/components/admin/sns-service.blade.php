@props([
    'name',
    'field',
    'fieldLabel' => 'プロフィールURL',
    'value' => '',
    'placeholder' => null,
    'help' => null,
])

{{-- Reusable SNS service block (Instagram / LINE / TikTok / …) --}}
<div {{ $attributes->class('sns-service') }}>
    <div class="sns-service__header">
        <span class="sns-service__icon" aria-hidden="true">
            {{ $icon }}
        </span>
        <span class="sns-service__name">{{ $name }}</span>
    </div>

    <div class="sns-service__body">
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
            <p class="sns-service__help">{{ $help }}</p>
        @endif
    </div>
</div>
