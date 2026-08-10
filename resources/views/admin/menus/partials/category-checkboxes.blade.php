@props([
    'menuKey',
    'selectedIds' => [],
    'allCategories',
    'currentCategoryId' => null,
    'name' => null,
    'disabled' => false,
])

@php
    $selected = collect($selectedIds)->map(fn ($id) => (string) $id)->all();
    if ($currentCategoryId !== null && $currentCategoryId !== '' && ! in_array((string) $currentCategoryId, $selected, true)) {
        $selected[] = (string) $currentCategoryId;
    }

    $fieldName = $name ?? 'menus['.$menuKey.'][category_ids][]';
    $options = collect($allCategories)->map(function ($categoryOption) {
        return [
            'value' => (string) ($categoryOption['id'] ?? $categoryOption->id),
            'label' => (string) ($categoryOption['name'] ?? $categoryOption->name),
            'allow_multiple' => (bool) (
                is_array($categoryOption)
                    ? ($categoryOption['allow_multiple'] ?? false)
                    : ($categoryOption->allow_multiple_selection ?? false)
            ),
        ];
    })->all();
@endphp

<div class="mt-0" data-menu-category-checkboxes>
    <p class="mb-1.5 text-xs font-medium text-admin-muted">{{ $disabled ? 'カテゴリ（参照専用）' : 'カテゴリ（複数選択可）' }}</p>
    <x-admin.choice-toggles
        :name="$fieldName"
        :options="$options"
        :selected="$selected"
        :disabled="$disabled"
        aria-label="カテゴリ"
        variant="auto"
        input-data-attribute="data-menu-category-checkbox"
        data-menu-category-checkbox-list
        class="!mt-0"
    />
</div>
