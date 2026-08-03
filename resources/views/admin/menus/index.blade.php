@extends('layouts.admin')

@section('heading', 'メニュー・料金管理')

@php
    $oldCategoriesForToolbar = old('categories', []);
    $hasRestoredNewCategories = false;
    foreach ($oldCategoriesForToolbar as $key => $_) {
        if (preg_match('/^new_\d+$/', (string) $key)) {
            $hasRestoredNewCategories = true;
            break;
        }
    }
    $hasAnyCategory = $categories->isNotEmpty() || $hasRestoredNewCategories;
@endphp

@section('save-bar')
    <div class="flex min-w-0 flex-wrap items-center gap-3">
        <button
            type="button"
            class="admin-btn shadow-md shrink-0 {{ $hasAnyCategory ? '' : 'hidden' }}"
            data-bulk-save-btn
            data-admin-confirm-trigger
            data-confirm-form="menus-bulk-form"
            data-confirm-title="一括保存の確認"
            data-confirm-message="変更内容を一括保存します。&#10;よろしいですか？"
            data-confirm-note="カテゴリ、メニュー、料金、表示順、公開状態など、現在入力されている内容が反映されます。"
            data-confirm-submit-label="一括保存する"
        >一括保存</button>
        <p class="text-sm text-admin-muted">
            公開サイトに表示するメニュー・料金・カテゴリを登録・編集します。
        </p>
    </div>

@endsection

@section('content')
    @php
        $menuIdToCategoryId = [];
        foreach ($categories as $cat) {
            foreach ($cat->menus as $menu) {
                $menuIdToCategoryId[$menu->id] = $cat->id;
            }
        }

        $oldCategories = old('categories', []);
        $restoredNewCategories = [];
        foreach ($oldCategories as $key => $data) {
            if (preg_match('/^new_\d+$/', (string) $key)) {
                $restoredNewCategories[(string) $key] = is_array($data) ? $data : [];
            }
        }

        $oldMenus = old('menus', []);
        $restoredNewMenusByCategory = [];
        $nextNewMenuIndex = 1;
        foreach ($oldMenus as $key => $data) {
            if (! preg_match('/^new_menu_(\d+)$/', (string) $key, $m)) {
                continue;
            }
            $nextNewMenuIndex = max($nextNewMenuIndex, ((int) $m[1]) + 1);
            $catRef = is_array($data) ? (string) ($data['category_id'] ?? '') : '';
            if ($catRef === '') {
                continue;
            }
            $restoredNewMenusByCategory[$catRef][(string) $key] = is_array($data) ? $data : [];
        }

        $errorCategoryId = null;
        if ($errors->any()) {
            foreach ($errors->keys() as $key) {
                if (preg_match('/^categories\.(new_\d+)(\.|$)/', $key, $m)) {
                    $errorCategoryId = $m[1];
                    break;
                }
                if (preg_match('/^categories\.(\d+)(\.|$)/', $key, $m)) {
                    $errorCategoryId = (int) $m[1];
                    break;
                }
                if (preg_match('/^menus\.(new_menu_\d+)(\.|$)/', $key, $m)) {
                    $menuKey = $m[1];
                    $catRef = old('menus.'.$menuKey.'.category_id');
                    if ($catRef !== null && $catRef !== '') {
                        $errorCategoryId = preg_match('/^new_\d+$/', (string) $catRef)
                            ? (string) $catRef
                            : (int) $catRef;
                        break;
                    }
                }
                if (preg_match('/^menus\.(\d+)(\.|$)/', $key, $m)) {
                    $menuId = (int) $m[1];
                    if (isset($menuIdToCategoryId[$menuId])) {
                        $errorCategoryId = $menuIdToCategoryId[$menuId];
                        break;
                    }
                }
            }
        }

        $validCategoryIds = $categories->pluck('id')->map(fn ($id) => (int) $id)->all();
        $sessionSelectedId = session('selected_category_id');
        $initialSelectedId = null;

        if ($sessionSelectedId !== null && in_array((int) $sessionSelectedId, $validCategoryIds, true)) {
            $initialSelectedId = (int) $sessionSelectedId;
        } elseif ($errorCategoryId !== null) {
            if (is_string($errorCategoryId) && preg_match('/^new_\d+$/', $errorCategoryId)) {
                $initialSelectedId = $errorCategoryId;
            } elseif (in_array((int) $errorCategoryId, $validCategoryIds, true)) {
                $initialSelectedId = (int) $errorCategoryId;
            }
        }

        if ($initialSelectedId === null) {
            if ($categories->isNotEmpty()) {
                $initialSelectedId = (int) $categories->first()->id;
            } elseif ($restoredNewCategories !== []) {
                $initialSelectedId = array_key_first($restoredNewCategories);
            }
        }

        $hasAnyCategory = $categories->isNotEmpty() || $restoredNewCategories !== [];
        $nextNewIndex = 1;
        foreach (array_keys($restoredNewCategories) as $newKey) {
            if (preg_match('/^new_(\d+)$/', $newKey, $m)) {
                $nextNewIndex = max($nextNewIndex, ((int) $m[1]) + 1);
            }
        }

        // old() の sort_order でカテゴリ一覧順を復元（既存・new_* を混在可）
        $displayCategories = [];
        foreach ($categories as $category) {
            $displayCategories[] = [
                'kind' => 'existing',
                'id' => (string) $category->id,
                'category' => $category,
                'sort' => (int) old('categories.'.$category->id.'.sort_order', $category->sort_order),
                'menu_count' => $category->menus->count() + count($restoredNewMenusByCategory[(string) $category->id] ?? []),
            ];
        }
        foreach ($restoredNewCategories as $newKey => $newData) {
            $displayCategories[] = [
                'kind' => 'new',
                'id' => (string) $newKey,
                'data' => $newData,
                'sort' => (int) ($newData['sort_order'] ?? 1),
                'menu_count' => count($restoredNewMenusByCategory[(string) $newKey] ?? []),
            ];
        }
        usort($displayCategories, fn ($a, $b) => $a['sort'] <=> $b['sort']);
        foreach ($displayCategories as $i => &$displayEntry) {
            $displayEntry['sort'] = $i + 1;
        }
        unset($displayEntry);

        // カテゴリ内メニューを old() sort_order で並べ直し（既存・new_menu_* 混在）
        $orderedMenusByCategory = [];
        foreach ($categories as $category) {
            $rows = [];
            foreach ($category->menus as $menu) {
                $rows[] = [
                    'kind' => 'existing',
                    'menu' => $menu,
                    'sort' => (int) old('menus.'.$menu->id.'.sort_order', $menu->sort_order),
                ];
            }
            foreach ($restoredNewMenusByCategory[(string) $category->id] ?? [] as $newMenuKey => $newMenuData) {
                $rows[] = [
                    'kind' => 'new',
                    'key' => $newMenuKey,
                    'data' => $newMenuData,
                    'sort' => (int) ($newMenuData['sort_order'] ?? 1),
                ];
            }
            usort($rows, fn ($a, $b) => $a['sort'] <=> $b['sort']);
            foreach ($rows as $i => &$menuRow) {
                $menuRow['sort'] = $i + 1;
            }
            unset($menuRow);
            $orderedMenusByCategory[(string) $category->id] = $rows;
        }
        foreach ($restoredNewCategories as $newKey => $newData) {
            $rows = [];
            foreach ($restoredNewMenusByCategory[(string) $newKey] ?? [] as $newMenuKey => $newMenuData) {
                $rows[] = [
                    'kind' => 'new',
                    'key' => $newMenuKey,
                    'data' => $newMenuData,
                    'sort' => (int) ($newMenuData['sort_order'] ?? 1),
                ];
            }
            usort($rows, fn ($a, $b) => $a['sort'] <=> $b['sort']);
            foreach ($rows as $i => &$menuRow) {
                $menuRow['sort'] = $i + 1;
            }
            unset($menuRow);
            $orderedMenusByCategory[(string) $newKey] = $rows;
        }
    @endphp

    
    <form method="POST" action="{{ route('admin.menus.bulk-update') }}" id="menus-bulk-form" data-menus-workspace data-initial-category-id="{{ $initialSelectedId }}" data-next-new-index="{{ $nextNewIndex }}" data-next-new-menu-index="{{ $nextNewMenuIndex }}">
        @csrf
        @method('PUT')
        <input type="hidden" name="selected_category_id" id="selected_category_id" value="{{ $initialSelectedId }}" data-selected-category-input>

        {{-- SP: horizontal category tabs --}}
        <div class="mb-4 -mx-1 overflow-x-auto md:hidden {{ $hasAnyCategory ? '' : 'hidden' }}" data-category-tabs>
            <div class="flex min-w-min gap-2 px-1 pb-1" data-category-tabs-inner>
                @foreach($displayCategories as $entry)
                    @if($entry['kind'] === 'existing')
                        @php $category = $entry['category']; @endphp
                        <button
                            type="button"
                            data-select-category="{{ $category->id }}"
                            class="menu-category-tab shrink-0 rounded-lg border border-admin-border bg-admin-card px-3 py-2 text-left transition hover:border-admin-accent/40 hover:bg-admin-hover"
                            aria-pressed="false"
                        >
                            <span class="block text-sm font-medium text-admin-text" data-category-label="{{ $category->id }}">{{ old('categories.'.$category->id.'.name', $category->name) }}</span>
                            <span class="mt-0.5 block text-xs text-admin-muted">メニュー <span data-category-count="{{ $category->id }}">{{ $entry['menu_count'] }}</span>件</span>
                        </button>
                    @else
                        <button
                            type="button"
                            data-select-category="{{ $entry['id'] }}"
                            class="menu-category-tab shrink-0 rounded-lg border border-admin-border bg-admin-card px-3 py-2 text-left transition hover:border-admin-accent/40 hover:bg-admin-hover"
                            aria-pressed="false"
                        >
                            <span class="block text-sm font-medium text-admin-text" data-category-label="{{ $entry['id'] }}">{{ $entry['data']['name'] ?? '新しいカテゴリ' }}</span>
                            <span class="mt-0.5 block text-xs text-admin-muted">メニュー <span data-category-count="{{ $entry['id'] }}">{{ $entry['menu_count'] }}</span>件</span>
                        </button>
                    @endif
                @endforeach
            </div>
            <div class="mt-2 px-1">
                <button type="button" class="admin-btn-secondary w-full text-sm" data-add-category>＋ カテゴリを追加</button>
            </div>
        </div>

        <div class="md:grid md:grid-cols-[15rem_minmax(0,1fr)] md:items-start md:gap-4 lg:grid-cols-[16rem_minmax(0,1fr)]">
            {{-- PC: left category list --}}
            <aside class="hidden md:block" data-category-aside>
                <div class="overflow-hidden rounded-xl border border-admin-border/40 bg-admin-card shadow-[0_2px_10px_rgba(0,0,0,0.04)]">
                    <p class="border-b border-admin-border/40 px-3 py-3 text-xs font-medium tracking-wide text-admin-muted">カテゴリ</p>
                    <ul class="divide-y divide-admin-border/80" data-category-list>
                        @foreach($displayCategories as $entry)
                            @if($entry['kind'] === 'existing')
                                @php $category = $entry['category']; @endphp
                                <li class="menu-category-row flex items-stretch" data-category-row="{{ $category->id }}">
                                    <span
                                        class="category-drag-handle"
                                        data-category-drag-handle
                                        draggable="true"
                                        role="button"
                                        tabindex="0"
                                        aria-label="カテゴリを並び替え"
                                        title="ドラッグして並び替え"
                                        aria-roledescription="ドラッグハンドル"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <circle cx="7" cy="5" r="1.25"/>
                                            <circle cx="13" cy="5" r="1.25"/>
                                            <circle cx="7" cy="10" r="1.25"/>
                                            <circle cx="13" cy="10" r="1.25"/>
                                            <circle cx="7" cy="15" r="1.25"/>
                                            <circle cx="13" cy="15" r="1.25"/>
                                        </svg>
                                    </span>
                                    <button
                                        type="button"
                                        data-select-category="{{ $category->id }}"
                                        class="menu-category-nav flex min-w-0 flex-1 items-center gap-2 py-3 pr-2 text-left transition"
                                        aria-pressed="false"
                                    >
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-sm font-medium text-admin-text" data-category-label="{{ $category->id }}">{{ old('categories.'.$category->id.'.name', $category->name) }}</span>
                                            <span class="mt-0.5 block text-xs text-admin-muted">メニュー <span data-category-count="{{ $category->id }}">{{ $entry['menu_count'] }}</span>件</span>
                                        </span>
                                    </button>
                                    <div class="flex shrink-0 flex-col items-end justify-center gap-0.5 py-2 pl-1.5 pr-3" data-category-sort-wrap>
                                        <div class="flex flex-col items-start gap-0.5">
                                            <label for="category-sort-{{ $category->id }}" class="text-left text-xs leading-none text-admin-muted whitespace-nowrap">表示順</label>
                                            <div class="flex items-center gap-2.5">
                                                <input
                                                    type="number"
                                                    id="category-sort-{{ $category->id }}"
                                                    name="categories[{{ $category->id }}][sort_order]"
                                                    value="{{ $entry['sort'] }}"
                                                    min="1"
                                                    step="1"
                                                    required
                                                    class="admin-input category-sort-order-input w-10 py-1 text-center"
                                                    data-category-sort-order
                                                    aria-label="表示順"
                                                >
                                                <button
                                                    type="button"
                                                    class="category-delete-x"
                                                    data-admin-delete-trigger
                                                    data-delete-form="delete-category-{{ $category->id }}"
                                                    data-delete-message="「{{ $category->name }}」カテゴリと配下のメニューを削除しますか？"
                                                    aria-label="カテゴリを削除"
                                                    title="カテゴリを削除"
                                                >
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                        </div>
                                        @error('categories.'.$category->id.'.sort_order')
                                            <p class="max-w-[7.5rem] text-right text-[10px] leading-tight text-admin-danger">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </li>
                            @else
                                <li class="menu-category-row flex items-stretch" data-category-row="{{ $entry['id'] }}" data-new-category="{{ $entry['id'] }}">
                                    <span
                                        class="category-drag-handle"
                                        data-category-drag-handle
                                        draggable="true"
                                        role="button"
                                        tabindex="0"
                                        aria-label="カテゴリを並び替え"
                                        title="ドラッグして並び替え"
                                        aria-roledescription="ドラッグハンドル"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <circle cx="7" cy="5" r="1.25"/>
                                            <circle cx="13" cy="5" r="1.25"/>
                                            <circle cx="7" cy="10" r="1.25"/>
                                            <circle cx="13" cy="10" r="1.25"/>
                                            <circle cx="7" cy="15" r="1.25"/>
                                            <circle cx="13" cy="15" r="1.25"/>
                                        </svg>
                                    </span>
                                    <button
                                        type="button"
                                        data-select-category="{{ $entry['id'] }}"
                                        class="menu-category-nav flex min-w-0 flex-1 items-center gap-2 py-3 pr-2 text-left transition"
                                        aria-pressed="false"
                                    >
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-sm font-medium text-admin-text" data-category-label="{{ $entry['id'] }}">{{ $entry['data']['name'] ?? '新しいカテゴリ' }}</span>
                                            <span class="mt-0.5 block text-xs text-admin-muted">メニュー <span data-category-count="{{ $entry['id'] }}">{{ $entry['menu_count'] }}</span>件</span>
                                        </span>
                                    </button>
                                    <div class="flex shrink-0 flex-col items-end justify-center gap-0.5 py-2 pl-1.5 pr-3" data-category-sort-wrap>
                                        <div class="flex flex-col items-start gap-0.5">
                                            <label for="category-sort-{{ $entry['id'] }}" class="text-left text-xs leading-none text-admin-muted whitespace-nowrap">表示順</label>
                                            <div class="flex items-center gap-2.5">
                                                <input
                                                    type="number"
                                                    id="category-sort-{{ $entry['id'] }}"
                                                    name="categories[{{ $entry['id'] }}][sort_order]"
                                                    value="{{ $entry['sort'] }}"
                                                    min="1"
                                                    step="1"
                                                    required
                                                    class="admin-input category-sort-order-input w-10 py-1 text-center"
                                                    data-category-sort-order
                                                    aria-label="表示順"
                                                >
                                                <button
                                                    type="button"
                                                    class="category-delete-x"
                                                    data-discard-category="{{ $entry['id'] }}"
                                                    aria-label="カテゴリを削除"
                                                    title="カテゴリを削除"
                                                >
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                        </div>
                                        @error('categories.'.$entry['id'].'.sort_order')
                                            <p class="max-w-[7.5rem] text-right text-[10px] leading-tight text-admin-danger">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                    <div class="border-t border-admin-border/40 p-2">
                        <button type="button" class="admin-btn-secondary w-full text-sm" data-add-category>＋ カテゴリを追加</button>
                    </div>
                </div>
            </aside>

            {{-- Right / main: category panels (all in DOM) --}}
            <div class="min-w-0 space-y-0" data-category-panels>
                <div
                    class="admin-card {{ $hasAnyCategory ? 'hidden' : '' }}"
                    data-menus-empty-workspace
                    @if($hasAnyCategory) hidden @endif
                >
                    <x-admin.empty-state
                        variant="menu"
                        title="カテゴリがありません。まずカテゴリを追加してください。"
                        description="左の「＋ カテゴリを追加」から作成できます。"
                    >
                        <button type="button" class="btn-admin-create" data-add-category>
                            <svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 3a1 1 0 0 1 1 1v5h5a1 1 0 1 1 0 2h-5v5a1 1 0 1 1-2 0v-5H4a1 1 0 1 1 0-2h5V4a1 1 0 0 1 1-1Z"/></svg>
                            <span>カテゴリを追加</span>
                        </button>
                    </x-admin.empty-state>
                </div>

                @foreach($categories as $category)
                    @php
                        $menuRows = $orderedMenusByCategory[(string) $category->id] ?? [];
                        $hasMenus = $menuRows !== [];
                    @endphp
                    <div
                        class="admin-card overflow-x-auto {{ (string) $category->id === (string) $initialSelectedId ? '' : 'hidden' }}"
                        data-category-panel="{{ $category->id }}"
                        @if((string) $category->id !== (string) $initialSelectedId) hidden @endif
                    >
                        <div class="mb-4 flex flex-wrap items-end justify-between gap-4 border-b border-admin-border/40 pb-4">
                            <div class="min-w-0 flex-1">
                                <label class="admin-label" for="category-name-{{ $category->id }}">カテゴリ名</label>
                                <input
                                    type="text"
                                    id="category-name-{{ $category->id }}"
                                    name="categories[{{ $category->id }}][name]"
                                    value="{{ old('categories.'.$category->id.'.name', $category->name) }}"
                                    required
                                    class="admin-input font-medium"
                                    data-category-name-input="{{ $category->id }}"
                                >
                            </div>
                            <div class="md:hidden">
                                <button
                                    type="button"
                                    class="category-delete-x !opacity-100"
                                    data-admin-delete-trigger
                                    data-delete-form="delete-category-{{ $category->id }}"
                                    data-delete-message="「{{ $category->name }}」カテゴリと配下のメニューを削除しますか？"
                                    aria-label="カテゴリを削除"
                                    title="カテゴリを削除"
                                >
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3 flex items-center justify-between gap-3" data-menu-list-header>
                            <h3 class="text-sm font-medium text-admin-text">メニュー</h3>
                            <x-admin.create-button class="{{ $hasMenus ? '' : 'hidden' }}" data-menu-add-btn>メニュー追加</x-admin.create-button>
                        </div>

                        <div class="{{ $hasMenus ? 'hidden' : '' }}" data-menu-empty @if($hasMenus) hidden @endif>
                            <x-admin.empty-state
                                variant="menu"
                                title="このカテゴリにメニューはありません。"
                                description="メニューを追加してみましょう。"
                            >
                                <x-admin.create-button data-menu-add-btn>メニュー追加</x-admin.create-button>
                            </x-admin.empty-state>
                        </div>

                        <table class="admin-table menu-list-table table-fixed w-full {{ $hasMenus ? '' : 'hidden' }}" data-menu-table @if(! $hasMenus) hidden @endif>
                            <colgroup>
                                <col class="menu-col-handle">
                                <col class="menu-col-name">
                                <col class="menu-col-desc">
                                <col class="menu-col-price">
                                <col class="menu-col-pub">
                                <col class="menu-col-sort">
                                <col class="menu-col-actions">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th class="!pb-3 !pr-1" aria-label="並び替え"></th>
                                    <th class="!pb-3 !pr-3">メニュー名</th>
                                    <th class="!pb-3 !pr-3">説明</th>
                                    <th class="!pb-3 !pr-3">料金表示</th>
                                    <th class="!pb-3 !pr-3">公開</th>
                                    <th class="!pb-3 !pl-1 !pr-3 text-center whitespace-nowrap">表示順</th>
                                    <th class="!pb-3 !pl-0 !pr-4 text-center whitespace-nowrap">操作</th>
                                </tr>
                            </thead>
                            <tbody data-menu-tbody>
                                @foreach($menuRows as $row)
                                    @if($row['kind'] === 'existing')
                                        @php $menu = $row['menu']; @endphp
                                        <tr class="align-top menu-list-row" data-menu-row="{{ $menu->id }}">
                                            <td class="!py-3 !pr-1">
                                                <span
                                                    class="menu-drag-handle"
                                                    data-menu-drag-handle
                                                    draggable="true"
                                                    role="button"
                                                    tabindex="0"
                                                    aria-label="メニューを並び替え"
                                                    title="ドラッグして並び替え"
                                                    aria-roledescription="ドラッグハンドル"
                                                >
                                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                        <circle cx="7" cy="5" r="1.25"/>
                                                        <circle cx="13" cy="5" r="1.25"/>
                                                        <circle cx="7" cy="10" r="1.25"/>
                                                        <circle cx="13" cy="10" r="1.25"/>
                                                        <circle cx="7" cy="15" r="1.25"/>
                                                        <circle cx="13" cy="15" r="1.25"/>
                                                    </svg>
                                                </span>
                                            </td>
                                            <td class="!py-3 !pr-3 min-w-0">
                                                <textarea
                                                    name="menus[{{ $menu->id }}][name]"
                                                    rows="3"
                                                    required
                                                    class="admin-input menu-name-textarea w-full min-w-0 min-h-[76px] resize-none overflow-y-auto py-1.5"
                                                >{{ old('menus.'.$menu->id.'.name', $menu->name) }}</textarea>
                                            </td>
                                            <td class="!py-3 !pr-3 min-w-0">
                                                <textarea
                                                    name="menus[{{ $menu->id }}][description]"
                                                    id="menu-description-{{ $menu->id }}"
                                                    rows="3"
                                                    class="admin-input menu-description-textarea w-full min-w-0 min-h-[76px] resize-none overflow-y-auto py-1.5"
                                                    placeholder="説明（任意）"
                                                >{{ old('menus.'.$menu->id.'.description', $menu->description) }}</textarea>
                                            </td>
                                            <td class="!py-3 !pr-3 min-w-0">
                                                <input type="text" name="menus[{{ $menu->id }}][price]" value="{{ old('menus.'.$menu->id.'.price', $menu->price) }}" maxlength="100" placeholder="例: ¥5,500" class="admin-input min-w-0 py-1.5" title="公開サイトへそのまま表示されます。">
                                            </td>
                                            <td class="!py-3 !pr-3">
                                                @php
                                                    $menuPublished = old('menus.'.$menu->id.'.is_published', $menu->is_published ? '1' : '0') == '1';
                                                @endphp
                                                <label class="admin-switch admin-switch--compact" data-published-control>
                                                    <input type="hidden" name="menus[{{ $menu->id }}][is_published]" value="0">
                                                    <input
                                                        type="checkbox"
                                                        name="menus[{{ $menu->id }}][is_published]"
                                                        value="1"
                                                        class="admin-switch-input"
                                                        data-published-checkbox
                                                        @checked($menuPublished)
                                                        aria-label="公開状態"
                                                    >
                                                    <span class="admin-switch-track" aria-hidden="true">
                                                        <span class="admin-switch-thumb"></span>
                                                    </span>
                                                    <span class="admin-switch-text" data-published-text>{{ $menuPublished ? '公開' : '非公開' }}</span>
                                                </label>
                                            </td>
                                            <td class="!py-3 !pl-1 !pr-2 text-center">
                                                <input
                                                    type="number"
                                                    name="menus[{{ $menu->id }}][sort_order]"
                                                    value="{{ $row['sort'] }}"
                                                    min="1"
                                                    step="1"
                                                    required
                                                    class="admin-input menu-sort-order-input w-10 py-1.5 text-center"
                                                    data-menu-sort-order
                                                    aria-label="表示順"
                                                >
                                                @error('menus.'.$menu->id.'.sort_order')
                                                    <p class="mt-1 text-xs text-admin-danger">{{ $message }}</p>
                                                @enderror
                                            </td>
                                            <td class="!py-3 !pl-0 !pr-4 text-left">
                                                <button
                                                    type="button"
                                                    class="category-delete-x"
                                                    data-admin-delete-trigger
                                                    data-delete-form="delete-menu-{{ $menu->id }}"
                                                    data-delete-message="「{{ $menu->name }}」を削除しますか？"
                                                    aria-label="メニューを削除"
                                                    title="メニューを削除"
                                                >
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </td>
                                        </tr>
                                    @else
                                        @php
                                            $newMenuKey = $row['key'];
                                            $newMenuData = $row['data'];
                                        @endphp
                                        <tr class="align-top menu-list-row" data-menu-row="{{ $newMenuKey }}" data-new-menu="{{ $newMenuKey }}">
                                            <td class="!py-3 !pr-1">
                                                <span
                                                    class="menu-drag-handle"
                                                    data-menu-drag-handle
                                                    draggable="true"
                                                    role="button"
                                                    tabindex="0"
                                                    aria-label="メニューを並び替え"
                                                    title="ドラッグして並び替え"
                                                    aria-roledescription="ドラッグハンドル"
                                                >
                                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                        <circle cx="7" cy="5" r="1.25"/>
                                                        <circle cx="13" cy="5" r="1.25"/>
                                                        <circle cx="7" cy="10" r="1.25"/>
                                                        <circle cx="13" cy="10" r="1.25"/>
                                                        <circle cx="7" cy="15" r="1.25"/>
                                                        <circle cx="13" cy="15" r="1.25"/>
                                                    </svg>
                                                </span>
                                            </td>
                                            <td class="!py-3 !pr-3 min-w-0">
                                                <input type="hidden" name="menus[{{ $newMenuKey }}][category_id]" value="{{ $category->id }}">
                                                <textarea
                                                    name="menus[{{ $newMenuKey }}][name]"
                                                    rows="3"
                                                    required
                                                    class="admin-input menu-name-textarea w-full min-w-0 min-h-[76px] resize-none overflow-y-auto py-1.5"
                                                    data-menu-name-input
                                                >{{ $newMenuData['name'] ?? '' }}</textarea>
                                                @error('menus.'.$newMenuKey.'.name')
                                                    <p class="mt-1 text-xs text-admin-danger">{{ $message }}</p>
                                                @enderror
                                            </td>
                                            <td class="!py-3 !pr-3 min-w-0">
                                                <textarea
                                                    name="menus[{{ $newMenuKey }}][description]"
                                                    rows="3"
                                                    class="admin-input menu-description-textarea w-full min-w-0 min-h-[76px] resize-none overflow-y-auto py-1.5"
                                                    placeholder="説明（任意）"
                                                >{{ $newMenuData['description'] ?? '' }}</textarea>
                                            </td>
                                            <td class="!py-3 !pr-3 min-w-0">
                                                <input type="text" name="menus[{{ $newMenuKey }}][price]" value="{{ $newMenuData['price'] ?? '' }}" maxlength="100" placeholder="例: ¥5,500" class="admin-input min-w-0 py-1.5" title="公開サイトへそのまま表示されます。">
                                            </td>
                                            <td class="!py-3 !pr-3">
                                                @php
                                                    $newMenuPublished = ($newMenuData['is_published'] ?? '1') == '1';
                                                @endphp
                                                <label class="admin-switch admin-switch--compact" data-published-control>
                                                    <input type="hidden" name="menus[{{ $newMenuKey }}][is_published]" value="0">
                                                    <input
                                                        type="checkbox"
                                                        name="menus[{{ $newMenuKey }}][is_published]"
                                                        value="1"
                                                        class="admin-switch-input"
                                                        data-published-checkbox
                                                        @checked($newMenuPublished)
                                                        aria-label="公開状態"
                                                    >
                                                    <span class="admin-switch-track" aria-hidden="true">
                                                        <span class="admin-switch-thumb"></span>
                                                    </span>
                                                    <span class="admin-switch-text" data-published-text>{{ $newMenuPublished ? '公開' : '非公開' }}</span>
                                                </label>
                                            </td>
                                            <td class="!py-3 !pl-1 !pr-2 text-center">
                                                <input
                                                    type="number"
                                                    name="menus[{{ $newMenuKey }}][sort_order]"
                                                    value="{{ $row['sort'] }}"
                                                    min="1"
                                                    step="1"
                                                    required
                                                    class="admin-input menu-sort-order-input w-10 py-1.5 text-center"
                                                    data-menu-sort-order
                                                    aria-label="表示順"
                                                >
                                                @error('menus.'.$newMenuKey.'.sort_order')
                                                    <p class="mt-1 text-xs text-admin-danger">{{ $message }}</p>
                                                @enderror
                                            </td>
                                            <td class="!py-3 !pl-0 !pr-4 text-left">
                                                <button
                                                    type="button"
                                                    class="category-delete-x"
                                                    data-discard-menu="{{ $newMenuKey }}"
                                                    aria-label="メニュー追加を取り消す"
                                                    title="メニュー追加を取り消す"
                                                >
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach

                @foreach($restoredNewCategories as $newKey => $newData)
                    @php
                        $menuRows = $orderedMenusByCategory[(string) $newKey] ?? [];
                        $hasMenus = $menuRows !== [];
                    @endphp
                    <div
                        class="admin-card overflow-x-auto {{ (string) $newKey === (string) $initialSelectedId ? '' : 'hidden' }}"
                        data-category-panel="{{ $newKey }}"
                        data-new-panel="{{ $newKey }}"
                        @if((string) $newKey !== (string) $initialSelectedId) hidden @endif
                    >
                        <div class="mb-4 flex flex-wrap items-end justify-between gap-4 border-b border-admin-border/40 pb-4">
                            <div class="min-w-0 flex-1">
                                <label class="admin-label" for="category-name-{{ $newKey }}">カテゴリ名</label>
                                <input
                                    type="text"
                                    id="category-name-{{ $newKey }}"
                                    name="categories[{{ $newKey }}][name]"
                                    value="{{ $newData['name'] ?? '' }}"
                                    required
                                    class="admin-input font-medium"
                                    data-category-name-input="{{ $newKey }}"
                                >
                                @error('categories.'.$newKey.'.name')
                                    <p class="mt-1 text-xs text-admin-danger">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="md:hidden">
                                <button
                                    type="button"
                                    class="category-delete-x !opacity-100"
                                    data-discard-category="{{ $newKey }}"
                                    aria-label="カテゴリを削除"
                                    title="カテゴリを削除"
                                >
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        </div>
                        <div class="mb-3 flex items-center justify-between gap-3" data-menu-list-header>
                            <h3 class="text-sm font-medium text-admin-text">メニュー</h3>
                            <x-admin.create-button class="{{ $hasMenus ? '' : 'hidden' }}" data-menu-add-btn>メニュー追加</x-admin.create-button>
                        </div>
                        <div class="{{ $hasMenus ? 'hidden' : '' }}" data-menu-empty @if($hasMenus) hidden @endif>
                            <x-admin.empty-state
                                variant="menu"
                                title="このカテゴリにメニューはありません。"
                                description="メニューを追加してみましょう。"
                            >
                                <x-admin.create-button data-menu-add-btn>メニュー追加</x-admin.create-button>
                            </x-admin.empty-state>
                        </div>
                        <table class="admin-table menu-list-table table-fixed w-full {{ $hasMenus ? '' : 'hidden' }}" data-menu-table @if(! $hasMenus) hidden @endif>
                            <colgroup>
                                <col class="menu-col-handle">
                                <col class="menu-col-name">
                                <col class="menu-col-desc">
                                <col class="menu-col-price">
                                <col class="menu-col-pub">
                                <col class="menu-col-sort">
                                <col class="menu-col-actions">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th class="!pb-3 !pr-1" aria-label="並び替え"></th>
                                    <th class="!pb-3 !pr-3">メニュー名</th>
                                    <th class="!pb-3 !pr-3">説明</th>
                                    <th class="!pb-3 !pr-3">料金表示</th>
                                    <th class="!pb-3 !pr-3">公開</th>
                                    <th class="!pb-3 !pl-1 !pr-3 text-center whitespace-nowrap">表示順</th>
                                    <th class="!pb-3 !pl-0 !pr-4 text-center whitespace-nowrap">操作</th>
                                </tr>
                            </thead>
                            <tbody data-menu-tbody>
                                @foreach($menuRows as $row)
                                    @php
                                        $newMenuKey = $row['key'];
                                        $newMenuData = $row['data'];
                                    @endphp
                                    <tr class="align-top menu-list-row" data-menu-row="{{ $newMenuKey }}" data-new-menu="{{ $newMenuKey }}">
                                        <td class="!py-3 !pr-1">
                                            <span
                                                class="menu-drag-handle"
                                                data-menu-drag-handle
                                                draggable="true"
                                                role="button"
                                                tabindex="0"
                                                aria-label="メニューを並び替え"
                                                title="ドラッグして並び替え"
                                                aria-roledescription="ドラッグハンドル"
                                            >
                                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <circle cx="7" cy="5" r="1.25"/>
                                                    <circle cx="13" cy="5" r="1.25"/>
                                                    <circle cx="7" cy="10" r="1.25"/>
                                                    <circle cx="13" cy="10" r="1.25"/>
                                                    <circle cx="7" cy="15" r="1.25"/>
                                                    <circle cx="13" cy="15" r="1.25"/>
                                                </svg>
                                            </span>
                                        </td>
                                        <td class="!py-3 !pr-3 min-w-0">
                                            <input type="hidden" name="menus[{{ $newMenuKey }}][category_id]" value="{{ $newKey }}">
                                            <textarea
                                                name="menus[{{ $newMenuKey }}][name]"
                                                rows="3"
                                                required
                                                class="admin-input menu-name-textarea w-full min-w-0 min-h-[76px] resize-none overflow-y-auto py-1.5"
                                                data-menu-name-input
                                            >{{ $newMenuData['name'] ?? '' }}</textarea>
                                            @error('menus.'.$newMenuKey.'.name')
                                                <p class="mt-1 text-xs text-admin-danger">{{ $message }}</p>
                                            @enderror
                                        </td>
                                        <td class="!py-3 !pr-3 min-w-0">
                                            <textarea
                                                name="menus[{{ $newMenuKey }}][description]"
                                                rows="3"
                                                class="admin-input menu-description-textarea w-full min-w-0 min-h-[76px] resize-none overflow-y-auto py-1.5"
                                                placeholder="説明（任意）"
                                            >{{ $newMenuData['description'] ?? '' }}</textarea>
                                        </td>
                                        <td class="!py-3 !pr-3 min-w-0">
                                            <input type="text" name="menus[{{ $newMenuKey }}][price]" value="{{ $newMenuData['price'] ?? '' }}" maxlength="100" placeholder="例: ¥5,500" class="admin-input min-w-0 py-1.5" title="公開サイトへそのまま表示されます。">
                                        </td>
                                        <td class="!py-3 !pr-3">
                                            @php
                                                $newMenuPublished = ($newMenuData['is_published'] ?? '1') == '1';
                                            @endphp
                                            <label class="admin-switch admin-switch--compact" data-published-control>
                                                <input type="hidden" name="menus[{{ $newMenuKey }}][is_published]" value="0">
                                                <input
                                                    type="checkbox"
                                                    name="menus[{{ $newMenuKey }}][is_published]"
                                                    value="1"
                                                    class="admin-switch-input"
                                                    data-published-checkbox
                                                    @checked($newMenuPublished)
                                                    aria-label="公開状態"
                                                >
                                                <span class="admin-switch-track" aria-hidden="true">
                                                    <span class="admin-switch-thumb"></span>
                                                </span>
                                                <span class="admin-switch-text" data-published-text>{{ $newMenuPublished ? '公開' : '非公開' }}</span>
                                            </label>
                                        </td>
                                        <td class="!py-3 !pl-1 !pr-2 text-center">
                                            <input
                                                type="number"
                                                name="menus[{{ $newMenuKey }}][sort_order]"
                                                value="{{ $row['sort'] }}"
                                                min="1"
                                                step="1"
                                                required
                                                class="admin-input menu-sort-order-input w-10 py-1.5 text-center"
                                                data-menu-sort-order
                                                aria-label="表示順"
                                            >
                                            @error('menus.'.$newMenuKey.'.sort_order')
                                                <p class="mt-1 text-xs text-admin-danger">{{ $message }}</p>
                                            @enderror
                                        </td>
                                        <td class="!py-3 !pl-0 !pr-4 text-left">
                                            <button
                                                type="button"
                                                class="category-delete-x"
                                                data-discard-menu="{{ $newMenuKey }}"
                                                aria-label="メニュー追加を取り消す"
                                                title="メニュー追加を取り消す"
                                            >
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </div>
        </div>
    </form>

    @foreach($categories as $category)
        <form id="delete-category-{{ $category->id }}" method="POST" action="{{ route('admin.menus.categories.destroy', $category) }}" class="hidden">
            @csrf
            @method('DELETE')
        </form>
        @foreach($category->menus as $menu)
            <form id="delete-menu-{{ $menu->id }}" method="POST" action="{{ route('admin.menus.destroy', $menu) }}" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        @endforeach
    @endforeach

    {{-- 未保存カテゴリ破棄の軽い確認 --}}
    <div id="category-discard-modal" class="menu-modal hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" role="dialog" aria-modal="true" aria-labelledby="category-discard-title">
        <div class="admin-modal-panel w-full max-w-sm rounded-xl border border-admin-border/50 bg-admin-card p-6">
            <h2 id="category-discard-title" class="text-lg font-semibold text-admin-text">入力内容を破棄しますか？</h2>
            <p class="mt-2 text-sm text-admin-muted">このカテゴリはまだ保存されていません。入力した内容は失われます。</p>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" class="admin-btn-secondary" data-discard-cancel>キャンセル</button>
                <button type="button" class="admin-btn-danger" data-discard-confirm>破棄する</button>
            </div>
        </div>
    </div>

    <template id="new-category-row-template">
        <li class="menu-category-row flex items-stretch" data-category-row="__ID__" data-new-category="__ID__">
            <span
                class="category-drag-handle"
                data-category-drag-handle
                draggable="true"
                role="button"
                tabindex="0"
                aria-label="カテゴリを並び替え"
                title="ドラッグして並び替え"
                aria-roledescription="ドラッグハンドル"
            >
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <circle cx="7" cy="5" r="1.25"/>
                    <circle cx="13" cy="5" r="1.25"/>
                    <circle cx="7" cy="10" r="1.25"/>
                    <circle cx="13" cy="10" r="1.25"/>
                    <circle cx="7" cy="15" r="1.25"/>
                    <circle cx="13" cy="15" r="1.25"/>
                </svg>
            </span>
            <button
                type="button"
                data-select-category="__ID__"
                class="menu-category-nav flex min-w-0 flex-1 items-center gap-2 py-3 pr-2 text-left transition"
                aria-pressed="false"
            >
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-medium text-admin-text" data-category-label="__ID__">新しいカテゴリ</span>
                    <span class="mt-0.5 block text-xs text-admin-muted">メニュー <span data-category-count="__ID__">0</span>件</span>
                </span>
            </button>
            <div class="flex shrink-0 flex-col items-end justify-center gap-0.5 py-2 pl-1.5 pr-3" data-category-sort-wrap>
                <div class="flex flex-col items-start gap-0.5">
                    <label for="category-sort-__ID__" class="text-left text-xs leading-none text-admin-muted whitespace-nowrap">表示順</label>
                    <div class="flex items-center gap-2.5">
                        <input
                            type="number"
                            id="category-sort-__ID__"
                            name="categories[__ID__][sort_order]"
                            value="1"
                            min="1"
                            step="1"
                            required
                            class="admin-input category-sort-order-input w-10 py-1 text-center"
                            data-category-sort-order
                            aria-label="表示順"
                        >
                        <button
                            type="button"
                            class="category-delete-x"
                            data-discard-category="__ID__"
                            aria-label="カテゴリを削除"
                            title="カテゴリを削除"
                        >
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                </div>
            </div>
        </li>
    </template>

    <template id="new-category-tab-template">
        <button
            type="button"
            data-select-category="__ID__"
            class="menu-category-tab shrink-0 rounded-lg border border-admin-border bg-admin-card px-3 py-2 text-left transition hover:border-admin-accent/40 hover:bg-admin-hover"
            aria-pressed="false"
        >
            <span class="block text-sm font-medium text-admin-text" data-category-label="__ID__">新しいカテゴリ</span>
            <span class="mt-0.5 block text-xs text-admin-muted">メニュー <span data-category-count="__ID__">0</span>件</span>
        </button>
    </template>

    <template id="new-category-panel-template">
        <div class="admin-card overflow-x-auto hidden" data-category-panel="__ID__" data-new-panel="__ID__" hidden>
            <div class="mb-4 flex flex-wrap items-end justify-between gap-4 border-b border-admin-border/40 pb-4">
                <div class="min-w-0 flex-1">
                    <label class="admin-label" for="category-name-__ID__">カテゴリ名</label>
                    <input
                        type="text"
                        id="category-name-__ID__"
                        name="categories[__ID__][name]"
                        value=""
                        required
                        class="admin-input font-medium"
                        data-category-name-input="__ID__"
                        placeholder="カテゴリ名を入力"
                    >
                </div>
                <div class="md:hidden">
                    <button
                        type="button"
                        class="category-delete-x !opacity-100"
                        data-discard-category="__ID__"
                        aria-label="カテゴリを削除"
                        title="カテゴリを削除"
                    >
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            </div>
            <div class="mb-3 flex items-center justify-between gap-3" data-menu-list-header>
                <h3 class="text-sm font-medium text-admin-text">メニュー</h3>
                <button type="button" class="btn-admin-create hidden" data-menu-add-btn>
                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 3a1 1 0 0 1 1 1v5h5a1 1 0 1 1 0 2h-5v5a1 1 0 1 1-2 0v-5H4a1 1 0 1 1 0-2h5V4a1 1 0 0 1 1-1Z"/></svg>
                    <span>メニュー追加</span>
                </button>
            </div>
            <div data-menu-empty>
                <div class="admin-empty-state">
                    <div class="admin-empty-state-icon" aria-hidden="true">
                        <svg class="h-14 w-14" viewBox="0 0 80 80" fill="none" stroke="#B8B09F" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 58c-3-10-2-22 6-30 8-8 20-10 28-6" stroke-width="1.25" opacity="0.55"/>
                            <path d="M28 36c4-6 10-10 16-10" stroke-width="1.2" opacity="0.5"/>
                            <path d="M24 46c6-4 14-6 22-4" stroke-width="1.2" opacity="0.5"/>
                            <circle cx="28" cy="28" r="6" stroke-width="1.4"/>
                            <circle cx="28" cy="52" r="6" stroke-width="1.4"/>
                            <path d="M33 32.5 56 52" stroke-width="1.4"/>
                            <path d="M33 47.5 56 28" stroke-width="1.4"/>
                            <path d="M40 40h.01" stroke-width="2"/>
                            <path d="M58 24c4 2 8 7 8 13 0 4-2 8-5 10" stroke-width="1.25" opacity="0.7"/>
                            <path d="M58 24c-1 5 1 10 5 13" stroke-width="1.2" opacity="0.55"/>
                        </svg>
                    </div>
                    <p class="admin-empty-state-title">このカテゴリにメニューはありません。</p>
                    <p class="admin-empty-state-desc">メニューを追加してみましょう。</p>
                    <div class="admin-empty-state-actions">
                        <button type="button" class="btn-admin-create" data-menu-add-btn>
                            <svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 3a1 1 0 0 1 1 1v5h5a1 1 0 1 1 0 2h-5v5a1 1 0 1 1-2 0v-5H4a1 1 0 1 1 0-2h5V4a1 1 0 0 1 1-1Z"/></svg>
                            <span>メニュー追加</span>
                        </button>
                    </div>
                </div>
            </div>
            <table class="admin-table menu-list-table table-fixed w-full hidden" data-menu-table hidden>
                <colgroup>
                    <col class="menu-col-handle">
                    <col class="menu-col-name">
                    <col class="menu-col-desc">
                    <col class="menu-col-price">
                    <col class="menu-col-pub">
                    <col class="menu-col-sort">
                    <col class="menu-col-actions">
                </colgroup>
                <thead>
                    <tr>
                        <th class="!pb-3 !pr-1" aria-label="並び替え"></th>
                        <th class="!pb-3 !pr-3">メニュー名</th>
                        <th class="!pb-3 !pr-3">説明</th>
                        <th class="!pb-3 !pr-3">料金表示</th>
                        <th class="!pb-3 !pr-3">公開</th>
                        <th class="!pb-3 !pl-1 !pr-3 text-center whitespace-nowrap">表示順</th>
                        <th class="!pb-3 !pl-0 !pr-4 text-center whitespace-nowrap">操作</th>
                    </tr>
                </thead>
                <tbody data-menu-tbody></tbody>
            </table>
        </div>
    </template>

    <template id="new-menu-row-template">
        <tr class="align-top menu-list-row" data-menu-row="__MENU_ID__" data-new-menu="__MENU_ID__">
            <td class="!py-3 !pr-1">
                <span
                    class="menu-drag-handle"
                    data-menu-drag-handle
                    draggable="true"
                    role="button"
                    tabindex="0"
                    aria-label="メニューを並び替え"
                    title="ドラッグして並び替え"
                    aria-roledescription="ドラッグハンドル"
                >
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <circle cx="7" cy="5" r="1.25"/>
                        <circle cx="13" cy="5" r="1.25"/>
                        <circle cx="7" cy="10" r="1.25"/>
                        <circle cx="13" cy="10" r="1.25"/>
                        <circle cx="7" cy="15" r="1.25"/>
                        <circle cx="13" cy="15" r="1.25"/>
                    </svg>
                </span>
            </td>
            <td class="!py-3 !pr-3 min-w-0">
                <input type="hidden" name="menus[__MENU_ID__][category_id]" value="__CATEGORY_ID__">
                <textarea
                    name="menus[__MENU_ID__][name]"
                    rows="3"
                    required
                    class="admin-input menu-name-textarea w-full min-w-0 min-h-[76px] resize-none overflow-y-auto py-1.5"
                    data-menu-name-input
                ></textarea>
            </td>
            <td class="!py-3 !pr-3 min-w-0">
                <textarea
                    name="menus[__MENU_ID__][description]"
                    rows="3"
                    class="admin-input menu-description-textarea w-full min-w-0 min-h-[76px] resize-none overflow-y-auto py-1.5"
                    placeholder="説明（任意）"
                ></textarea>
            </td>
            <td class="!py-3 !pr-3 min-w-0">
                <input type="text" name="menus[__MENU_ID__][price]" value="" maxlength="100" placeholder="例: ¥5,500" class="admin-input min-w-0 py-1.5" title="公開サイトへそのまま表示されます。">
            </td>
            <td class="!py-3 !pr-3">
                <label class="admin-switch admin-switch--compact" data-published-control>
                    <input type="hidden" name="menus[__MENU_ID__][is_published]" value="0">
                    <input
                        type="checkbox"
                        name="menus[__MENU_ID__][is_published]"
                        value="1"
                        class="admin-switch-input"
                        data-published-checkbox
                        checked
                        aria-label="公開状態"
                    >
                    <span class="admin-switch-track" aria-hidden="true">
                        <span class="admin-switch-thumb"></span>
                    </span>
                    <span class="admin-switch-text" data-published-text>公開</span>
                </label>
            </td>
            <td class="!py-3 !pl-1 !pr-2 text-center">
                <input
                    type="number"
                    name="menus[__MENU_ID__][sort_order]"
                    value="__SORT__"
                    min="1"
                    step="1"
                    required
                    class="admin-input menu-sort-order-input w-10 py-1.5 text-center"
                    data-menu-sort-order
                    aria-label="表示順"
                >
            </td>
            <td class="!py-3 !pl-0 !pr-4 text-left">
                <button
                    type="button"
                    class="category-delete-x"
                    data-discard-menu="__MENU_ID__"
                    aria-label="メニュー追加を取り消す"
                    title="メニュー追加を取り消す"
                >
                    <span aria-hidden="true">&times;</span>
                </button>
            </td>
        </tr>
    </template>

    <style>
        .menu-list-table {
            width: 100%;
            table-layout: fixed;
        }

        .menu-list-table .menu-col-handle { width: 28px; }
        .menu-list-table .menu-col-name { width: 20%; }
        .menu-list-table .menu-col-desc { width: 38%; }
        .menu-list-table .menu-col-price { width: 13%; }
        .menu-list-table .menu-col-pub { width: 11%; min-width: 7rem; }
        /* sort: 2.5rem input + pl-1 + pr-2; wide enough for nowrap「表示順」 */
        .menu-list-table .menu-col-sort { width: 5.5rem; }
        /* actions: 2rem × + pr-4; room for nowrap「操作」(2 chars) without vertical wrap */
        .menu-list-table .menu-col-actions { width: 4.25rem; }

        .menu-list-table th:nth-last-child(2),
        .menu-list-table th:last-child {
            white-space: nowrap;
            writing-mode: horizontal-tb;
        }

        .menu-sort-order-input,
        .category-sort-order-input {
            width: 2.5rem; /* w-10: 2-digit fit */
            min-width: 2.5rem;
            max-width: 2.5rem;
            padding-left: 0.25rem;
            padding-right: 0.25rem;
            box-sizing: border-box;
        }

        .menu-list-table th,
        .menu-list-table td {
            vertical-align: top;
            box-sizing: border-box;
        }

        .menu-list-table td.min-w-0 {
            overflow: hidden;
        }

        .menu-list-table .menu-col-actions,
        .menu-list-table th:last-child,
        .menu-list-table td:last-child {
            overflow: visible;
        }

        .menu-name-textarea,
        .menu-description-textarea {
            display: block;
            width: 100%;
            max-width: 100%;
            height: 76px;
            min-height: 76px;
            max-height: 76px;
            overflow-x: hidden;
            overflow-y: auto;
            resize: none;
            line-height: 1.4;
            box-sizing: border-box;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .menu-list-row {
            position: relative;
            transition: background-color 0.15s ease;
        }

        .menu-list-row:hover {
            background-color: #EEF1E8;
        }

        .menu-list-row.is-selected {
            background-color: #E5EADD;
        }

        .menu-list-row.is-dragging {
            background-color: #E8EDE3;
            box-shadow: 0 2px 8px rgba(61, 56, 51, 0.08);
            opacity: 0.92;
            z-index: 1;
        }

        .menu-list-row.drag-insert-before::before,
        .menu-list-row.drag-insert-after::after {
            content: '';
            position: absolute;
            left: 0.5rem;
            right: 0.5rem;
            height: 2px;
            border-radius: 1px;
            background-color: #697A55;
            pointer-events: none;
            z-index: 2;
        }

        .menu-list-row.drag-insert-before::before {
            top: 0;
        }

        .menu-list-row.drag-insert-after::after {
            bottom: 0;
        }

        .menu-drag-handle {
            display: inline-flex;
            flex-shrink: 0;
            align-items: center;
            justify-content: center;
            width: 1.75rem;
            height: 2rem;
            color: rgba(115, 109, 101, 0.55);
            cursor: grab;
            touch-action: none;
            user-select: none;
            -webkit-user-select: none;
        }

        .menu-drag-handle:hover,
        .menu-drag-handle:focus-visible {
            color: #556344;
        }

        .menu-drag-handle:focus {
            outline: none;
        }

        .menu-drag-handle:focus-visible {
            box-shadow: inset 0 0 0 2px rgba(105, 122, 85, 0.35);
            border-radius: 0.25rem;
        }

        .menu-drag-handle:active,
        .menu-list-row.is-dragging .menu-drag-handle {
            cursor: grabbing;
        }

        .menu-category-row {
            transition: background-color 0.15s ease;
            position: relative;
        }

        .menu-category-row:hover {
            background-color: #EEF1E8;
        }

        .menu-category-row.is-selected {
            background-color: #E5EADD;
        }

        .menu-category-row.is-selected [data-category-label] {
            color: #556344;
        }

        .category-drag-handle {
            display: inline-flex;
            flex-shrink: 0;
            align-items: center;
            justify-content: center;
            align-self: stretch;
            width: 1.75rem;
            padding-left: 0.35rem;
            color: rgba(115, 109, 101, 0.55);
            cursor: grab;
            touch-action: none;
            user-select: none;
            -webkit-user-select: none;
        }

        .category-drag-handle:hover,
        .category-drag-handle:focus-visible {
            color: #556344;
        }

        .category-drag-handle:focus {
            outline: none;
        }

        .category-drag-handle:focus-visible {
            box-shadow: inset 0 0 0 2px rgba(105, 122, 85, 0.35);
            border-radius: 0.25rem;
        }

        .category-drag-handle:active,
        .menu-category-row.is-dragging .category-drag-handle {
            cursor: grabbing;
        }

        .menu-category-row.is-dragging {
            background-color: #E8EDE3;
            box-shadow: 0 2px 8px rgba(61, 56, 51, 0.08);
            opacity: 0.92;
            z-index: 1;
        }

        .menu-category-row.drag-insert-before::before,
        .menu-category-row.drag-insert-after::after {
            content: '';
            position: absolute;
            left: 0.5rem;
            right: 0.5rem;
            height: 2px;
            border-radius: 1px;
            background-color: #697A55;
            pointer-events: none;
            z-index: 2;
        }

        .menu-category-row.drag-insert-before::before {
            top: 0;
        }

        .menu-category-row.drag-insert-after::after {
            bottom: 0;
        }

        @media (prefers-reduced-motion: reduce) {
            .menu-category-row,
            .menu-list-row {
                transition: none;
            }
        }

        .menu-category-tab[aria-pressed="true"] {
            background-color: #E5EADD;
            border-color: #697A55;
            box-shadow: inset 0 -2px 0 #697A55;
        }

        .menu-category-tab[aria-pressed="true"] [data-category-label] {
            color: #556344;
        }

        .category-delete-x {
            display: inline-flex;
            height: 2rem;
            width: 2rem;
            flex-shrink: 0;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            border: 1px solid #E5E0D7;
            background-color: #fff;
            font-size: 1rem;
            line-height: 1;
            color: #A89D8C;
            box-shadow: none;
            opacity: 0;
            transition: opacity 0.15s ease, color 0.15s ease, border-color 0.15s ease, background-color 0.15s ease, box-shadow 0.15s ease;
        }

        .category-delete-x:hover {
            background-color: #F7F5F0;
            border-color: #D8D2C7;
            color: #736D65;
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        }

        .category-delete-x:active {
            background-color: #EEF1E8;
            color: #697A55;
        }

        .menu-category-row:hover .category-delete-x,
        .menu-category-row.is-selected .category-delete-x,
        .menu-list-row:hover .category-delete-x,
        .menu-list-row.is-selected .category-delete-x,
        .category-delete-x:focus,
        .category-delete-x:focus-visible {
            opacity: 1;
        }

        .category-delete-x:focus,
        .category-delete-x:focus-visible {
            outline: none;
            background-color: #F7F5F0;
            border-color: #D8D2C7;
            color: #736D65;
            box-shadow: 0 0 0 3px rgba(105, 122, 85, 0.15);
        }
    </style>

    <script>
        (function () {
            let selectedCategoryId = null;
            let selectedMenuId = null;
            let pendingDiscardId = null;

            const workspace = document.querySelector('[data-menus-workspace]');
            const categoryList = document.querySelector('[data-category-list]');
            const tabsWrap = document.querySelector('[data-category-tabs]');
            const tabsInner = document.querySelector('[data-category-tabs-inner]');
            const panelsWrap = document.querySelector('[data-category-panels]');
            const emptyWorkspace = document.querySelector('[data-menus-empty-workspace]');
            const bulkSaveBtn = document.querySelector('[data-bulk-save-btn]');
            const selectedInput = document.querySelector('[data-selected-category-input]');
            const discardModal = document.getElementById('category-discard-modal');
            const rowTemplate = document.getElementById('new-category-row-template');
            const tabTemplate = document.getElementById('new-category-tab-template');
            const panelTemplate = document.getElementById('new-category-panel-template');
            const menuRowTemplate = document.getElementById('new-menu-row-template');

            let nextNewIndex = workspace
                ? parseInt(workspace.getAttribute('data-next-new-index') || '1', 10) || 1
                : 1;
            let nextNewMenuIndex = workspace
                ? parseInt(workspace.getAttribute('data-next-new-menu-index') || '1', 10) || 1
                : 1;

            function syncPublishedLabel(checkbox) {
                const control = checkbox.closest('[data-published-control]');
                if (!control) {
                    return;
                }
                const text = control.querySelector('[data-published-text]');
                if (text) {
                    text.textContent = checkbox.checked ? '公開' : '非公開';
                }
            }

            function closeModals() {
                document.querySelectorAll('.menu-modal').forEach(function (el) {
                    el.classList.add('hidden');
                });
                pendingDiscardId = null;
            }

            function updateEmptyAndBulkVisibility() {
                const hasRows = document.querySelectorAll('[data-category-row]').length > 0;
                if (emptyWorkspace) {
                    emptyWorkspace.classList.toggle('hidden', hasRows);
                    if (hasRows) {
                        emptyWorkspace.setAttribute('hidden', '');
                    } else {
                        emptyWorkspace.removeAttribute('hidden');
                    }
                }
                if (bulkSaveBtn) {
                    bulkSaveBtn.classList.toggle('hidden', !hasRows);
                }
                if (tabsWrap) {
                    tabsWrap.classList.toggle('hidden', !hasRows);
                }
            }

            function selectCategory(categoryId, options) {
                options = options || {};
                selectedCategoryId = categoryId ? String(categoryId) : null;

                if (selectedInput) {
                    selectedInput.value = selectedCategoryId || '';
                }

                document.querySelectorAll('[data-category-panel]').forEach(function (panel) {
                    const isActive = selectedCategoryId && panel.getAttribute('data-category-panel') === selectedCategoryId;
                    panel.classList.toggle('hidden', !isActive);
                    if (isActive) {
                        panel.removeAttribute('hidden');
                    } else {
                        panel.setAttribute('hidden', '');
                    }
                });

                document.querySelectorAll('[data-select-category]').forEach(function (btn) {
                    const isActive = selectedCategoryId && btn.getAttribute('data-select-category') === selectedCategoryId;
                    btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                });

                document.querySelectorAll('[data-category-row]').forEach(function (row) {
                    row.classList.toggle('is-selected', selectedCategoryId && row.getAttribute('data-category-row') === selectedCategoryId);
                });

                if (options.scrollInvalid) {
                    const target = options.scrollInvalid;
                    if (typeof target.scrollIntoView === 'function') {
                        target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    if (typeof target.focus === 'function') {
                        try { target.focus({ preventScroll: true }); } catch (e) { target.focus(); }
                    }
                }

                if (options.focusName) {
                    const nameInput = document.querySelector('[data-category-name-input="' + selectedCategoryId + '"]');
                    if (nameInput) {
                        nameInput.focus();
                        nameInput.select();
                    }
                }
            }

            function selectMenu(menuId) {
                selectedMenuId = menuId ? String(menuId) : null;
                document.querySelectorAll('[data-menu-row]').forEach(function (row) {
                    row.classList.toggle('is-selected', selectedMenuId && row.getAttribute('data-menu-row') === selectedMenuId);
                });
            }

            function syncCategoryLabel(categoryId, value) {
                const label = value && value.trim() ? value : '新しいカテゴリ';
                document.querySelectorAll('[data-category-label="' + categoryId + '"]').forEach(function (el) {
                    el.textContent = label;
                });
            }

            function bindCategoryNameInput(input) {
                if (!input || input.dataset.boundName === '1') {
                    return;
                }
                input.dataset.boundName = '1';
                input.addEventListener('input', function () {
                    syncCategoryLabel(input.getAttribute('data-category-name-input'), input.value);
                });
            }

            function bindSelectButtons(root) {
                (root || document).querySelectorAll('[data-select-category]').forEach(function (btn) {
                    if (btn.dataset.boundSelect === '1') {
                        return;
                    }
                    btn.dataset.boundSelect = '1';
                    btn.addEventListener('click', function () {
                        selectCategory(btn.getAttribute('data-select-category'));
                    });
                });
            }

            function bindDiscardButtons(root) {
                (root || document).querySelectorAll('[data-discard-category]').forEach(function (btn) {
                    if (btn.dataset.boundDiscard === '1') {
                        return;
                    }
                    btn.dataset.boundDiscard = '1';
                    btn.addEventListener('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        requestDiscard(btn.getAttribute('data-discard-category'));
                    });
                });
            }

            function cloneTemplate(template, id) {
                const html = template.innerHTML.split('__ID__').join(id);
                const wrap = document.createElement('div');
                wrap.innerHTML = html.trim();
                return wrap.firstElementChild;
            }

            function getActivePanel() {
                if (!selectedCategoryId) {
                    return null;
                }
                return document.querySelector('[data-category-panel="' + selectedCategoryId + '"]');
            }

            function syncCategoryMenuCount(categoryId) {
                const panel = document.querySelector('[data-category-panel="' + categoryId + '"]');
                if (!panel) {
                    return;
                }
                const count = panel.querySelectorAll('[data-menu-tbody] [data-menu-row]').length;
                document.querySelectorAll('[data-category-count="' + categoryId + '"]').forEach(function (el) {
                    el.textContent = String(count);
                });

                const emptyEl = panel.querySelector('[data-menu-empty]');
                const tableEl = panel.querySelector('[data-menu-table]');
                const headerAdd = panel.querySelector('[data-menu-list-header] [data-menu-add-btn]');
                const hasMenus = count > 0;

                if (emptyEl) {
                    emptyEl.classList.toggle('hidden', hasMenus);
                    if (hasMenus) {
                        emptyEl.setAttribute('hidden', '');
                    } else {
                        emptyEl.removeAttribute('hidden');
                    }
                }
                if (tableEl) {
                    tableEl.classList.toggle('hidden', !hasMenus);
                    if (hasMenus) {
                        tableEl.removeAttribute('hidden');
                    } else {
                        tableEl.setAttribute('hidden', '');
                    }
                }
                if (headerAdd) {
                    headerAdd.classList.toggle('hidden', !hasMenus);
                }
            }

            function nextSortOrderForPanel(panel) {
                const tbody = panel.querySelector('[data-menu-tbody]');
                if (!tbody) {
                    return 1;
                }
                return tbody.querySelectorAll('[data-menu-row]').length + 1;
            }

            function renumberMenuSortOrders(panel) {
                if (!panel) {
                    return;
                }
                const tbody = panel.querySelector('[data-menu-tbody]');
                if (!tbody) {
                    return;
                }
                tbody.querySelectorAll('[data-menu-row]').forEach(function (row, index) {
                    const input = row.querySelector('[data-menu-sort-order]');
                    if (input) {
                        input.value = String(index + 1);
                    }
                });
            }

            function addInlineMenu() {
                if (!selectedCategoryId || !menuRowTemplate) {
                    return;
                }
                const panel = getActivePanel();
                if (!panel) {
                    return;
                }
                const tbody = panel.querySelector('[data-menu-tbody]');
                if (!tbody) {
                    return;
                }

                const menuId = 'new_menu_' + nextNewMenuIndex;
                nextNewMenuIndex += 1;
                if (workspace) {
                    workspace.setAttribute('data-next-new-menu-index', String(nextNewMenuIndex));
                }

                const sortOrder = nextSortOrderForPanel(panel);
                let html = menuRowTemplate.innerHTML
                    .split('__MENU_ID__').join(menuId)
                    .split('__CATEGORY_ID__').join(selectedCategoryId)
                    .split('__SORT__').join(String(sortOrder));
                const wrap = document.createElement('tbody');
                wrap.innerHTML = html.trim();
                const row = wrap.firstElementChild;
                tbody.appendChild(row);
                renumberMenuSortOrders(panel);

                syncCategoryMenuCount(selectedCategoryId);

                const nameInput = row.querySelector('[data-menu-name-input]');
                if (nameInput) {
                    nameInput.focus();
                }
                selectMenu(menuId);
            }

            function discardNewMenu(menuId) {
                const row = document.querySelector('[data-new-menu="' + menuId + '"]');
                if (!row) {
                    return;
                }
                const panel = row.closest('[data-category-panel]');
                const categoryId = panel ? panel.getAttribute('data-category-panel') : null;
                if (selectedMenuId && selectedMenuId === String(menuId)) {
                    selectedMenuId = null;
                }
                row.remove();
                if (panel) {
                    renumberMenuSortOrders(panel);
                }
                if (categoryId) {
                    syncCategoryMenuCount(categoryId);
                }
            }

            function addNewCategory() {
                if (!workspace || !categoryList || !panelsWrap || !rowTemplate || !panelTemplate) {
                    return;
                }

                const id = 'new_' + nextNewIndex;
                nextNewIndex += 1;
                workspace.setAttribute('data-next-new-index', String(nextNewIndex));

                const row = cloneTemplate(rowTemplate, id);
                categoryList.appendChild(row);

                if (tabsInner && tabTemplate) {
                    const tab = cloneTemplate(tabTemplate, id);
                    tabsInner.appendChild(tab);
                }

                const panel = cloneTemplate(panelTemplate, id);
                panelsWrap.appendChild(panel);

                renumberCategorySortOrders();

                bindSelectButtons(row);
                bindSelectButtons(tabsInner);
                bindDiscardButtons(row);
                bindDiscardButtons(panel);
                panel.querySelectorAll('[data-category-name-input]').forEach(bindCategoryNameInput);

                updateEmptyAndBulkVisibility();
                selectCategory(id, { focusName: true });
            }

            function getCategoryIdsInOrder() {
                const ids = [];
                document.querySelectorAll('[data-category-list] > [data-category-row]').forEach(function (row) {
                    ids.push(row.getAttribute('data-category-row'));
                });
                return ids;
            }

            function renumberCategorySortOrders() {
                document.querySelectorAll('[data-category-list] > [data-category-row]').forEach(function (row, index) {
                    const input = row.querySelector('[data-category-sort-order]');
                    if (input) {
                        input.value = String(index + 1);
                    }
                });
            }

            function syncAllSortOrdersFromDom() {
                renumberCategorySortOrders();
                document.querySelectorAll('[data-category-panel]').forEach(function (panel) {
                    renumberMenuSortOrders(panel);
                });
            }

            function syncCategoryTabsOrder() {
                if (!tabsInner) {
                    return;
                }
                getCategoryIdsInOrder().forEach(function (id) {
                    const tab = tabsInner.querySelector('[data-select-category="' + id + '"]');
                    if (tab) {
                        tabsInner.appendChild(tab);
                    }
                });
            }

            function clearCategoryDragIndicators() {
                if (!categoryList) {
                    return;
                }
                categoryList.querySelectorAll('.drag-insert-before, .drag-insert-after').forEach(function (row) {
                    row.classList.remove('drag-insert-before', 'drag-insert-after');
                });
            }

            function initCategoryDragDrop() {
                if (!categoryList) {
                    return;
                }

                let dragRow = null;

                categoryList.addEventListener('dragstart', function (e) {
                    const handle = e.target.closest('[data-category-drag-handle]');
                    if (!handle || !categoryList.contains(handle)) {
                        e.preventDefault();
                        return;
                    }
                    const row = handle.closest('[data-category-row]');
                    if (!row) {
                        e.preventDefault();
                        return;
                    }
                    dragRow = row;
                    row.classList.add('is-dragging');
                    e.dataTransfer.effectAllowed = 'move';
                    e.dataTransfer.setData('text/plain', row.getAttribute('data-category-row') || '');
                    try {
                        e.dataTransfer.setDragImage(row, 16, 20);
                    } catch (err) {
                        // ignore browsers that reject custom drag images
                    }
                });

                categoryList.addEventListener('dragend', function () {
                    if (dragRow) {
                        dragRow.classList.remove('is-dragging');
                    }
                    clearCategoryDragIndicators();
                    dragRow = null;
                });

                categoryList.addEventListener('dragover', function (e) {
                    if (!dragRow) {
                        return;
                    }
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'move';
                    const overRow = e.target.closest('[data-category-row]');
                    clearCategoryDragIndicators();
                    if (!overRow || overRow === dragRow || !categoryList.contains(overRow)) {
                        return;
                    }
                    const rect = overRow.getBoundingClientRect();
                    const before = e.clientY < rect.top + rect.height / 2;
                    overRow.classList.add(before ? 'drag-insert-before' : 'drag-insert-after');
                });

                categoryList.addEventListener('dragleave', function (e) {
                    if (!categoryList.contains(e.relatedTarget)) {
                        clearCategoryDragIndicators();
                    }
                });

                categoryList.addEventListener('drop', function (e) {
                    e.preventDefault();
                    if (!dragRow) {
                        return;
                    }
                    const overRow = e.target.closest('[data-category-row]');
                    if (overRow && overRow !== dragRow && categoryList.contains(overRow)) {
                        const rect = overRow.getBoundingClientRect();
                        const before = e.clientY < rect.top + rect.height / 2;
                        if (before) {
                            categoryList.insertBefore(dragRow, overRow);
                        } else {
                            categoryList.insertBefore(dragRow, overRow.nextSibling);
                        }
                        renumberCategorySortOrders();
                        syncCategoryTabsOrder();
                    }
                    clearCategoryDragIndicators();
                    dragRow.classList.remove('is-dragging');
                    dragRow = null;
                });

                categoryList.addEventListener('keydown', function (e) {
                    const handle = e.target.closest('[data-category-drag-handle]');
                    if (!handle || !categoryList.contains(handle)) {
                        return;
                    }
                    if (e.key !== 'ArrowUp' && e.key !== 'ArrowDown') {
                        return;
                    }
                    e.preventDefault();
                    const row = handle.closest('[data-category-row]');
                    if (!row) {
                        return;
                    }
                    if (e.key === 'ArrowUp' && row.previousElementSibling) {
                        categoryList.insertBefore(row, row.previousElementSibling);
                    } else if (e.key === 'ArrowDown' && row.nextElementSibling) {
                        categoryList.insertBefore(row.nextElementSibling, row);
                    } else {
                        return;
                    }
                    renumberCategorySortOrders();
                    syncCategoryTabsOrder();
                    handle.focus();
                });
            }

            function clearMenuDragIndicators(tbody) {
                if (!tbody) {
                    return;
                }
                tbody.querySelectorAll('.drag-insert-before, .drag-insert-after').forEach(function (row) {
                    row.classList.remove('drag-insert-before', 'drag-insert-after');
                });
            }

            function initMenuDragDrop() {
                if (!panelsWrap) {
                    return;
                }

                let dragRow = null;
                let dragTbody = null;

                panelsWrap.addEventListener('dragstart', function (e) {
                    const handle = e.target.closest('[data-menu-drag-handle]');
                    if (!handle || !panelsWrap.contains(handle)) {
                        return;
                    }
                    const row = handle.closest('[data-menu-row]');
                    const tbody = row ? row.closest('[data-menu-tbody]') : null;
                    if (!row || !tbody) {
                        e.preventDefault();
                        return;
                    }
                    dragRow = row;
                    dragTbody = tbody;
                    row.classList.add('is-dragging');
                    e.dataTransfer.effectAllowed = 'move';
                    e.dataTransfer.setData('text/plain', row.getAttribute('data-menu-row') || '');
                    try {
                        e.dataTransfer.setDragImage(row, 16, 20);
                    } catch (err) {
                        // ignore browsers that reject custom drag images
                    }
                });

                panelsWrap.addEventListener('dragend', function () {
                    if (dragRow) {
                        dragRow.classList.remove('is-dragging');
                    }
                    clearMenuDragIndicators(dragTbody);
                    dragRow = null;
                    dragTbody = null;
                });

                panelsWrap.addEventListener('dragover', function (e) {
                    if (!dragRow || !dragTbody) {
                        return;
                    }
                    const overRow = e.target.closest('[data-menu-row]');
                    if (!overRow || !dragTbody.contains(overRow)) {
                        return;
                    }
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'move';
                    clearMenuDragIndicators(dragTbody);
                    if (overRow === dragRow) {
                        return;
                    }
                    const rect = overRow.getBoundingClientRect();
                    const before = e.clientY < rect.top + rect.height / 2;
                    overRow.classList.add(before ? 'drag-insert-before' : 'drag-insert-after');
                });

                panelsWrap.addEventListener('dragleave', function (e) {
                    if (dragTbody && !dragTbody.contains(e.relatedTarget)) {
                        clearMenuDragIndicators(dragTbody);
                    }
                });

                panelsWrap.addEventListener('drop', function (e) {
                    if (!dragRow || !dragTbody) {
                        return;
                    }
                    const overRow = e.target.closest('[data-menu-row]');
                    if (!overRow || !dragTbody.contains(overRow)) {
                        return;
                    }
                    e.preventDefault();
                    if (overRow !== dragRow) {
                        const rect = overRow.getBoundingClientRect();
                        const before = e.clientY < rect.top + rect.height / 2;
                        if (before) {
                            dragTbody.insertBefore(dragRow, overRow);
                        } else {
                            dragTbody.insertBefore(dragRow, overRow.nextSibling);
                        }
                        const panel = dragTbody.closest('[data-category-panel]');
                        renumberMenuSortOrders(panel);
                    }
                    clearMenuDragIndicators(dragTbody);
                    dragRow.classList.remove('is-dragging');
                    dragRow = null;
                    dragTbody = null;
                });

                panelsWrap.addEventListener('keydown', function (e) {
                    const handle = e.target.closest('[data-menu-drag-handle]');
                    if (!handle || !panelsWrap.contains(handle)) {
                        return;
                    }
                    if (e.key !== 'ArrowUp' && e.key !== 'ArrowDown') {
                        return;
                    }
                    e.preventDefault();
                    const row = handle.closest('[data-menu-row]');
                    const tbody = row ? row.closest('[data-menu-tbody]') : null;
                    if (!row || !tbody) {
                        return;
                    }
                    if (e.key === 'ArrowUp' && row.previousElementSibling) {
                        tbody.insertBefore(row, row.previousElementSibling);
                    } else if (e.key === 'ArrowDown' && row.nextElementSibling) {
                        tbody.insertBefore(row.nextElementSibling, row);
                    } else {
                        return;
                    }
                    const panel = tbody.closest('[data-category-panel]');
                    renumberMenuSortOrders(panel);
                    handle.focus();
                });
            }

            function requestDiscard(id) {
                const nameInput = document.querySelector('[data-category-name-input="' + id + '"]');
                const nameVal = nameInput ? nameInput.value.trim() : '';
                const panel = document.querySelector('[data-category-panel="' + id + '"]');
                const hasMenus = panel ? panel.querySelectorAll('[data-menu-row]').length > 0 : false;
                // sort_order は追加・DnDで自動採番されるため、破棄確認の判定には使わない
                const hasInput = nameVal !== '' || hasMenus;

                if (!hasInput) {
                    const idsBefore = getCategoryIdsInOrder();
                    const idx = idsBefore.indexOf(id);
                    document.querySelectorAll('[data-category-row="' + id + '"]').forEach(function (el) { el.remove(); });
                    document.querySelectorAll('.menu-category-tab[data-select-category="' + id + '"]').forEach(function (el) { el.remove(); });
                    document.querySelectorAll('[data-category-panel="' + id + '"]').forEach(function (el) { el.remove(); });
                    renumberCategorySortOrders();
                    updateEmptyAndBulkVisibility();
                    const remaining = getCategoryIdsInOrder();
                    if (remaining.length === 0) {
                        selectCategory(null);
                    } else {
                        const nextId = (idx > 0 ? idsBefore[idx - 1] : null);
                        selectCategory(nextId && remaining.indexOf(nextId) !== -1 ? nextId : remaining[0]);
                    }
                    return;
                }

                pendingDiscardId = id;
                discardModal?.classList.remove('hidden');
            }

            function confirmDiscard() {
                if (!pendingDiscardId) {
                    return;
                }
                const id = pendingDiscardId;
                const idsBefore = getCategoryIdsInOrder();
                const idx = idsBefore.indexOf(id);
                pendingDiscardId = null;
                discardModal?.classList.add('hidden');

                document.querySelectorAll('[data-category-row="' + id + '"]').forEach(function (el) { el.remove(); });
                document.querySelectorAll('.menu-category-tab[data-select-category="' + id + '"]').forEach(function (el) { el.remove(); });
                document.querySelectorAll('[data-category-panel="' + id + '"]').forEach(function (el) { el.remove(); });
                renumberCategorySortOrders();
                updateEmptyAndBulkVisibility();

                const remaining = getCategoryIdsInOrder();
                if (remaining.length === 0) {
                    selectCategory(null);
                } else {
                    const prevId = idx > 0 ? idsBefore[idx - 1] : null;
                    selectCategory(prevId && remaining.indexOf(prevId) !== -1 ? prevId : remaining[0]);
                }
            }

            if (workspace) {
                const initialId = workspace.getAttribute('data-initial-category-id');
                if (initialId) {
                    selectCategory(initialId);
                }

                bindSelectButtons(document);
                document.querySelectorAll('[data-category-name-input]').forEach(bindCategoryNameInput);
                bindDiscardButtons(document);
                initCategoryDragDrop();
                initMenuDragDrop();

                if (bulkSaveBtn) {
                    bulkSaveBtn.addEventListener('click', function () {
                        syncAllSortOrdersFromDom();
                    }, true);
                }

                document.querySelectorAll('[data-add-category]').forEach(function (btn) {
                    btn.addEventListener('click', function (e) {
                        e.preventDefault();
                        addNewCategory();
                    });
                });

                workspace.addEventListener('keydown', function (e) {
                    if (e.key !== 'Enter') {
                        return;
                    }
                    const target = e.target;
                    if (!target || !target.closest) {
                        return;
                    }
                    if (target.tagName === 'TEXTAREA') {
                        return;
                    }
                    if (target.tagName === 'BUTTON' || (target.tagName === 'INPUT' && target.type === 'submit')) {
                        return;
                    }
                    if (target.matches('input, select')) {
                        e.preventDefault();
                    }
                });

                workspace.addEventListener('change', function (e) {
                    const checkbox = e.target.closest('[data-published-checkbox]');
                    if (checkbox) {
                        syncPublishedLabel(checkbox);
                    }
                });

                workspace.addEventListener('invalid', function (e) {
                    const field = e.target;
                    if (!field || !field.closest) {
                        return;
                    }
                    const panel = field.closest('[data-category-panel]');
                    if (!panel) {
                        return;
                    }
                    const categoryId = panel.getAttribute('data-category-panel');
                    if (categoryId && categoryId !== selectedCategoryId) {
                        selectCategory(categoryId, { scrollInvalid: field });
                    } else if (typeof field.scrollIntoView === 'function') {
                        field.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        try { field.focus({ preventScroll: true }); } catch (err) { field.focus(); }
                    }
                }, true);
            }

            document.addEventListener('click', function (e) {
                const discardMenuBtn = e.target.closest('[data-discard-menu]');
                if (discardMenuBtn) {
                    e.preventDefault();
                    discardNewMenu(discardMenuBtn.getAttribute('data-discard-menu'));
                    return;
                }

                const menuRow = e.target.closest('[data-menu-row]');
                if (menuRow) {
                    selectMenu(menuRow.getAttribute('data-menu-row'));
                }

                const menuAddTrigger = e.target.closest('[data-menu-add-btn]');
                if (!menuAddTrigger) {
                    return;
                }
                e.preventDefault();
                addInlineMenu();
            });

            document.querySelectorAll('.menu-modal').forEach(function (modal) {
                modal.addEventListener('click', function (e) {
                    if (e.target === modal) {
                        closeModals();
                    }
                });
            });

            discardModal?.querySelector('[data-discard-cancel]')?.addEventListener('click', function () {
                pendingDiscardId = null;
                discardModal.classList.add('hidden');
            });
            discardModal?.querySelector('[data-discard-confirm]')?.addEventListener('click', confirmDiscard);
        })();
    </script>
@endsection
