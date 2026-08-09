@props([
    'menuKey',
    'selectedIds' => [],
    'allCategories' => [],
    'currentCategoryId' => null,
    'isPrimaryEditor' => true,
])

<tr class="menu-list-row menu-list-row--categories" data-menu-category-row="{{ $menuKey }}">
    <td class="!py-0 !pr-1" aria-hidden="true"></td>
    <td colspan="6" class="!pt-1 !pb-3 !pr-4 min-w-0">
        @include('admin.menus.partials.category-checkboxes', [
            'menuKey' => $menuKey,
            'selectedIds' => $selectedIds,
            'allCategories' => $allCategories,
            'currentCategoryId' => $currentCategoryId,
            'disabled' => ! $isPrimaryEditor,
        ])
    </td>
</tr>
