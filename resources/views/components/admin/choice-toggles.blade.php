@props([
    'name',
    'options' => [],
    'selected' => [],
    'ariaLabel' => null,
    'variant' => 'auto',
    'inputDataAttribute' => null,
    'disabled' => false,
])

@php
    $selectedValues = collect($selected)->map(fn ($id) => (string) $id)->all();
    $choicesClass = $variant === 'equal'
        ? 'admin-choice-choices news-weekday-choices'
        : 'admin-choice-choices admin-choice-choices--auto news-weekday-choices news-weekday-choices--auto';
@endphp

<div
    {{ $attributes->class([$choicesClass, 'mt-1', $disabled ? 'pointer-events-none opacity-80' : null]) }}
    role="group"
    @if($ariaLabel) aria-label="{{ $ariaLabel }}" @endif
    @if($disabled) aria-disabled="true" @endif
>
    @foreach($options as $option)
        @php
            $value = (string) (is_array($option) ? ($option['value'] ?? $option['id'] ?? '') : ($option->id ?? ''));
            $label = (string) (is_array($option) ? ($option['label'] ?? $option['name'] ?? $value) : ($option->name ?? $value));
            $checked = in_array($value, $selectedValues, true);
            $allowMultiple = is_array($option)
                ? ! empty($option['allow_multiple'])
                : (bool) ($option->allow_multiple_selection ?? false);
        @endphp
        <label class="admin-choice-option news-weekday-option">
            <input
                type="checkbox"
                name="{{ $name }}"
                value="{{ $value }}"
                class="admin-choice-input news-weekday-input"
                @if($inputDataAttribute) {{ $inputDataAttribute }} @endif
                data-allow-multiple="{{ $allowMultiple ? '1' : '0' }}"
                @checked($checked)
                @disabled($disabled)
            >
            <span class="admin-choice-face news-weekday-face">{{ $label }}</span>
        </label>
    @endforeach
</div>
