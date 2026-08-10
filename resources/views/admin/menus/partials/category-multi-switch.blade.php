@props([
    'categoryKey',
    'checked' => false,
])

@php
    $isChecked = (bool) $checked;
@endphp

<div class="shrink-0">
    <span class="admin-label mb-1.5 block">複数設定可</span>
    <label class="admin-switch admin-switch--compact" data-category-multi-control>
        <input type="hidden" name="categories[{{ $categoryKey }}][allow_multiple_selection]" value="0">
        <input
            type="checkbox"
            name="categories[{{ $categoryKey }}][allow_multiple_selection]"
            value="1"
            class="admin-switch-input"
            data-category-allow-multiple="{{ $categoryKey }}"
            aria-label="複数設定可"
            @checked($isChecked)
        >
        <span class="admin-switch-track" aria-hidden="true">
            <span class="admin-switch-thumb"></span>
        </span>
        <span class="admin-switch-text" data-category-multi-text>{{ $isChecked ? '可' : '不可' }}</span>
    </label>
</div>
