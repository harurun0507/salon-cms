{{-- Shown below the menu list when the category already has menus --}}
<div
    class="menu-add-footer {{ $hasMenus ? '' : 'hidden' }}"
    data-menu-add-footer
    @if(! $hasMenus) hidden @endif
>
    <x-admin.empty-state variant="menu">
        <x-admin.create-button data-menu-add-btn>メニュー追加</x-admin.create-button>
    </x-admin.empty-state>
</div>
