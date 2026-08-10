{{-- Shown when the category has no menus --}}
<div
    class="{{ $hasMenus ? 'hidden' : '' }}"
    data-menu-empty
    @if($hasMenus) hidden @endif
>
    <x-admin.empty-state
        variant="menu"
        title="このカテゴリにメニューはありません。"
        description="メニューを追加してみましょう。"
    >
        <x-admin.create-button data-menu-add-btn>メニュー追加</x-admin.create-button>
    </x-admin.empty-state>
</div>
