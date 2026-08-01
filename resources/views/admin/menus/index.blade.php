@extends('layouts.admin')

@section('heading', 'メニュー・料金管理')

@section('content')
    <div class="sticky top-[4.5rem] z-10 -mx-4 mb-6 border-b border-[#E3DDD2] bg-[#FAF7F1]/95 px-4 py-3 shadow-[0_1px_0_rgba(58,51,46,0.04)] backdrop-blur-sm md:-mx-8 md:px-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-6">
            <div class="flex min-w-0 flex-wrap items-center gap-3">
                @if($categories->isNotEmpty())
                    <button
                        type="button"
                        class="admin-btn shadow-md shrink-0"
                        data-admin-confirm-trigger
                        data-confirm-form="menus-bulk-form"
                        data-confirm-title="一括保存の確認"
                        data-confirm-message="変更内容を一括保存します。&#10;よろしいですか？"
                        data-confirm-note="カテゴリ、メニュー、料金、表示順、公開状態など、現在入力されている内容が反映されます。"
                        data-confirm-submit-label="一括保存する"
                    >一括保存</button>
                @endif
                <p class="text-sm text-gray-600">
                    表形式で編集し、「一括保存」で反映できます
                </p>
            </div>

            <div class="flex flex-wrap gap-2 sm:shrink-0 sm:justify-end">
                <x-admin.create-button data-open-modal="category-add-modal">カテゴリ追加</x-admin.create-button>
                <x-admin.create-button data-open-modal="menu-add-modal" :disabled="$categories->isEmpty()">メニュー追加</x-admin.create-button>
            </div>
        </div>
    </div>

    @if($categories->isEmpty())
        <div class="admin-card text-center text-gray-500">
            <p class="mb-4">カテゴリがありません。まずカテゴリを追加してください。</p>
            <x-admin.create-button data-open-modal="category-add-modal">カテゴリ追加</x-admin.create-button>
        </div>
    @else
        <form method="POST" action="{{ route('admin.menus.bulk-update') }}" id="menus-bulk-form">
            @csrf
            @method('PUT')

            @foreach($categories as $category)
                <div class="admin-card mb-6 overflow-x-auto">
                    <div class="mb-4 flex flex-wrap items-end justify-between gap-4 border-b border-gray-100 pb-4">
                        <div class="flex min-w-0 flex-1 flex-wrap items-end gap-4">
                            <div class="min-w-[200px] flex-1">
                                <label class="admin-label">カテゴリ名</label>
                                <input type="text" name="categories[{{ $category->id }}][name]" value="{{ old('categories.'.$category->id.'.name', $category->name) }}" required class="admin-input font-medium">
                            </div>
                            <div class="w-28">
                                <label class="admin-label">表示順</label>
                                <input type="number" name="categories[{{ $category->id }}][sort_order]" value="{{ old('categories.'.$category->id.'.sort_order', $category->sort_order) }}" min="0" class="admin-input">
                            </div>
                        </div>
                        <x-admin.delete-button
                            :form="'delete-category-'.$category->id"
                            :message="'「'.$category->name.'」カテゴリと配下のメニューを削除しますか？'"
                        >削除</x-admin.delete-button>
                    </div>

                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 text-left text-xs text-gray-500">
                                <th class="pb-3 pr-3 font-medium">メニュー名</th>
                                <th class="pb-3 pr-3 font-medium w-28">料金（円）</th>
                                <th class="pb-3 pr-3 font-medium w-24">表示順</th>
                                <th class="pb-3 pr-3 font-medium w-28">公開</th>
                                <th class="pb-3 pr-3 font-medium">説明</th>
                                <th class="pb-3 font-medium min-w-[9rem] text-right">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($category->menus as $menu)
                                <tr class="border-b border-gray-100 align-top">
                                    <td class="py-3 pr-3">
                                        <input type="text" name="menus[{{ $menu->id }}][name]" value="{{ old('menus.'.$menu->id.'.name', $menu->name) }}" required class="admin-input py-1.5">
                                    </td>
                                    <td class="py-3 pr-3">
                                        <input type="number" name="menus[{{ $menu->id }}][price]" value="{{ old('menus.'.$menu->id.'.price', $menu->price) }}" min="0" required class="admin-input py-1.5">
                                    </td>
                                    <td class="py-3 pr-3">
                                        <input type="number" name="menus[{{ $menu->id }}][sort_order]" value="{{ old('menus.'.$menu->id.'.sort_order', $menu->sort_order) }}" min="0" class="admin-input py-1.5">
                                    </td>
                                    <td class="py-3 pr-3">
                                        <select name="menus[{{ $menu->id }}][is_published]" class="admin-input py-1.5">
                                            <option value="1" @selected(old('menus.'.$menu->id.'.is_published', $menu->is_published ? '1' : '0') == '1')>公開</option>
                                            <option value="0" @selected(old('menus.'.$menu->id.'.is_published', $menu->is_published ? '1' : '0') == '0')>非公開</option>
                                        </select>
                                    </td>
                                    <td class="py-3 pr-3">
                                        <input type="hidden" name="menus[{{ $menu->id }}][description]" id="menu-description-{{ $menu->id }}" value="{{ old('menus.'.$menu->id.'.description', $menu->description) }}">
                                        <p class="line-clamp-2 text-xs text-gray-500" id="menu-description-preview-{{ $menu->id }}">{{ $menu->description ?: '（未入力）' }}</p>
                                    </td>
                                    <td class="py-3 text-right">
                                        <x-admin.action-group>
                                            <x-admin.edit-button
                                                data-open-detail
                                                data-menu-id="{{ $menu->id }}"
                                                data-menu-name="{{ $menu->name }}"
                                            />
                                            <x-admin.delete-button
                                                :form="'delete-menu-'.$menu->id"
                                                :name="$menu->name"
                                            />
                                        </x-admin.action-group>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-6 text-center text-gray-500">このカテゴリにメニューはありません</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endforeach
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
    @endif

    {{-- カテゴリ追加モーダル --}}
    <div id="category-add-modal" class="menu-modal hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-lg">
            <h2 class="mb-4 text-lg font-semibold">カテゴリ追加</h2>
            <form method="POST" action="{{ route('admin.menus.categories.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="category_name" class="admin-label">カテゴリ名</label>
                    <input type="text" name="name" id="category_name" required class="admin-input">
                </div>
                <div>
                    <label for="category_sort_order" class="admin-label">表示順</label>
                    <input type="number" name="sort_order" id="category_sort_order" value="0" min="0" class="admin-input">
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" class="admin-btn-secondary" data-close-modal>キャンセル</button>
                    <button type="submit" class="admin-btn">追加する</button>
                </div>
            </form>
        </div>
    </div>

    {{-- メニュー追加モーダル --}}
    @if($categories->isNotEmpty())
        <div id="menu-add-modal" class="menu-modal hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="w-full max-w-lg rounded-lg bg-white p-6 shadow-lg">
                <h2 class="mb-4 text-lg font-semibold">メニュー追加</h2>
                <form method="POST" id="menu-add-form" action="{{ route('admin.menus.store', $categories->first()) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="add_menu_category_id" class="admin-label">カテゴリ</label>
                        <select id="add_menu_category_id" class="admin-input" onchange="document.getElementById('menu-add-form').action = '{{ url('admin/menus/categories') }}/' + this.value + '/menus'">
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="add_menu_name" class="admin-label">メニュー名</label>
                        <input type="text" name="name" id="add_menu_name" required class="admin-input">
                    </div>
                    <div>
                        <label for="add_menu_price" class="admin-label">料金（円）</label>
                        <input type="number" name="price" id="add_menu_price" min="0" required class="admin-input">
                    </div>
                    <div>
                        <label for="add_menu_description" class="admin-label">説明</label>
                        <textarea name="description" id="add_menu_description" rows="3" class="admin-input"></textarea>
                    </div>
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <label for="add_menu_sort_order" class="admin-label">表示順</label>
                            <input type="number" name="sort_order" id="add_menu_sort_order" value="0" min="0" class="admin-input">
                        </div>
                        <label class="flex items-center gap-2 self-end pb-2 text-sm">
                            <input type="checkbox" name="is_published" value="1" checked>
                            公開する
                        </label>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" class="admin-btn-secondary" data-close-modal>キャンセル</button>
                        <button type="submit" class="admin-btn">追加する</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- 説明詳細編集モーダル --}}
    <div id="menu-detail-modal" class="menu-modal hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <div class="w-full max-w-lg rounded-lg bg-white p-6 shadow-lg">
            <h2 class="mb-1 text-lg font-semibold">詳細編集</h2>
            <p id="detail-menu-name" class="mb-4 text-sm text-gray-500"></p>
            <div>
                <label for="detail-description" class="admin-label">ひとこと説明・補足説明</label>
                <textarea id="detail-description" rows="6" class="admin-input"></textarea>
                <p class="mt-1 text-xs text-gray-500">「反映」で一覧にセットし、「一括保存」でDBに保存されます。</p>
            </div>
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" class="admin-btn-secondary" data-close-modal>キャンセル</button>
                <button type="button" class="admin-btn" id="apply-detail-btn">反映</button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            let activeMenuId = null;

            function openModal(id) {
                document.getElementById(id)?.classList.remove('hidden');
            }

            function closeModals() {
                document.querySelectorAll('.menu-modal').forEach(function (el) {
                    el.classList.add('hidden');
                });
                activeMenuId = null;
            }

            document.querySelectorAll('[data-open-modal]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    openModal(btn.getAttribute('data-open-modal'));
                });
            });

            document.querySelectorAll('[data-close-modal]').forEach(function (btn) {
                btn.addEventListener('click', closeModals);
            });

            document.querySelectorAll('.menu-modal').forEach(function (modal) {
                modal.addEventListener('click', function (e) {
                    if (e.target === modal) {
                        closeModals();
                    }
                });
            });

            document.querySelectorAll('[data-open-detail]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    activeMenuId = btn.getAttribute('data-menu-id');
                    const hidden = document.getElementById('menu-description-' + activeMenuId);
                    document.getElementById('detail-menu-name').textContent = btn.getAttribute('data-menu-name');
                    document.getElementById('detail-description').value = hidden ? hidden.value : '';
                    openModal('menu-detail-modal');
                });
            });

            document.getElementById('apply-detail-btn')?.addEventListener('click', function () {
                if (!activeMenuId) {
                    return;
                }

                const value = document.getElementById('detail-description').value;
                const hidden = document.getElementById('menu-description-' + activeMenuId);
                const preview = document.getElementById('menu-description-preview-' + activeMenuId);

                if (hidden) {
                    hidden.value = value;
                }
                if (preview) {
                    preview.textContent = value.trim() ? value : '（未入力）';
                }

                closeModals();
            });
        })();
    </script>
@endsection
