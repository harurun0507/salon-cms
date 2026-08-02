@extends('layouts.admin')

@section('heading', 'ギャラリー管理')

@section('content')
    @php
        $maxSort = (int) ($galleries->max('sort_order') ?? 0);
    @endphp

    <div class="sticky top-[4.5rem] z-10 -mx-4 -mt-4 mb-6 border-b border-admin-border/50 bg-admin-bg/95 px-4 py-3 shadow-[0_1px_0_rgba(61,56,51,0.03)] backdrop-blur-sm md:-mx-8 md:-mt-8 md:px-8">
        <div class="flex min-w-0 flex-wrap items-center gap-3">
            <button
                type="button"
                class="admin-btn shadow-md shrink-0"
                data-admin-confirm-trigger
                data-confirm-form="galleries-bulk-form"
                data-confirm-title="一括保存の確認"
                data-confirm-message="変更内容を一括保存します。&#10;よろしいですか？"
                data-confirm-note="画像、キャプション、表示順、公開状態、削除など、現在入力されている内容が反映されます。"
                data-confirm-submit-label="一括保存する"
            >一括保存</button>
            <p class="text-sm text-admin-muted">
                カードで編集し、「一括保存」で反映できます
            </p>
        </div>
    </div>

    <div class="mb-6">
        <p class="text-sm text-admin-muted">画像のアップロード・表示順・公開設定</p>
    </div>

    <form
        method="POST"
        action="{{ route('admin.galleries.bulk-update') }}"
        id="galleries-bulk-form"
        enctype="multipart/form-data"
        data-gallery-workspace
        data-next-new-index="1"
        data-max-sort="{{ $maxSort }}"
    >
        @csrf
        @method('PUT')

        <div id="gallery-deleted-ids"></div>

        <div id="gallery-grid" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($galleries as $gallery)
                @php
                    $publishedOld = old('galleries.'.$gallery->id.'.is_published', $gallery->is_published ? '1' : '0');
                    $isPublished = in_array((string) $publishedOld, ['1', 'true', 'on'], true);
                @endphp
                <div
                    class="admin-card"
                    data-gallery-card
                    data-gallery-id="{{ $gallery->id }}"
                    data-gallery-existing
                >
                    <div class="gallery-card-header mb-3 flex items-center justify-between gap-3">
                        <p class="gallery-image-label text-sm font-medium text-gray-800">画像{{ $loop->iteration }}</p>
                        <button
                            type="button"
                            class="admin-icon-btn admin-icon-btn-delete"
                            data-gallery-remove
                            aria-label="削除"
                            title="削除"
                        >
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="mb-3">
                        <div
                            data-gallery-dropzone
                            class="cursor-pointer overflow-hidden rounded-lg"
                        >
                            <div data-gallery-preview>
                                <img
                                    src="{{ asset('storage/'.$gallery->image_path) }}"
                                    alt=""
                                    class="aspect-[4/3] w-full object-cover"
                                    data-gallery-image
                                >
                            </div>
                            <div data-gallery-placeholder class="hidden min-h-[10.5rem] flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 bg-white px-4 text-center transition hover:border-gray-400 hover:bg-gray-50">
                                <p class="text-sm text-gray-700">ここに画像をドラッグ＆ドロップ、またはクリックして選択</p>
                                <p class="mt-2 text-xs text-gray-500">JPEG / PNG / WebP、5MBまで</p>
                            </div>
                        </div>
                        <input
                            type="file"
                            name="galleries[{{ $gallery->id }}][image]"
                            accept="image/jpeg,image/png,image/webp"
                            class="hidden"
                            data-gallery-file
                        >
                        <p class="mt-1 text-xs text-admin-muted">クリックまたは DnD で画像を変更できます</p>
                        <p class="mt-1 hidden text-sm text-red-600" data-gallery-image-error role="alert"></p>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label for="gallery_caption_{{ $gallery->id }}" class="admin-label">キャプション</label>
                            <input
                                type="text"
                                name="galleries[{{ $gallery->id }}][caption]"
                                id="gallery_caption_{{ $gallery->id }}"
                                value="{{ old('galleries.'.$gallery->id.'.caption', $gallery->caption) }}"
                                maxlength="255"
                                class="admin-input"
                            >
                        </div>
                        <div class="gallery-card-meta">
                            <div class="gallery-card-meta-sort">
                                <label for="gallery_sort_{{ $gallery->id }}" class="admin-label">表示順</label>
                                <input
                                    type="number"
                                    name="galleries[{{ $gallery->id }}][sort_order]"
                                    id="gallery_sort_{{ $gallery->id }}"
                                    value="{{ old('galleries.'.$gallery->id.'.sort_order', $gallery->sort_order) }}"
                                    min="0"
                                    class="admin-input gallery-sort-input"
                                    data-gallery-sort
                                >
                            </div>
                            <div class="gallery-card-meta-published">
                                <span class="admin-label">公開</span>
                                <div class="gallery-card-published">
                                    <label class="menu-published-control" data-published-control>
                                        <input type="hidden" name="galleries[{{ $gallery->id }}][is_published]" value="0">
                                        <input
                                            type="checkbox"
                                            name="galleries[{{ $gallery->id }}][is_published]"
                                            value="1"
                                            class="menu-published-checkbox"
                                            data-published-checkbox
                                            @checked($isPublished)
                                            aria-label="公開状態"
                                        >
                                        <span
                                            class="menu-published-label {{ $isPublished ? 'is-published' : 'is-unpublished' }}"
                                            data-published-label
                                        >
                                            <span class="menu-published-dot" data-published-dot aria-hidden="true"></span>
                                            <span data-published-text>{{ $isPublished ? '公開' : '非公開' }}</span>
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            <div
                id="gallery-add-card"
                class="admin-card flex min-h-[22rem] w-full flex-col items-center justify-center px-6 py-10 text-center"
            >
                <div class="admin-empty-state-icon !mb-4" aria-hidden="true">
                    <svg class="h-14 w-14" viewBox="0 0 80 80" fill="none" stroke="#B8B09F" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 52c-4-8-6-16-4-24 3-10 12-16 20-14" stroke-width="1.3" opacity="0.7"/>
                        <path d="M18 34c-2 4-2 9 1 13" stroke-width="1.2" opacity="0.55"/>
                        <path d="M22 28c4-1 8 1 10 5" stroke-width="1.2" opacity="0.55"/>
                        <path d="M66 18c6 8 8 18 4 28-4 10-14 16-22 14" stroke-width="1.3" opacity="0.7"/>
                        <path d="M58 28c3 3 4 8 2 12" stroke-width="1.2" opacity="0.55"/>
                        <path d="M62 36c-4 2-7 6-8 10" stroke-width="1.2" opacity="0.55"/>
                        <rect x="22" y="22" width="36" height="36" rx="3.5" stroke-width="1.4"/>
                        <circle cx="32" cy="33" r="3.2" stroke-width="1.3"/>
                        <path d="M26 50l9-9a4 4 0 0 1 5.5 0l13.5 13.5" stroke-width="1.3"/>
                        <path d="M44 44l3.5-3.5a3.5 3.5 0 0 1 5 0L58 46" stroke-width="1.3"/>
                    </svg>
                </div>
                <x-admin.create-button data-gallery-add>
                    画像を追加
                </x-admin.create-button>
                <p class="mt-3 text-xs text-admin-muted">カードを追加し、一括保存で登録できます。</p>
            </div>
        </div>
    </form>

    <script>
        (function () {
            const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            const maxSize = 5 * 1024 * 1024;
            const form = document.getElementById('galleries-bulk-form');
            const grid = document.getElementById('gallery-grid');
            const deletedIdsWrap = document.getElementById('gallery-deleted-ids');
            const addCard = document.getElementById('gallery-add-card');
            const addButton = addCard ? addCard.querySelector('[data-gallery-add]') : null;
            if (!form || !grid || !addCard || !addButton) {
                return;
            }

            let nextNewIndex = parseInt(form.getAttribute('data-next-new-index') || '1', 10);
            let maxSort = parseInt(form.getAttribute('data-max-sort') || '0', 10);

            function syncPublishedLabel(checkbox) {
                const control = checkbox.closest('[data-published-control]');
                if (!control) {
                    return;
                }
                const label = control.querySelector('[data-published-label]');
                const text = control.querySelector('[data-published-text]');
                if (!label) {
                    return;
                }
                const published = !!checkbox.checked;
                label.classList.toggle('is-published', published);
                label.classList.toggle('is-unpublished', !published);
                if (text) {
                    text.textContent = published ? '公開' : '非公開';
                }
            }

            document.addEventListener('change', function (e) {
                const checkbox = e.target.closest('[data-published-checkbox]');
                if (checkbox) {
                    syncPublishedLabel(checkbox);
                }
            });

            function showCardImageError(card, message) {
                const errorEl = card.querySelector('[data-gallery-image-error]');
                if (errorEl) {
                    errorEl.textContent = message || '';
                    errorEl.classList.toggle('hidden', !message);
                }
                if (message && typeof window.showToast === 'function') {
                    window.showToast(message, 'error');
                }
            }

            function isValidImage(file) {
                if (!file) {
                    return '画像を選択してください。';
                }
                if (allowedTypes.indexOf(file.type) === -1) {
                    return 'JPEG / PNG / WebP形式の画像を選択してください。';
                }
                if (file.size > maxSize) {
                    return '画像サイズは5MB以下にしてください。';
                }
                return null;
            }

            function setCardPreview(card, fileOrUrl) {
                const preview = card.querySelector('[data-gallery-preview]');
                const placeholder = card.querySelector('[data-gallery-placeholder]');
                const dropzone = card.querySelector('[data-gallery-dropzone]');
                if (!preview || !placeholder) {
                    return;
                }

                function showImage(src) {
                    let img = preview.querySelector('[data-gallery-image]');
                    if (!img) {
                        img = document.createElement('img');
                        img.setAttribute('data-gallery-image', '');
                        img.alt = '';
                        img.className = 'aspect-[4/3] w-full object-cover';
                        preview.appendChild(img);
                    }
                    img.src = src;
                    placeholder.classList.add('hidden');
                    preview.classList.remove('hidden');
                    if (dropzone) {
                        dropzone.classList.remove(
                            'rounded-lg', 'border-2', 'border-dashed', 'border-gray-300', 'bg-white',
                            'px-4', 'min-h-[10.5rem]', 'flex', 'flex-col', 'items-center', 'justify-center',
                            'text-center', 'transition', 'hover:border-gray-400', 'hover:bg-gray-50'
                        );
                        dropzone.classList.add('overflow-hidden', 'rounded-lg');
                    }
                }

                if (typeof fileOrUrl === 'string') {
                    showImage(fileOrUrl);
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (e) {
                    showImage(e.target.result);
                };
                reader.readAsDataURL(fileOrUrl);
            }

            function applyFile(card, file) {
                const input = card.querySelector('[data-gallery-file]');
                const error = isValidImage(file);
                if (error) {
                    if (input) {
                        input.value = '';
                    }
                    showCardImageError(card, error);
                    return;
                }
                showCardImageError(card, '');
                if (input) {
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    input.files = dt.files;
                }
                setCardPreview(card, file);
            }

            function nextSortOrder() {
                let max = maxSort;
                grid.querySelectorAll('[data-gallery-sort]').forEach(function (input) {
                    const value = parseInt(input.value, 10);
                    if (!Number.isNaN(value)) {
                        max = Math.max(max, value);
                    }
                });
                return max + 1;
            }

            function renumberGalleryTitles() {
                grid.querySelectorAll('[data-gallery-card]').forEach(function (card, index) {
                    const label = card.querySelector('.gallery-image-label');
                    if (label) {
                        label.textContent = '画像' + (index + 1);
                    }
                });
            }

            function bindCard(card) {
                const dropzone = card.querySelector('[data-gallery-dropzone]');
                const input = card.querySelector('[data-gallery-file]');
                const removeBtn = card.querySelector('[data-gallery-remove]');

                dropzone?.addEventListener('click', function () {
                    input?.click();
                });
                input?.addEventListener('change', function () {
                    applyFile(card, input.files && input.files[0]);
                });
                ['dragenter', 'dragover'].forEach(function (eventName) {
                    dropzone?.addEventListener(eventName, function (e) {
                        e.preventDefault();
                        dropzone.classList.add('border-gray-400', 'bg-gray-50');
                    });
                });
                ['dragleave', 'drop'].forEach(function (eventName) {
                    dropzone?.addEventListener(eventName, function (e) {
                        e.preventDefault();
                        dropzone.classList.remove('border-gray-400', 'bg-gray-50');
                    });
                });
                dropzone?.addEventListener('drop', function (e) {
                    applyFile(card, e.dataTransfer.files[0]);
                });
                removeBtn?.addEventListener('click', function () {
                    const existingId = card.getAttribute('data-gallery-id');
                    if (existingId && deletedIdsWrap) {
                        const hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = 'deleted_ids[]';
                        hidden.value = existingId;
                        deletedIdsWrap.appendChild(hidden);
                    }
                    card.remove();
                    renumberGalleryTitles();
                });
            }

            function createEmptyCard() {
                const key = 'new_' + nextNewIndex;
                nextNewIndex += 1;
                form.setAttribute('data-next-new-index', String(nextNewIndex));

                const sortOrder = nextSortOrder();
                maxSort = Math.max(maxSort, sortOrder);
                form.setAttribute('data-max-sort', String(maxSort));

                const card = document.createElement('div');
                card.className = 'admin-card';
                card.setAttribute('data-gallery-card', '');
                card.setAttribute('data-gallery-new', '1');
                card.innerHTML =
                    '<div class="gallery-card-header mb-3 flex items-center justify-between gap-3">' +
                        '<p class="gallery-image-label text-sm font-medium text-gray-800">画像</p>' +
                        '<button type="button" class="admin-icon-btn admin-icon-btn-delete" data-gallery-remove aria-label="削除" title="削除">' +
                            '<span aria-hidden="true">&times;</span>' +
                        '</button>' +
                    '</div>' +
                    '<div class="mb-3">' +
                        '<div data-gallery-dropzone class="flex min-h-[10.5rem] cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 bg-white px-4 text-center transition hover:border-gray-400 hover:bg-gray-50">' +
                            '<div data-gallery-placeholder>' +
                                '<p class="text-sm text-gray-700">ここに画像をドラッグ＆ドロップ、またはクリックして選択</p>' +
                                '<p class="mt-2 text-xs text-gray-500">JPEG / PNG / WebP、5MBまで</p>' +
                            '</div>' +
                            '<div data-gallery-preview class="hidden"></div>' +
                        '</div>' +
                        '<input type="file" name="new_galleries[' + key + '][image]" accept="image/jpeg,image/png,image/webp" class="hidden" data-gallery-file>' +
                        '<p class="mt-1 hidden text-sm text-red-600" data-gallery-image-error role="alert"></p>' +
                    '</div>' +
                    '<div class="space-y-3">' +
                        '<div>' +
                            '<label for="gallery_new_caption_' + key + '" class="admin-label">キャプション</label>' +
                            '<input type="text" name="new_galleries[' + key + '][caption]" id="gallery_new_caption_' + key + '" value="" maxlength="255" class="admin-input">' +
                        '</div>' +
                        '<div class="gallery-card-meta">' +
                            '<div class="gallery-card-meta-sort">' +
                                '<label for="gallery_new_sort_' + key + '" class="admin-label">表示順</label>' +
                                '<input type="number" name="new_galleries[' + key + '][sort_order]" id="gallery_new_sort_' + key + '" value="' + sortOrder + '" min="0" class="admin-input gallery-sort-input" data-gallery-sort>' +
                            '</div>' +
                            '<div class="gallery-card-meta-published">' +
                                '<span class="admin-label">公開</span>' +
                                '<div class="gallery-card-published">' +
                                    '<label class="menu-published-control" data-published-control>' +
                                        '<input type="hidden" name="new_galleries[' + key + '][is_published]" value="0">' +
                                        '<input type="checkbox" name="new_galleries[' + key + '][is_published]" value="1" class="menu-published-checkbox" data-published-checkbox checked aria-label="公開状態">' +
                                        '<span class="menu-published-label is-published" data-published-label>' +
                                            '<span class="menu-published-dot" data-published-dot aria-hidden="true"></span>' +
                                            '<span data-published-text>公開</span>' +
                                        '</span>' +
                                    '</label>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>';

                addCard.before(card);
                bindCard(card);
                renumberGalleryTitles();
            }

            grid.querySelectorAll('[data-gallery-card]').forEach(bindCard);
            renumberGalleryTitles();

            addButton.addEventListener('click', function (e) {
                e.preventDefault();
                createEmptyCard();
            });
        })();
    </script>
@endsection
