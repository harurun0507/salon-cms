@extends('layouts.admin')

@section('heading', 'ギャラリー管理')

@section('content')
    @php
        $maxOrder = (int) ($galleries->max('sort_order') ?? 0);
    @endphp

    <div class="sticky top-[4.5rem] z-10 -mx-4 -mt-4 mb-6 border-b border-admin-border/50 bg-admin-bg/95 px-4 py-3 shadow-[0_1px_0_rgba(61,56,51,0.03)] backdrop-blur-sm md:-mx-8 md:-mt-8 md:px-8">
        <div class="flex min-w-0 flex-wrap items-center gap-3">
            <button
                type="button"
                class="admin-btn shadow-md shrink-0"
                data-admin-confirm-trigger
                data-confirm-form="galleries-bulk-form"
                data-confirm-title="ギャラリー保存の確認"
                data-confirm-message="変更内容を保存します。&#10;よろしいですか？"
                data-confirm-note="画像、キャプション、表示順、公開状態、削除など、現在入力されている内容が反映されます。"
                data-confirm-submit-label="保存する"
            >保存する</button>
            <p class="text-sm text-admin-muted">
                公開サイトに表示するギャラリー画像を登録・編集します。
            </p>
        </div>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form
        method="POST"
        action="{{ route('admin.galleries.bulk-update') }}"
        id="galleries-bulk-form"
        enctype="multipart/form-data"
        data-gallery-workspace
        data-next-new-index="1"
        data-max-order="{{ $maxOrder }}"
    >
        @csrf
        @method('PUT')

        <div id="gallery-deleted-ids"></div>

        <div id="gallery-grid" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3" data-gallery-grid>
            @foreach($galleries as $gallery)
                @php
                    $prefix = 'galleries.'.$gallery->id;
                    $publishedOld = old($prefix.'.is_published', $gallery->is_published ? '1' : '0');
                    $isPublished = in_array((string) $publishedOld, ['1', 'true', 'on'], true);
                    $sortOrder = old($prefix.'.sort_order', $gallery->sort_order);
                    $cardCaption = trim((string) old($prefix.'.caption', $gallery->caption));
                    $headingTitle = $cardCaption !== '' ? $cardCaption : '新規ギャラリー';
                @endphp
                <div
                    class="admin-card gallery-card"
                    data-gallery-card
                    data-gallery-id="{{ $gallery->id }}"
                    data-gallery-existing
                >
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-2">
                            <span
                                class="gallery-drag-handle"
                                data-gallery-drag-handle
                                draggable="true"
                                role="button"
                                tabindex="0"
                                aria-label="ギャラリーを並び替え"
                                title="ドラッグして並び替え"
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
                            <p class="banner-card-label truncate text-sm font-medium text-gray-800" data-gallery-card-title title="{{ $headingTitle }}">{{ $headingTitle }}</p>
                        </div>
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

                    <input type="hidden" name="galleries[{{ $gallery->id }}][sort_order]" value="{{ $sortOrder }}" data-gallery-order>

                    <div class="mb-3">
                        <div data-gallery-dropzone class="banner-dropzone cursor-pointer overflow-hidden rounded-lg">
                            <div data-gallery-preview>
                                <img
                                    src="{{ asset('storage/'.$gallery->image_path) }}"
                                    alt=""
                                    class="aspect-[4/3] w-full object-cover"
                                    data-gallery-image
                                >
                            </div>
                            <div data-gallery-placeholder class="banner-dropzone-placeholder hidden min-h-[7.5rem] flex-col items-center justify-center px-4 text-center">
                                <div class="banner-dropzone-main">
                                    <svg class="banner-dropzone-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <rect x="3.5" y="5.5" width="17" height="13" rx="2" stroke="currentColor" stroke-width="1.5"/>
                                        <circle cx="9" cy="10.5" r="1.5" fill="currentColor" opacity="0.7"/>
                                        <path d="M5.5 16.5l4-3.5 2.5 2 3.5-3.5 3 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <p class="banner-dropzone-text text-sm text-gray-700">画像をドラッグ＆ドロップ、またはクリックして選択</p>
                                </div>
                                <p class="banner-dropzone-hint mt-2 text-xs text-gray-500">JPEG / PNG / WebP、5MBまで</p>
                            </div>
                            <p class="banner-dropzone-drag-message" aria-hidden="true">ここに画像をドロップしてください</p>
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
                        @error($prefix.'.image')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label for="gallery_caption_{{ $gallery->id }}" class="admin-label">キャプション</label>
                            <input
                                type="text"
                                name="galleries[{{ $gallery->id }}][caption]"
                                id="gallery_caption_{{ $gallery->id }}"
                                value="{{ old($prefix.'.caption', $gallery->caption) }}"
                                maxlength="255"
                                class="admin-input"
                                data-gallery-caption-input
                            >
                        </div>
                        <div>
                            <span class="admin-label">公開</span>
                            <div class="admin-segmented mt-1" role="radiogroup" aria-label="公開状態">
                                <label class="admin-segmented-option">
                                    <input type="radio" name="galleries[{{ $gallery->id }}][is_published]" value="1" class="admin-segmented-input" @checked($isPublished)>
                                    <span class="admin-segmented-face">
                                        <svg class="admin-segmented-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                            <path d="M1.5 8s2.5-4.5 6.5-4.5S14.5 8 14.5 8s-2.5 4.5-6.5 4.5S1.5 8 1.5 8z" stroke="currentColor" stroke-width="1.35" stroke-linejoin="round"/>
                                            <circle cx="8" cy="8" r="2" stroke="currentColor" stroke-width="1.35"/>
                                        </svg>
                                        <span class="admin-segmented-text">公開</span>
                                    </span>
                                </label>
                                <label class="admin-segmented-option">
                                    <input type="radio" name="galleries[{{ $gallery->id }}][is_published]" value="0" class="admin-segmented-input" @checked(!$isPublished)>
                                    <span class="admin-segmented-face">
                                        <svg class="admin-segmented-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                            <path d="M2 2.5 13.5 13.5" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/>
                                            <path d="M6.7 4.1A6.4 6.4 0 0 1 8 3.5c4 0 6.5 4.5 6.5 4.5a10.3 10.3 0 0 1-2.15 2.55M4.2 5.85A10.2 10.2 0 0 0 1.5 8S4 12.5 8 12.5c.7 0 1.35-.12 1.95-.34" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M6.65 7.1a2 2 0 0 0 2.35 2.35" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/>
                                        </svg>
                                        <span class="admin-segmented-text">非公開</span>
                                    </span>
                                </label>
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
                <p class="mt-3 text-xs text-admin-muted">カードを追加し、保存で登録できます。</p>
            </div>
        </div>
    </form>

    <style>
        .gallery-drag-handle {
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
        .gallery-drag-handle:hover,
        .gallery-drag-handle:focus-visible {
            color: #556344;
        }
        .gallery-drag-handle:focus {
            outline: none;
        }
        .gallery-drag-handle:focus-visible {
            box-shadow: inset 0 0 0 2px rgba(105, 122, 85, 0.35);
            border-radius: 0.25rem;
        }
        .gallery-drag-handle:active,
        .gallery-card.is-dragging .gallery-drag-handle {
            cursor: grabbing;
        }
        .gallery-card.is-dragging {
            opacity: 0.55;
        }
        .gallery-card.is-drag-over {
            outline: 2px dashed rgba(105, 122, 85, 0.45);
            outline-offset: 2px;
        }
    </style>

    <script>
        (function () {
            const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            const maxSize = 5 * 1024 * 1024;
            const form = document.getElementById('galleries-bulk-form');
            const grid = document.getElementById('gallery-grid');
            const deletedIdsWrap = document.getElementById('gallery-deleted-ids');
            const addCard = document.getElementById('gallery-add-card');
            const addButton = addCard ? addCard.querySelector('[data-gallery-add]') : null;
            const emptyHeading = '新規ギャラリー';
            const dropzoneMainHtml =
                '<div class="banner-dropzone-main">' +
                    '<svg class="banner-dropzone-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">' +
                        '<rect x="3.5" y="5.5" width="17" height="13" rx="2" stroke="currentColor" stroke-width="1.5"/>' +
                        '<circle cx="9" cy="10.5" r="1.5" fill="currentColor" opacity="0.7"/>' +
                        '<path d="M5.5 16.5l4-3.5 2.5 2 3.5-3.5 3 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>' +
                    '</svg>' +
                    '<p class="banner-dropzone-text text-sm text-gray-700">画像をドラッグ＆ドロップ、またはクリックして選択</p>' +
                '</div>';

            if (!form || !grid || !addCard || !addButton) {
                return;
            }

            let nextNewIndex = parseInt(form.getAttribute('data-next-new-index') || '1', 10);
            let maxOrder = parseInt(form.getAttribute('data-max-order') || '0', 10);
            let dragCard = null;

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
                        dropzone.classList.remove('is-empty');
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

            function syncCardHeading(card) {
                const label = card.querySelector('[data-gallery-card-title]');
                const input = card.querySelector('[data-gallery-caption-input]');
                if (!label || !input) {
                    return;
                }
                const value = (input.value || '').trim();
                const text = value !== '' ? value : emptyHeading;
                label.textContent = text;
                label.setAttribute('title', text);
            }

            function syncDisplayOrders() {
                grid.querySelectorAll('[data-gallery-card]').forEach(function (card, index) {
                    const orderInput = card.querySelector('[data-gallery-order]');
                    if (orderInput) {
                        orderInput.value = String(index + 1);
                    }
                });
                maxOrder = grid.querySelectorAll('[data-gallery-card]').length;
                form.setAttribute('data-max-order', String(maxOrder));
            }

            function clearDropzoneDragState(dropzone) {
                if (!dropzone) {
                    return;
                }
                dropzone._galleryDragCounter = 0;
                dropzone.classList.remove('is-drag-active');
            }

            function bindCard(card) {
                const dropzone = card.querySelector('[data-gallery-dropzone]');
                const input = card.querySelector('[data-gallery-file]');
                const removeBtn = card.querySelector('[data-gallery-remove]');
                const captionInput = card.querySelector('[data-gallery-caption-input]');

                if (dropzone) {
                    dropzone._galleryDragCounter = 0;
                }

                dropzone?.addEventListener('click', function () {
                    input?.click();
                });
                input?.addEventListener('change', function () {
                    applyFile(card, input.files && input.files[0]);
                });

                dropzone?.addEventListener('dragenter', function (e) {
                    if (dragCard) {
                        return;
                    }
                    e.preventDefault();
                    dropzone._galleryDragCounter = (dropzone._galleryDragCounter || 0) + 1;
                    dropzone.classList.add('is-drag-active');
                });
                dropzone?.addEventListener('dragover', function (e) {
                    if (dragCard) {
                        return;
                    }
                    e.preventDefault();
                    dropzone.classList.add('is-drag-active');
                });
                dropzone?.addEventListener('dragleave', function (e) {
                    if (dragCard) {
                        return;
                    }
                    e.preventDefault();
                    dropzone._galleryDragCounter = Math.max(0, (dropzone._galleryDragCounter || 0) - 1);
                    if (dropzone._galleryDragCounter === 0) {
                        dropzone.classList.remove('is-drag-active');
                    }
                });
                dropzone?.addEventListener('drop', function (e) {
                    if (dragCard) {
                        return;
                    }
                    e.preventDefault();
                    clearDropzoneDragState(dropzone);
                    applyFile(card, e.dataTransfer.files[0]);
                });

                captionInput?.addEventListener('input', function () {
                    syncCardHeading(card);
                });
                syncCardHeading(card);

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
                    syncDisplayOrders();
                });
            }

            function createEmptyCard() {
                const key = 'new_' + nextNewIndex;
                nextNewIndex += 1;
                form.setAttribute('data-next-new-index', String(nextNewIndex));

                const order = grid.querySelectorAll('[data-gallery-card]').length + 1;
                maxOrder = Math.max(maxOrder, order);
                form.setAttribute('data-max-order', String(maxOrder));

                const card = document.createElement('div');
                card.className = 'admin-card gallery-card';
                card.setAttribute('data-gallery-card', '');
                card.setAttribute('data-gallery-new', '1');
                card.innerHTML =
                    '<div class="mb-3 flex items-center justify-between gap-3">' +
                        '<div class="flex min-w-0 items-center gap-2">' +
                            '<span class="gallery-drag-handle" data-gallery-drag-handle draggable="true" role="button" tabindex="0" aria-label="ギャラリーを並び替え" title="ドラッグして並び替え">' +
                                '<svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">' +
                                    '<circle cx="7" cy="5" r="1.25"/><circle cx="13" cy="5" r="1.25"/>' +
                                    '<circle cx="7" cy="10" r="1.25"/><circle cx="13" cy="10" r="1.25"/>' +
                                    '<circle cx="7" cy="15" r="1.25"/><circle cx="13" cy="15" r="1.25"/>' +
                                '</svg>' +
                            '</span>' +
                            '<p class="banner-card-label truncate text-sm font-medium text-gray-800" data-gallery-card-title title="' + emptyHeading + '">' + emptyHeading + '</p>' +
                        '</div>' +
                        '<button type="button" class="admin-icon-btn admin-icon-btn-delete" data-gallery-remove aria-label="削除" title="削除">' +
                            '<span aria-hidden="true">&times;</span>' +
                        '</button>' +
                    '</div>' +
                    '<input type="hidden" name="new_galleries[' + key + '][sort_order]" value="' + order + '" data-gallery-order>' +
                    '<div class="mb-3">' +
                        '<div data-gallery-dropzone class="banner-dropzone is-empty cursor-pointer">' +
                            '<div data-gallery-placeholder class="banner-dropzone-placeholder">' +
                                dropzoneMainHtml +
                                '<p class="banner-dropzone-hint mt-2 text-xs text-gray-500">JPEG / PNG / WebP、5MBまで</p>' +
                            '</div>' +
                            '<div data-gallery-preview class="hidden"></div>' +
                            '<p class="banner-dropzone-drag-message" aria-hidden="true">ここに画像をドロップしてください</p>' +
                        '</div>' +
                        '<input type="file" name="new_galleries[' + key + '][image]" accept="image/jpeg,image/png,image/webp" class="hidden" data-gallery-file>' +
                        '<p class="mt-1 hidden text-sm text-red-600" data-gallery-image-error role="alert"></p>' +
                    '</div>' +
                    '<div class="space-y-3">' +
                        '<div>' +
                            '<label for="gallery_new_caption_' + key + '" class="admin-label">キャプション</label>' +
                            '<input type="text" name="new_galleries[' + key + '][caption]" id="gallery_new_caption_' + key + '" value="" maxlength="255" class="admin-input" data-gallery-caption-input>' +
                        '</div>' +
                        '<div>' +
                            '<span class="admin-label">公開</span>' +
                            '<div class="admin-segmented mt-1" role="radiogroup" aria-label="公開状態">' +
                                '<label class="admin-segmented-option">' +
                                    '<input type="radio" name="new_galleries[' + key + '][is_published]" value="1" class="admin-segmented-input" checked>' +
                                    '<span class="admin-segmented-face">' +
                                        '<svg class="admin-segmented-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">' +
                                            '<path d="M1.5 8s2.5-4.5 6.5-4.5S14.5 8 14.5 8s-2.5 4.5-6.5 4.5S1.5 8 1.5 8z" stroke="currentColor" stroke-width="1.35" stroke-linejoin="round"/>' +
                                            '<circle cx="8" cy="8" r="2" stroke="currentColor" stroke-width="1.35"/>' +
                                        '</svg>' +
                                        '<span class="admin-segmented-text">公開</span>' +
                                    '</span>' +
                                '</label>' +
                                '<label class="admin-segmented-option">' +
                                    '<input type="radio" name="new_galleries[' + key + '][is_published]" value="0" class="admin-segmented-input">' +
                                    '<span class="admin-segmented-face">' +
                                        '<svg class="admin-segmented-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">' +
                                            '<path d="M2 2.5 13.5 13.5" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/>' +
                                            '<path d="M6.7 4.1A6.4 6.4 0 0 1 8 3.5c4 0 6.5 4.5 6.5 4.5a10.3 10.3 0 0 1-2.15 2.55M4.2 5.85A10.2 10.2 0 0 0 1.5 8S4 12.5 8 12.5c.7 0 1.35-.12 1.95-.34" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round"/>' +
                                            '<path d="M6.65 7.1a2 2 0 0 0 2.35 2.35" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/>' +
                                        '</svg>' +
                                        '<span class="admin-segmented-text">非公開</span>' +
                                    '</span>' +
                                '</label>' +
                            '</div>' +
                        '</div>' +
                    '</div>';

                addCard.before(card);
                bindCard(card);
                syncDisplayOrders();
            }

            grid.addEventListener('dragstart', function (e) {
                const handle = e.target.closest('[data-gallery-drag-handle]');
                if (!handle || !grid.contains(handle)) {
                    return;
                }
                const card = handle.closest('[data-gallery-card]');
                if (!card) {
                    e.preventDefault();
                    return;
                }
                dragCard = card;
                card.classList.add('is-dragging');
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', card.getAttribute('data-gallery-id') || 'new');
            });

            grid.addEventListener('dragend', function () {
                if (dragCard) {
                    dragCard.classList.remove('is-dragging');
                }
                grid.querySelectorAll('.is-drag-over').forEach(function (el) {
                    el.classList.remove('is-drag-over');
                });
                grid.querySelectorAll('[data-gallery-dropzone]').forEach(clearDropzoneDragState);
                dragCard = null;
                syncDisplayOrders();
            });

            grid.addEventListener('dragover', function (e) {
                if (!dragCard) {
                    return;
                }
                e.preventDefault();
                const over = e.target.closest('[data-gallery-card]');
                if (!over || over === dragCard || !grid.contains(over)) {
                    return;
                }
                grid.querySelectorAll('.is-drag-over').forEach(function (el) {
                    if (el !== over) {
                        el.classList.remove('is-drag-over');
                    }
                });
                over.classList.add('is-drag-over');
                const rect = over.getBoundingClientRect();
                const before = (e.clientY - rect.top) < rect.height / 2;
                if (before) {
                    over.before(dragCard);
                } else {
                    over.after(dragCard);
                }
            });

            grid.addEventListener('drop', function (e) {
                if (!dragCard) {
                    return;
                }
                e.preventDefault();
            });

            grid.querySelectorAll('[data-gallery-card]').forEach(bindCard);
            syncDisplayOrders();

            addButton.addEventListener('click', function (e) {
                e.preventDefault();
                createEmptyCard();
            });
        })();
    </script>
@endsection
