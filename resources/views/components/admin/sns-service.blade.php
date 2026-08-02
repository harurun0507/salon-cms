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
    <div class="sns-account-heading">
        {{ $icon }}
        <span class="sns-account-name">{{ $name }}</span>
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
        <p class="sns-account-help">{{ $help }}</p>
    @endif
</div>
