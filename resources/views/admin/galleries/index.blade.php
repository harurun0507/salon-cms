@extends('layouts.admin')

@section('heading', 'ギャラリー管理')

@section('content')
    <div class="mb-6 flex justify-between">
        <p class="text-sm text-gray-500">画像のアップロード・表示順・公開設定</p>
        <x-admin.create-button type="button" data-open-gallery-create>画像を追加</x-admin.create-button>
    </div>

    <div id="gallery-grid" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($galleries as $gallery)
            <div class="admin-card" data-gallery-card="{{ $gallery->id }}" data-sort-order="{{ $gallery->sort_order }}">
                <img src="{{ asset('storage/'.$gallery->image_path) }}" alt="" class="gallery-image mb-3 aspect-[4/3] w-full rounded object-cover">
                <p class="gallery-caption text-sm">{{ $gallery->caption ?: '（キャプションなし）' }}</p>
                <p class="gallery-meta mt-1 text-xs text-gray-500">表示順: {{ $gallery->sort_order }} / {{ $gallery->is_published ? '公開' : '非公開' }}</p>
                <div class="mt-4">
                    <x-admin.action-group class="justify-start">
                        <x-admin.edit-button
                            type="button"
                            data-open-gallery-edit
                            data-gallery-id="{{ $gallery->id }}"
                        />
                        <x-admin.delete-button
                            :action="route('admin.galleries.destroy', $gallery)"
                            message="ギャラリー画像を削除しますか？"
                        />
                    </x-admin.action-group>
                </div>
            </div>
        @empty
            <p id="gallery-empty-message" class="text-gray-500">ギャラリー画像がありません。</p>
        @endforelse
    </div>
    <div class="mt-4">{{ $galleries->links() }}</div>

    <template id="gallery-card-template">
        <div class="admin-card" data-gallery-card="" data-sort-order="0">
            <img src="" alt="" class="gallery-image mb-3 aspect-[4/3] w-full rounded object-cover">
            <p class="gallery-caption text-sm"></p>
            <p class="gallery-meta mt-1 text-xs text-gray-500"></p>
            <div class="mt-4">
                <div class="admin-action-group justify-start">
                    <button type="button" class="btn-admin-edit" data-open-gallery-edit data-gallery-id="">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M2.695 14.763l-1.262 3.154a.5.5 0 0 0 .65.65l3.155-1.262a4 4 0 0 0 1.343-.885L17.5 5.5a2.121 2.121 0 0 0-3-3L3.58 13.42a4 4 0 0 0-.885 1.343Z"/></svg>
                        <span>編集</span>
                    </button>
                    <form action="" method="POST" class="inline" data-admin-delete-form>
                        <input type="hidden" name="_token" value="">
                        <input type="hidden" name="_method" value="DELETE">
                        <button
                            type="button"
                            data-admin-delete-trigger
                            data-delete-message="ギャラリー画像を削除しますか？"
                            class="btn-admin-delete"
                        >
                            <svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8.5 3a1.5 1.5 0 0 0-1.415 1H4a1 1 0 0 0 0 2h.293l.72 9.364A2.5 2.5 0 0 0 7.505 18h4.99a2.5 2.5 0 0 0 2.492-2.636L15.707 6H16a1 1 0 1 0 0-2h-3.085A1.5 1.5 0 0 0 11.5 3h-3Zm1 1a.5.5 0 0 0-.5.5V5h2v-.5a.5.5 0 0 0-.5-.5h-1ZM7.3 6l.69 8.97a.5.5 0 0 0 .498.53h3.024a.5.5 0 0 0 .498-.53L12.7 6H7.3Z" clip-rule="evenodd"/></svg>
                            <span>削除</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </template>

    @php
        $galleryEditPayload = $galleries->getCollection()->mapWithKeys(function ($gallery) {
            return [
                (string) $gallery->id => [
                    'id' => $gallery->id,
                    'caption' => $gallery->caption,
                    'sort_order' => (int) $gallery->sort_order,
                    'is_published' => (bool) $gallery->is_published,
                    'image_url' => asset('storage/'.$gallery->image_path),
                    'update_url' => route('admin.galleries.update', $gallery),
                ],
            ];
        });
    @endphp
    <script type="application/json" id="gallery-edit-data">{!! json_encode($galleryEditPayload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>

    {{-- ギャラリー作成モーダル（一覧に1つ） --}}
    <div
        id="gallery-create-modal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="gallery-create-modal-title"
        hidden
    >
        <div class="flex max-h-[90vh] w-full max-w-xl flex-col overflow-hidden rounded-lg bg-white shadow-lg" data-gallery-create-modal-panel>
            <div class="flex shrink-0 items-center justify-between border-b border-gray-200 px-6 py-4">
                <h2 id="gallery-create-modal-title" class="text-lg font-semibold text-gray-900">ギャラリー登録</h2>
                <button
                    type="button"
                    class="rounded-md p-1 text-gray-500 hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-salon-button/40"
                    aria-label="閉じる"
                    data-close-gallery-create
                >
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/>
                    </svg>
                </button>
            </div>

            <form
                id="gallery-create-form"
                method="POST"
                action="{{ route('admin.galleries.store') }}"
                enctype="multipart/form-data"
                class="flex min-h-0 flex-1 flex-col"
            >
                @csrf

                <div class="space-y-5 overflow-y-auto px-6 py-5">
                    <div>
                        <label for="create-gallery-image" class="admin-label">画像</label>
                        <input type="file" name="image" id="create-gallery-image" accept="image/jpeg,image/png,image/webp" class="admin-input">
                        <p class="mt-1 text-xs text-gray-500">JPEG / PNG / WebP、最大5MB。選択すると下にプレビューが表示されます。</p>
                        <img id="create-gallery-image-preview" src="" alt="" class="mt-2 hidden h-40 rounded object-cover">
                        <p class="mt-1 hidden text-sm text-red-600" data-error-for="image"></p>
                        <p id="create-gallery-image-reselect-hint" class="mt-1 hidden text-xs text-amber-700">
                            画像のバリデーションエラー後は、ブラウザの仕様上ファイルを再選択する必要があります。
                        </p>
                    </div>

                    <div>
                        <label for="create-gallery-caption" class="admin-label">キャプション</label>
                        <input type="text" name="caption" id="create-gallery-caption" class="admin-input">
                        <p class="mt-1 hidden text-sm text-red-600" data-error-for="caption"></p>
                    </div>

                    <div>
                        <label for="create-gallery-sort_order" class="admin-label">表示順</label>
                        <input type="number" name="sort_order" id="create-gallery-sort_order" min="0" value="0" class="admin-input">
                        <p class="mt-1 hidden text-sm text-red-600" data-error-for="sort_order"></p>
                    </div>

                    <div>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_published" id="create-gallery-is_published" value="1" checked>
                            公開する
                        </label>
                        <p class="mt-1 hidden text-sm text-red-600" data-error-for="is_published"></p>
                    </div>
                </div>

                <div class="flex shrink-0 justify-end gap-3 border-t border-gray-200 px-6 py-4">
                    <button type="button" class="admin-btn-secondary" data-close-gallery-create>キャンセル</button>
                    <button
                        type="submit"
                        id="gallery-create-submit"
                        class="inline-flex items-center rounded-md bg-salon-button px-4 py-2 text-sm font-medium text-white hover:bg-[#4f5d44] disabled:cursor-not-allowed disabled:opacity-60"
                    >登録する</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ギャラリー編集モーダル（一覧に1つ） --}}
    <div
        id="gallery-edit-modal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="gallery-edit-modal-title"
        hidden
    >
        <div class="flex max-h-[90vh] w-full max-w-xl flex-col overflow-hidden rounded-lg bg-white shadow-lg" data-gallery-modal-panel>
            <div class="flex shrink-0 items-center justify-between border-b border-gray-200 px-6 py-4">
                <h2 id="gallery-edit-modal-title" class="text-lg font-semibold text-gray-900">ギャラリー編集</h2>
                <button
                    type="button"
                    class="rounded-md p-1 text-gray-500 hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-salon-button/40"
                    aria-label="閉じる"
                    data-close-gallery-edit
                >
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/>
                    </svg>
                </button>
            </div>

            <form id="gallery-edit-form" method="POST" enctype="multipart/form-data" class="flex min-h-0 flex-1 flex-col">
                @csrf
                <input type="hidden" name="_method" value="PUT">

                <div class="space-y-5 overflow-y-auto px-6 py-5">
                    <div>
                        <p class="admin-label">登録済み画像</p>
                        <div id="modal-gallery-image-wrap" class="mt-1">
                            <img id="modal-gallery-image-preview" src="" alt="" class="hidden h-40 rounded object-cover">
                            <p id="modal-gallery-image-empty" class="text-sm text-gray-500">画像は登録されていません</p>
                        </div>
                    </div>

                    <div>
                        <label for="modal-gallery-image" class="admin-label">画像（変更する場合のみ）</label>
                        <input type="file" name="image" id="modal-gallery-image" accept="image/jpeg,image/png,image/webp" class="admin-input">
                        <p class="mt-1 text-xs text-gray-500">JPEG / PNG / WebP、最大5MB。選択すると上のプレビューが切り替わります。</p>
                        <p class="mt-1 hidden text-sm text-red-600" data-error-for="image"></p>
                        <p id="modal-gallery-image-reselect-hint" class="mt-1 hidden text-xs text-amber-700">
                            画像のバリデーションエラー後は、ブラウザの仕様上ファイルを再選択する必要があります。
                        </p>
                    </div>

                    <div>
                        <label for="modal-gallery-caption" class="admin-label">キャプション</label>
                        <input type="text" name="caption" id="modal-gallery-caption" class="admin-input">
                        <p class="mt-1 hidden text-sm text-red-600" data-error-for="caption"></p>
                    </div>

                    <div>
                        <label for="modal-gallery-sort_order" class="admin-label">表示順</label>
                        <input type="number" name="sort_order" id="modal-gallery-sort_order" min="0" class="admin-input">
                        <p class="mt-1 hidden text-sm text-red-600" data-error-for="sort_order"></p>
                    </div>

                    <div>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_published" id="modal-gallery-is_published" value="1">
                            公開する
                        </label>
                        <p class="mt-1 hidden text-sm text-red-600" data-error-for="is_published"></p>
                    </div>
                </div>

                <div class="flex shrink-0 justify-end gap-3 border-t border-gray-200 px-6 py-4">
                    <button type="button" class="admin-btn-secondary" data-close-gallery-edit>キャンセル</button>
                    <button
                        type="submit"
                        id="gallery-edit-submit"
                        class="inline-flex items-center rounded-md bg-salon-button px-4 py-2 text-sm font-medium text-white hover:bg-[#4f5d44] disabled:cursor-not-allowed disabled:opacity-60"
                    >更新する</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const dataEl = document.getElementById('gallery-edit-data');
            let galleryData = {};
            try {
                galleryData = JSON.parse(dataEl?.textContent || '{}');
            } catch (e) {
                galleryData = {};
            }

            // ---- 編集モーダル ----
            const editModal = document.getElementById('gallery-edit-modal');
            const editForm = document.getElementById('gallery-edit-form');
            const editSubmitBtn = document.getElementById('gallery-edit-submit');
            let editOpenTrigger = null;
            let editSubmitting = false;
            let savedImageUrl = null;

            const editImageInput = document.getElementById('modal-gallery-image');
            const editCaptionInput = document.getElementById('modal-gallery-caption');
            const editSortOrderInput = document.getElementById('modal-gallery-sort_order');
            const editIsPublishedInput = document.getElementById('modal-gallery-is_published');
            const editImagePreview = document.getElementById('modal-gallery-image-preview');
            const editImageEmpty = document.getElementById('modal-gallery-image-empty');
            const editImageReselectHint = document.getElementById('modal-gallery-image-reselect-hint');

            function setEditImagePreview(url) {
                if (url) {
                    editImagePreview.src = url;
                    editImagePreview.classList.remove('hidden');
                    editImageEmpty.classList.add('hidden');
                } else {
                    editImagePreview.removeAttribute('src');
                    editImagePreview.classList.add('hidden');
                    editImageEmpty.classList.remove('hidden');
                }
            }

            function clearEditErrors() {
                editForm.querySelectorAll('[data-error-for]').forEach(function (el) {
                    el.textContent = '';
                    el.classList.add('hidden');
                });
                editImageReselectHint.classList.add('hidden');
            }

            function showEditErrors(errors) {
                clearEditErrors();
                Object.keys(errors || {}).forEach(function (field) {
                    const target = editForm.querySelector('[data-error-for="' + field + '"]');
                    if (target && errors[field] && errors[field][0]) {
                        target.textContent = errors[field][0];
                        target.classList.remove('hidden');
                    }
                });

                if (errors && errors.image) {
                    editImageInput.value = '';
                    setEditImagePreview(savedImageUrl);
                    editImageReselectHint.classList.remove('hidden');
                }
            }

            function resetEditFormState() {
                editForm.reset();
                editImageInput.value = '';
                clearEditErrors();
                savedImageUrl = null;
                setEditImagePreview(null);
                editSubmitBtn.disabled = false;
                editSubmitBtn.textContent = '更新する';
                editSubmitting = false;
            }

            function fillEditForm(item) {
                resetEditFormState();
                editForm.action = item.update_url;
                editCaptionInput.value = item.caption || '';
                editSortOrderInput.value = item.sort_order != null ? item.sort_order : 0;
                editIsPublishedInput.checked = !!item.is_published;
                savedImageUrl = item.image_url || null;
                setEditImagePreview(savedImageUrl);
            }

            function openEditModal(galleryId, trigger) {
                const item = galleryData[String(galleryId)];
                if (!item) {
                    return;
                }

                editOpenTrigger = trigger || null;
                fillEditForm(item);
                editModal.classList.remove('hidden');
                editModal.classList.add('flex');
                editModal.removeAttribute('hidden');
                if (window.AdminUi) {
                    window.AdminUi.lockBody();
                } else {
                    document.body.style.overflow = 'hidden';
                }

                requestAnimationFrame(function () {
                    setTimeout(function () {
                        editCaptionInput.focus();
                    }, 0);
                });
            }

            function closeEditModal() {
                if (editSubmitting) {
                    return;
                }

                editModal.classList.add('hidden');
                editModal.classList.remove('flex');
                editModal.setAttribute('hidden', '');
                if (window.AdminUi) {
                    window.AdminUi.unlockBody();
                } else {
                    document.body.style.overflow = '';
                }

                resetEditFormState();

                const restore = editOpenTrigger;
                editOpenTrigger = null;
                if (restore && typeof restore.focus === 'function') {
                    restore.focus();
                }
            }

            function updateCard(gallery) {
                const card = document.querySelector('[data-gallery-card="' + gallery.id + '"]');
                if (!card) {
                    return;
                }

                const sortOrder = gallery.sort_order != null ? gallery.sort_order : 0;
                card.setAttribute('data-sort-order', String(sortOrder));

                const imageEl = card.querySelector('.gallery-image');
                if (imageEl && gallery.image_url) {
                    imageEl.src = gallery.image_url;
                }

                const captionEl = card.querySelector('.gallery-caption');
                if (captionEl) {
                    captionEl.textContent = gallery.caption || '（キャプションなし）';
                }

                const metaEl = card.querySelector('.gallery-meta');
                if (metaEl) {
                    const status = gallery.is_published ? '公開' : '非公開';
                    metaEl.textContent = '表示順: ' + sortOrder + ' / ' + status;
                }
            }

            document.addEventListener('click', function (e) {
                const btn = e.target.closest('[data-open-gallery-edit]');
                if (!btn) {
                    return;
                }
                openEditModal(btn.getAttribute('data-gallery-id'), btn);
            });

            document.querySelectorAll('[data-close-gallery-edit]').forEach(function (btn) {
                btn.addEventListener('click', closeEditModal);
            });

            editModal.addEventListener('click', function (e) {
                if (e.target === editModal) {
                    closeEditModal();
                }
            });

            document.addEventListener('keydown', function (e) {
                if (editModal.classList.contains('hidden')) {
                    return;
                }

                if (e.key === 'Escape') {
                    e.preventDefault();
                    closeEditModal();
                    return;
                }

                if (window.AdminUi) {
                    window.AdminUi.trapFocus(e, editModal);
                }
            });

            editImageInput.addEventListener('change', function () {
                const file = editImageInput.files && editImageInput.files[0];
                editImageReselectHint.classList.add('hidden');

                if (!file) {
                    setEditImagePreview(savedImageUrl);
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (event) {
                    setEditImagePreview(event.target.result);
                };
                reader.readAsDataURL(file);
            });

            editForm.addEventListener('submit', function (e) {
                e.preventDefault();
                if (editSubmitting) {
                    return;
                }

                editSubmitting = true;
                editSubmitBtn.disabled = true;
                editSubmitBtn.textContent = '更新中...';
                clearEditErrors();

                const formData = new FormData(editForm);

                fetch(editForm.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: formData,
                    credentials: 'same-origin',
                })
                    .then(async function (response) {
                        const data = await response.json().catch(function () {
                            return {};
                        });

                        if (response.status === 422) {
                            showEditErrors(data.errors || {});
                            if (typeof window.showToast === 'function') {
                                window.showToast('入力内容を確認してください。', 'error');
                            }
                            return;
                        }

                        if (!response.ok) {
                            showEditErrors({ caption: [data.message || '更新に失敗しました。'] });
                            if (typeof window.showToast === 'function') {
                                window.showToast(data.message || '更新に失敗しました。', 'error');
                            }
                            return;
                        }

                        const gallery = data.gallery;
                        if (gallery) {
                            galleryData[String(gallery.id)] = {
                                id: gallery.id,
                                caption: gallery.caption,
                                sort_order: gallery.sort_order,
                                is_published: !!gallery.is_published,
                                image_url: gallery.image_url || null,
                                update_url: editForm.action,
                            };
                            if (dataEl) {
                                dataEl.textContent = JSON.stringify(galleryData);
                            }
                            updateCard(gallery);
                        }

                        editSubmitting = false;
                        editSubmitBtn.disabled = false;
                        editSubmitBtn.textContent = '更新する';
                        closeEditModal();

                        if (typeof window.showToast === 'function') {
                            window.showToast(data.message || 'ギャラリーを更新しました。', 'success');
                        }
                    })
                    .catch(function () {
                        showEditErrors({ caption: ['通信エラーが発生しました。'] });
                        if (typeof window.showToast === 'function') {
                            window.showToast('通信エラーが発生しました。', 'error');
                        }
                    })
                    .finally(function () {
                        editSubmitting = false;
                        editSubmitBtn.disabled = false;
                        editSubmitBtn.textContent = '更新する';
                    });
            });

            // ---- 作成モーダル ----
            const createModal = document.getElementById('gallery-create-modal');
            const createForm = document.getElementById('gallery-create-form');
            const createSubmitBtn = document.getElementById('gallery-create-submit');
            const createImageInput = document.getElementById('create-gallery-image');
            const createCaptionInput = document.getElementById('create-gallery-caption');
            const createSortOrderInput = document.getElementById('create-gallery-sort_order');
            const createIsPublishedInput = document.getElementById('create-gallery-is_published');
            const createImagePreview = document.getElementById('create-gallery-image-preview');
            const createImageReselectHint = document.getElementById('create-gallery-image-reselect-hint');
            const cardTemplate = document.getElementById('gallery-card-template');
            const galleryGrid = document.getElementById('gallery-grid');
            const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
            const MAX_IMAGE_BYTES = 5 * 1024 * 1024;

            let createOpenTrigger = null;
            let createSubmitting = false;

            function setCreateImagePreview(url) {
                if (url) {
                    createImagePreview.src = url;
                    createImagePreview.classList.remove('hidden');
                } else {
                    createImagePreview.removeAttribute('src');
                    createImagePreview.classList.add('hidden');
                }
            }

            function clearCreateErrors() {
                createForm.querySelectorAll('[data-error-for]').forEach(function (el) {
                    el.textContent = '';
                    el.classList.add('hidden');
                });
                createImageReselectHint.classList.add('hidden');
            }

            function showCreateFieldError(field, message) {
                const target = createForm.querySelector('[data-error-for="' + field + '"]');
                if (target) {
                    target.textContent = message;
                    target.classList.remove('hidden');
                }
            }

            function showCreateErrors(errors) {
                clearCreateErrors();
                Object.keys(errors || {}).forEach(function (field) {
                    if (errors[field] && errors[field][0]) {
                        showCreateFieldError(field, errors[field][0]);
                    }
                });

                if (errors && errors.image) {
                    createImageInput.value = '';
                    setCreateImagePreview(null);
                    createImageReselectHint.classList.remove('hidden');
                }
            }

            function resetCreateFormState() {
                createForm.reset();
                createImageInput.value = '';
                createSortOrderInput.value = '0';
                createIsPublishedInput.checked = true;
                clearCreateErrors();
                setCreateImagePreview(null);
                createSubmitBtn.disabled = false;
                createSubmitBtn.textContent = '登録する';
                createSubmitting = false;
            }

            function openCreateModal(trigger) {
                createOpenTrigger = trigger || null;
                resetCreateFormState();
                createModal.classList.remove('hidden');
                createModal.classList.add('flex');
                createModal.removeAttribute('hidden');
                if (window.AdminUi) {
                    window.AdminUi.lockBody();
                } else {
                    document.body.style.overflow = 'hidden';
                }

                requestAnimationFrame(function () {
                    setTimeout(function () {
                        createImageInput.focus();
                    }, 0);
                });
            }

            function closeCreateModal() {
                if (createSubmitting) {
                    return;
                }

                createModal.classList.add('hidden');
                createModal.classList.remove('flex');
                createModal.setAttribute('hidden', '');
                if (window.AdminUi) {
                    window.AdminUi.unlockBody();
                } else {
                    document.body.style.overflow = '';
                }

                resetCreateFormState();

                const restore = createOpenTrigger;
                createOpenTrigger = null;
                if (restore && typeof restore.focus === 'function') {
                    restore.focus();
                }
            }

            function validateCreateImageFile(file) {
                if (!file) {
                    return '画像は必須です。';
                }
                if (ALLOWED_TYPES.indexOf(file.type) === -1) {
                    return 'JPEG / PNG / WebP形式の画像を選択してください。';
                }
                if (file.size > MAX_IMAGE_BYTES) {
                    return '画像サイズは5MB以下にしてください。';
                }
                return null;
            }

            function compareGalleryCards(a, b) {
                const sortA = Number(a.getAttribute('data-sort-order') || 0);
                const sortB = Number(b.getAttribute('data-sort-order') || 0);
                if (sortA !== sortB) {
                    return sortA - sortB;
                }
                const idA = Number(a.getAttribute('data-gallery-card') || 0);
                const idB = Number(b.getAttribute('data-gallery-card') || 0);
                return idB - idA;
            }

            function insertGalleryCard(card) {
                const empty = document.getElementById('gallery-empty-message');
                if (empty) {
                    empty.remove();
                }

                const cards = Array.from(galleryGrid.querySelectorAll('[data-gallery-card]'));
                let inserted = false;
                for (let i = 0; i < cards.length; i++) {
                    if (compareGalleryCards(card, cards[i]) < 0) {
                        galleryGrid.insertBefore(card, cards[i]);
                        inserted = true;
                        break;
                    }
                }
                if (!inserted) {
                    galleryGrid.appendChild(card);
                }
            }

            function buildGalleryCard(gallery) {
                const node = cardTemplate.content.firstElementChild.cloneNode(true);
                const sortOrder = gallery.sort_order != null ? gallery.sort_order : 0;

                node.setAttribute('data-gallery-card', String(gallery.id));
                node.setAttribute('data-sort-order', String(sortOrder));

                const imageEl = node.querySelector('.gallery-image');
                if (imageEl) {
                    imageEl.src = gallery.image_url || '';
                }

                const captionEl = node.querySelector('.gallery-caption');
                if (captionEl) {
                    captionEl.textContent = gallery.caption || '（キャプションなし）';
                }

                const metaEl = node.querySelector('.gallery-meta');
                if (metaEl) {
                    metaEl.textContent = '表示順: ' + sortOrder + ' / ' + (gallery.is_published ? '公開' : '非公開');
                }

                const editBtn = node.querySelector('[data-open-gallery-edit]');
                if (editBtn) {
                    editBtn.setAttribute('data-gallery-id', String(gallery.id));
                }

                const deleteForm = node.querySelector('form[data-admin-delete-form]');
                if (deleteForm) {
                    deleteForm.action = gallery.destroy_url || '';
                    const tokenInput = deleteForm.querySelector('input[name="_token"]');
                    if (tokenInput) {
                        tokenInput.value = csrfToken;
                    }
                }

                return node;
            }

            document.querySelectorAll('[data-open-gallery-create]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    openCreateModal(btn);
                });
            });

            document.querySelectorAll('[data-close-gallery-create]').forEach(function (btn) {
                btn.addEventListener('click', closeCreateModal);
            });

            createModal.addEventListener('click', function (e) {
                if (e.target === createModal) {
                    closeCreateModal();
                }
            });

            document.addEventListener('keydown', function (e) {
                if (createModal.classList.contains('hidden')) {
                    return;
                }

                if (e.key === 'Escape') {
                    e.preventDefault();
                    closeCreateModal();
                    return;
                }

                if (window.AdminUi) {
                    window.AdminUi.trapFocus(e, createModal);
                }
            });

            createImageInput.addEventListener('change', function () {
                createImageReselectHint.classList.add('hidden');
                const file = createImageInput.files && createImageInput.files[0];

                if (!file) {
                    setCreateImagePreview(null);
                    return;
                }

                const error = validateCreateImageFile(file);
                if (error) {
                    createImageInput.value = '';
                    setCreateImagePreview(null);
                    showCreateFieldError('image', error);
                    if (typeof window.showToast === 'function') {
                        window.showToast(error, 'error');
                    }
                    return;
                }

                clearCreateErrors();
                const reader = new FileReader();
                reader.onload = function (event) {
                    setCreateImagePreview(event.target.result);
                };
                reader.readAsDataURL(file);
            });

            createForm.addEventListener('submit', function (e) {
                e.preventDefault();
                if (createSubmitting) {
                    return;
                }

                clearCreateErrors();

                const file = createImageInput.files && createImageInput.files[0];
                const clientError = validateCreateImageFile(file);
                if (clientError) {
                    showCreateFieldError('image', clientError);
                    if (typeof window.showToast === 'function') {
                        window.showToast(clientError, 'error');
                    }
                    return;
                }

                createSubmitting = true;
                createSubmitBtn.disabled = true;
                createSubmitBtn.textContent = '登録中...';

                const formData = new FormData(createForm);

                fetch(createForm.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: formData,
                    credentials: 'same-origin',
                })
                    .then(async function (response) {
                        const data = await response.json().catch(function () {
                            return {};
                        });

                        if (response.status === 422) {
                            showCreateErrors(data.errors || {});
                            if (typeof window.showToast === 'function') {
                                window.showToast('入力内容を確認してください。', 'error');
                            }
                            return;
                        }

                        if (!response.ok) {
                            showCreateErrors({ caption: [data.message || '登録に失敗しました。'] });
                            if (typeof window.showToast === 'function') {
                                window.showToast(data.message || '登録に失敗しました。', 'error');
                            }
                            return;
                        }

                        const gallery = data.gallery;
                        if (gallery) {
                            galleryData[String(gallery.id)] = {
                                id: gallery.id,
                                caption: gallery.caption,
                                sort_order: gallery.sort_order,
                                is_published: !!gallery.is_published,
                                image_url: gallery.image_url || null,
                                update_url: gallery.update_url,
                            };
                            if (dataEl) {
                                dataEl.textContent = JSON.stringify(galleryData);
                            }

                            const card = buildGalleryCard(gallery);
                            insertGalleryCard(card);
                        }

                        createSubmitting = false;
                        createSubmitBtn.disabled = false;
                        createSubmitBtn.textContent = '登録する';
                        closeCreateModal();

                        if (typeof window.showToast === 'function') {
                            window.showToast(data.message || 'ギャラリーを登録しました。', 'success');
                        }
                    })
                    .catch(function () {
                        showCreateErrors({ caption: ['通信エラーが発生しました。'] });
                        if (typeof window.showToast === 'function') {
                            window.showToast('通信エラーが発生しました。', 'error');
                        }
                    })
                    .finally(function () {
                        createSubmitting = false;
                        createSubmitBtn.disabled = false;
                        createSubmitBtn.textContent = '登録する';
                    });
            });
        })();
    </script>
@endsection
