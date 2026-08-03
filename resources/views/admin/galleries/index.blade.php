@extends('layouts.admin')

@section('heading', 'ギャラリー管理')

@section('save-bar')
    <div class="flex min-w-0 flex-wrap items-center gap-3">
        <button
            type="button"
            class="admin-btn shadow-md shrink-0"
            data-admin-confirm-trigger
            data-confirm-form="galleries-bulk-form"
            data-confirm-title="ギャラリー保存の確認"
            data-confirm-message="変更内容を保存します。&#10;よろしいですか？"
            data-confirm-note="画像、タイトル、詳細、担当スタッフ、表示順、公開状態、削除など、現在入力されている内容が反映されます。"
            data-confirm-submit-label="保存する"
        >保存する</button>
        <p class="text-sm text-admin-muted">
            公開サイトに表示するギャラリーを登録・編集します。1件あたり最大{{ \App\Models\Gallery::MAX_IMAGES }}枚まで画像を追加できます。
        </p>
    </div>

@endsection

@section('content')
    @php
        $maxOrder = (int) ($galleries->max('sort_order') ?? 0);
        $maxImages = \App\Models\Gallery::MAX_IMAGES;
    @endphp

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
        data-max-images="{{ $maxImages }}"
    >
        @csrf
        @method('PUT')

        <div id="gallery-deleted-ids"></div>

        <div id="gallery-grid" class="grid grid-cols-1 gap-4 lg:grid-cols-2" data-gallery-grid>
            @foreach($galleries as $gallery)
                @php
                    $prefix = 'galleries.'.$gallery->id;
                    $publishedOld = old($prefix.'.is_published', $gallery->is_published ? '1' : '0');
                    $isPublished = in_array((string) $publishedOld, ['1', 'true', 'on'], true);
                    $sortOrder = old($prefix.'.sort_order', $gallery->sort_order);
                    $cardTitle = trim((string) old($prefix.'.title', $gallery->title));
                    $headingTitle = $cardTitle !== '' ? $cardTitle : '新規ギャラリー';
                    $staffId = old($prefix.'.staff_id', $gallery->staff_id);
                @endphp
                <div
                    class="admin-card gallery-card"
                    data-gallery-card
                    data-gallery-id="{{ $gallery->id }}"
                    data-gallery-existing
                    data-gallery-name-prefix="galleries[{{ $gallery->id }}]"
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
                    <div data-gallery-deleted-images></div>

                    <div class="mb-3">
                        <p class="admin-label">ギャラリー画像</p>
                        <div class="gallery-image-grid" data-gallery-image-grid>
                            @foreach($gallery->images as $image)
                                <div
                                    class="gallery-image-item"
                                    data-gallery-image-item
                                    data-gallery-image-id="{{ $image->id }}"
                                    data-gallery-image-existing
                                >
                                    <span
                                        class="gallery-image-drag-handle"
                                        data-gallery-image-drag-handle
                                        draggable="true"
                                        role="button"
                                        tabindex="0"
                                        aria-label="画像を並び替え"
                                        title="ドラッグして並び替え"
                                    >
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <circle cx="7" cy="5" r="1.25"/><circle cx="13" cy="5" r="1.25"/>
                                            <circle cx="7" cy="10" r="1.25"/><circle cx="13" cy="10" r="1.25"/>
                                            <circle cx="7" cy="15" r="1.25"/><circle cx="13" cy="15" r="1.25"/>
                                        </svg>
                                    </span>
                                    <button
                                        type="button"
                                        class="gallery-image-remove"
                                        data-gallery-image-remove
                                        aria-label="画像を削除"
                                        title="画像を削除"
                                    >&times;</button>
                                    <div class="gallery-image-thumb" data-gallery-image-dropzone>
                                        <img src="{{ asset('storage/'.$image->image_path) }}" alt="" class="h-full w-full object-cover" data-gallery-image-preview>
                                    </div>
                                    <input type="hidden" name="galleries[{{ $gallery->id }}][images][{{ $image->id }}][display_order]" value="{{ $image->display_order }}" data-gallery-image-order>
                                    <input type="hidden" name="galleries[{{ $gallery->id }}][images][{{ $image->id }}][alt_text]" value="{{ $image->alt_text }}" data-gallery-image-alt>
                                    <input type="file" name="galleries[{{ $gallery->id }}][images][{{ $image->id }}][image]" accept="image/jpeg,image/png,image/webp" class="hidden" data-gallery-image-file>
                                    <p class="mt-1 text-center text-[10px] text-admin-muted" data-gallery-image-label>画像{{ $loop->iteration }}</p>
                                </div>
                            @endforeach

                            <button
                                type="button"
                                class="gallery-image-add"
                                data-gallery-image-add
                                @if($gallery->images->count() >= $maxImages) hidden @endif
                            >
                                <svg class="mx-auto h-8 w-8 text-[#B8B09F]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <rect x="3.5" y="5.5" width="17" height="13" rx="2" stroke="currentColor" stroke-width="1.5"/>
                                    <circle cx="9" cy="10.5" r="1.5" fill="currentColor" opacity="0.7"/>
                                    <path d="M5.5 16.5l4-3.5 2.5 2 3.5-3.5 3 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <span class="mt-1 block text-xs font-medium text-admin-accent">＋画像を追加</span>
                            </button>
                        </div>
                        <p class="mt-1 hidden text-sm text-red-600" data-gallery-image-error role="alert"></p>
                        @error($prefix.'.images')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        @error($prefix.'.new_images')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label for="gallery_title_{{ $gallery->id }}" class="admin-label">タイトル</label>
                            <input
                                type="text"
                                name="galleries[{{ $gallery->id }}][title]"
                                id="gallery_title_{{ $gallery->id }}"
                                value="{{ old($prefix.'.title', $gallery->title) }}"
                                maxlength="255"
                                class="admin-input"
                                placeholder="例：ショートボブ"
                                data-gallery-title-input
                            >
                        </div>
                        <div>
                            <label for="gallery_caption_{{ $gallery->id }}" class="admin-label">詳細</label>
                            <textarea
                                name="galleries[{{ $gallery->id }}][caption]"
                                id="gallery_caption_{{ $gallery->id }}"
                                rows="4"
                                maxlength="2000"
                                class="admin-input min-h-[7rem] resize-y"
                                placeholder="スタイルの特徴やポイントを入力してください"
                                data-gallery-caption-input
                            >{{ old($prefix.'.caption', $gallery->caption) }}</textarea>
                        </div>
                        <div>
                            <label for="gallery_staff_{{ $gallery->id }}" class="admin-label">担当スタッフ</label>
                            <select
                                name="galleries[{{ $gallery->id }}][staff_id]"
                                id="gallery_staff_{{ $gallery->id }}"
                                class="admin-input"
                                data-gallery-staff-input
                            >
                                <option value="">未設定</option>
                                @foreach($staffMembers as $member)
                                    <option value="{{ $member->id }}" @selected((string) $staffId === (string) $member->id)>{{ $member->name }}</option>
                                @endforeach
                            </select>
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
                    ギャラリーを追加
                </x-admin.create-button>
                <p class="mt-3 text-xs text-admin-muted">カードを追加し、保存で登録できます。</p>
            </div>
        </div>
    </form>

    <template id="gallery-staff-options-template">
        <option value="">未設定</option>
        @foreach($staffMembers as $member)
            <option value="{{ $member->id }}">{{ $member->name }}</option>
        @endforeach
    </template>

    <style>
        .gallery-drag-handle,
        .gallery-image-drag-handle {
            display: inline-flex;
            flex-shrink: 0;
            align-items: center;
            justify-content: center;
            color: rgba(115, 109, 101, 0.55);
            cursor: grab;
            touch-action: none;
            user-select: none;
            -webkit-user-select: none;
        }
        .gallery-drag-handle {
            width: 1.75rem;
            height: 2rem;
        }
        .gallery-image-drag-handle {
            position: absolute;
            top: 0.25rem;
            left: 0.25rem;
            z-index: 2;
            width: 1.5rem;
            height: 1.5rem;
            border-radius: 0.25rem;
            background: rgba(255, 255, 255, 0.9);
        }
        .gallery-drag-handle:hover,
        .gallery-drag-handle:focus-visible,
        .gallery-image-drag-handle:hover,
        .gallery-image-drag-handle:focus-visible {
            color: #556344;
        }
        .gallery-drag-handle:focus,
        .gallery-image-drag-handle:focus {
            outline: none;
        }
        .gallery-drag-handle:focus-visible,
        .gallery-image-drag-handle:focus-visible {
            box-shadow: inset 0 0 0 2px rgba(105, 122, 85, 0.35);
            border-radius: 0.25rem;
        }
        .gallery-drag-handle:active,
        .gallery-card.is-dragging .gallery-drag-handle,
        .gallery-image-drag-handle:active,
        .gallery-image-item.is-dragging .gallery-image-drag-handle {
            cursor: grabbing;
        }
        .gallery-card.is-dragging,
        .gallery-image-item.is-dragging {
            opacity: 0.55;
        }
        .gallery-card.is-drag-over,
        .gallery-image-item.is-drag-over {
            outline: 2px dashed rgba(105, 122, 85, 0.45);
            outline-offset: 2px;
        }
        .gallery-image-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.75rem;
        }
        @media (min-width: 640px) {
            .gallery-image-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }
        .gallery-image-item {
            position: relative;
            min-width: 0;
        }
        .gallery-image-thumb {
            aspect-ratio: 4 / 3;
            overflow: hidden;
            border-radius: 0.5rem;
            border: 1px solid rgba(229, 224, 215, 0.9);
            background: #f7f5f0;
            cursor: pointer;
        }
        .gallery-image-remove {
            position: absolute;
            top: 0.25rem;
            right: 0.25rem;
            z-index: 2;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.5rem;
            height: 1.5rem;
            border-radius: 9999px;
            border: 1px solid #E5E0D7;
            background: #fff;
            color: #A89D8C;
            font-size: 0.875rem;
            line-height: 1;
            cursor: pointer;
            box-shadow: none;
            transition:
                color 0.2s ease,
                border-color 0.2s ease,
                background-color 0.2s ease,
                box-shadow 0.2s ease;
        }
        .gallery-image-remove:hover {
            background: #F7F5F0;
            border-color: #D8D2C7;
            color: #736D65;
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        }
        .gallery-image-remove:active {
            background: #EEF1E8;
            color: #697A55;
        }
        .gallery-image-remove:focus,
        .gallery-image-remove:focus-visible {
            outline: none;
            background: #F7F5F0;
            border-color: #D8D2C7;
            color: #736D65;
            box-shadow: 0 0 0 3px rgba(105, 122, 85, 0.15);
        }
        .gallery-image-add {
            display: flex;
            min-height: 0;
            aspect-ratio: 4 / 3;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-radius: 0.5rem;
            border: 1px dashed rgba(184, 176, 159, 0.9);
            background: rgba(247, 245, 240, 0.7);
            padding: 0.75rem;
            text-align: center;
            cursor: pointer;
        }
        .gallery-image-add:hover {
            border-color: #697a55;
            background: #eef1e8;
        }
        .gallery-image-add[hidden] {
            display: none !important;
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
            const staffOptionsTemplate = document.getElementById('gallery-staff-options-template');
            const emptyHeading = '新規ギャラリー';
            const maxImages = parseInt(form && form.getAttribute('data-max-images') || '10', 10);

            if (!form || !grid || !addCard || !addButton) {
                return;
            }

            let nextNewIndex = parseInt(form.getAttribute('data-next-new-index') || '1', 10);
            let maxOrder = parseInt(form.getAttribute('data-max-order') || '0', 10);
            let dragCard = null;
            let dragImageItem = null;

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

            function fieldPrefix(card) {
                return card.getAttribute('data-gallery-name-prefix') || '';
            }

            function syncCardHeading(card) {
                const label = card.querySelector('[data-gallery-card-title]');
                const titleInput = card.querySelector('[data-gallery-title-input]');
                if (!label) {
                    return;
                }
                const value = ((titleInput && titleInput.value) || '').trim();
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

            function syncImageOrders(card) {
                const items = card.querySelectorAll('[data-gallery-image-item]');
                items.forEach(function (item, index) {
                    const orderInput = item.querySelector('[data-gallery-image-order]');
                    const label = item.querySelector('[data-gallery-image-label]');
                    if (orderInput) {
                        orderInput.value = String(index + 1);
                    }
                    if (label) {
                        label.textContent = '画像' + (index + 1);
                    }
                });
                const addBtn = card.querySelector('[data-gallery-image-add]');
                if (addBtn) {
                    addBtn.hidden = items.length >= maxImages;
                }
            }

            function staffOptionsHtml() {
                return staffOptionsTemplate ? staffOptionsTemplate.innerHTML : '<option value="">未設定</option>';
            }

            function createImageItem(card, options) {
                options = options || {};
                const prefix = fieldPrefix(card);
                const key = options.key || ('img_' + Date.now() + '_' + Math.floor(Math.random() * 1000));
                const isExisting = !!options.existingId;
                const nameBase = isExisting
                    ? prefix + '[images][' + options.existingId + ']'
                    : prefix + '[new_images][' + key + ']';

                const item = document.createElement('div');
                item.className = 'gallery-image-item';
                item.setAttribute('data-gallery-image-item', '');
                if (isExisting) {
                    item.setAttribute('data-gallery-image-id', String(options.existingId));
                    item.setAttribute('data-gallery-image-existing', '');
                } else {
                    item.setAttribute('data-gallery-image-new', '1');
                    item.setAttribute('data-gallery-image-key', key);
                }

                item.innerHTML =
                    '<span class="gallery-image-drag-handle" data-gallery-image-drag-handle draggable="true" role="button" tabindex="0" aria-label="画像を並び替え" title="ドラッグして並び替え">' +
                        '<svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">' +
                            '<circle cx="7" cy="5" r="1.25"/><circle cx="13" cy="5" r="1.25"/>' +
                            '<circle cx="7" cy="10" r="1.25"/><circle cx="13" cy="10" r="1.25"/>' +
                            '<circle cx="7" cy="15" r="1.25"/><circle cx="13" cy="15" r="1.25"/>' +
                        '</svg>' +
                    '</span>' +
                    '<button type="button" class="gallery-image-remove" data-gallery-image-remove aria-label="画像を削除" title="画像を削除">&times;</button>' +
                    '<div class="gallery-image-thumb" data-gallery-image-dropzone></div>' +
                    '<input type="hidden" name="' + nameBase + '[display_order]" value="1" data-gallery-image-order>' +
                    '<input type="hidden" name="' + nameBase + '[alt_text]" value="" data-gallery-image-alt>' +
                    '<input type="file" name="' + nameBase + '[image]" accept="image/jpeg,image/png,image/webp" class="hidden" data-gallery-image-file' + (isExisting ? '' : ' required') + '>' +
                    '<p class="mt-1 text-center text-[10px] text-admin-muted" data-gallery-image-label>画像</p>';

                const addBtn = card.querySelector('[data-gallery-image-add]');
                if (addBtn) {
                    addBtn.before(item);
                } else {
                    card.querySelector('[data-gallery-image-grid]')?.appendChild(item);
                }

                bindImageItem(card, item);

                if (options.file) {
                    applyImageFile(card, item, options.file);
                } else if (options.url) {
                    setImagePreview(item, options.url);
                } else {
                    item.querySelector('[data-gallery-image-file]')?.click();
                }

                syncImageOrders(card);
                return item;
            }

            function setImagePreview(item, src) {
                const thumb = item.querySelector('[data-gallery-image-dropzone]');
                if (!thumb) {
                    return;
                }
                let img = thumb.querySelector('[data-gallery-image-preview]');
                if (!img) {
                    img = document.createElement('img');
                    img.setAttribute('data-gallery-image-preview', '');
                    img.alt = '';
                    img.className = 'h-full w-full object-cover';
                    thumb.appendChild(img);
                }
                img.src = src;
            }

            function applyImageFile(card, item, file) {
                const input = item.querySelector('[data-gallery-image-file]');
                const error = isValidImage(file);
                if (error) {
                    if (input) {
                        input.value = '';
                    }
                    showCardImageError(card, error);
                    if (item.getAttribute('data-gallery-image-new') && !item.querySelector('[data-gallery-image-preview]')) {
                        item.remove();
                        syncImageOrders(card);
                    }
                    return;
                }
                showCardImageError(card, '');
                if (input) {
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    input.files = dt.files;
                }
                const reader = new FileReader();
                reader.onload = function (e) {
                    setImagePreview(item, e.target.result);
                };
                reader.readAsDataURL(file);
            }

            function bindImageItem(card, item) {
                const fileInput = item.querySelector('[data-gallery-image-file]');
                const dropzone = item.querySelector('[data-gallery-image-dropzone]');
                const removeBtn = item.querySelector('[data-gallery-image-remove]');

                dropzone?.addEventListener('click', function () {
                    fileInput?.click();
                });
                fileInput?.addEventListener('change', function () {
                    applyImageFile(card, item, fileInput.files && fileInput.files[0]);
                });
                removeBtn?.addEventListener('click', function () {
                    const existingId = item.getAttribute('data-gallery-image-id');
                    if (existingId) {
                        const wrap = card.querySelector('[data-gallery-deleted-images]');
                        if (wrap) {
                            const hidden = document.createElement('input');
                            hidden.type = 'hidden';
                            hidden.name = fieldPrefix(card) + '[deleted_image_ids][]';
                            hidden.value = existingId;
                            wrap.appendChild(hidden);
                        }
                    }
                    item.remove();
                    syncImageOrders(card);
                });
            }

            function bindCard(card) {
                const removeBtn = card.querySelector('[data-gallery-remove]');
                const titleInput = card.querySelector('[data-gallery-title-input]');
                const addImageBtn = card.querySelector('[data-gallery-image-add]');

                titleInput?.addEventListener('input', function () {
                    syncCardHeading(card);
                });
                syncCardHeading(card);
                syncImageOrders(card);

                card.querySelectorAll('[data-gallery-image-item]').forEach(function (item) {
                    bindImageItem(card, item);
                });

                addImageBtn?.addEventListener('click', function () {
                    const count = card.querySelectorAll('[data-gallery-image-item]').length;
                    if (count >= maxImages) {
                        showCardImageError(card, '画像は最大' + maxImages + '枚までです。');
                        return;
                    }
                    showCardImageError(card, '');
                    createImageItem(card, {});
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
                card.setAttribute('data-gallery-name-prefix', 'new_galleries[' + key + ']');
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
                    '<div data-gallery-deleted-images></div>' +
                    '<div class="mb-3">' +
                        '<p class="admin-label">ギャラリー画像</p>' +
                        '<div class="gallery-image-grid" data-gallery-image-grid>' +
                            '<button type="button" class="gallery-image-add" data-gallery-image-add>' +
                                '<svg class="mx-auto h-8 w-8 text-[#B8B09F]" viewBox="0 0 24 24" fill="none" aria-hidden="true">' +
                                    '<rect x="3.5" y="5.5" width="17" height="13" rx="2" stroke="currentColor" stroke-width="1.5"/>' +
                                    '<circle cx="9" cy="10.5" r="1.5" fill="currentColor" opacity="0.7"/>' +
                                    '<path d="M5.5 16.5l4-3.5 2.5 2 3.5-3.5 3 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>' +
                                '</svg>' +
                                '<span class="mt-1 block text-xs font-medium text-admin-accent">＋画像を追加</span>' +
                            '</button>' +
                        '</div>' +
                        '<p class="mt-1 hidden text-sm text-red-600" data-gallery-image-error role="alert"></p>' +
                    '</div>' +
                    '<div class="space-y-3">' +
                        '<div>' +
                            '<label for="gallery_new_title_' + key + '" class="admin-label">タイトル</label>' +
                            '<input type="text" name="new_galleries[' + key + '][title]" id="gallery_new_title_' + key + '" value="" maxlength="255" class="admin-input" placeholder="例：ショートボブ" data-gallery-title-input>' +
                        '</div>' +
                        '<div>' +
                            '<label for="gallery_new_caption_' + key + '" class="admin-label">詳細</label>' +
                            '<textarea name="new_galleries[' + key + '][caption]" id="gallery_new_caption_' + key + '" rows="4" maxlength="2000" class="admin-input min-h-[7rem] resize-y" placeholder="スタイルの特徴やポイントを入力してください" data-gallery-caption-input></textarea>' +
                        '</div>' +
                        '<div>' +
                            '<label for="gallery_new_staff_' + key + '" class="admin-label">担当スタッフ</label>' +
                            '<select name="new_galleries[' + key + '][staff_id]" id="gallery_new_staff_' + key + '" class="admin-input" data-gallery-staff-input>' +
                                staffOptionsHtml() +
                            '</select>' +
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

            // Gallery card drag
            grid.addEventListener('dragstart', function (e) {
                const imageHandle = e.target.closest('[data-gallery-image-drag-handle]');
                if (imageHandle && grid.contains(imageHandle)) {
                    const item = imageHandle.closest('[data-gallery-image-item]');
                    const card = imageHandle.closest('[data-gallery-card]');
                    if (!item || !card) {
                        e.preventDefault();
                        return;
                    }
                    dragImageItem = item;
                    dragCard = null;
                    item.classList.add('is-dragging');
                    e.dataTransfer.effectAllowed = 'move';
                    e.dataTransfer.setData('text/plain', 'image');
                    return;
                }

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
                dragImageItem = null;
                card.classList.add('is-dragging');
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', card.getAttribute('data-gallery-id') || 'new');
            });

            grid.addEventListener('dragend', function () {
                if (dragCard) {
                    dragCard.classList.remove('is-dragging');
                }
                if (dragImageItem) {
                    dragImageItem.classList.remove('is-dragging');
                    const card = dragImageItem.closest('[data-gallery-card]');
                    if (card) {
                        syncImageOrders(card);
                    }
                }
                grid.querySelectorAll('.is-drag-over').forEach(function (el) {
                    el.classList.remove('is-drag-over');
                });
                dragCard = null;
                dragImageItem = null;
                syncDisplayOrders();
            });

            grid.addEventListener('dragover', function (e) {
                if (dragImageItem) {
                    e.preventDefault();
                    const over = e.target.closest('[data-gallery-image-item]');
                    const card = dragImageItem.closest('[data-gallery-card]');
                    if (!over || over === dragImageItem || !card || !card.contains(over)) {
                        return;
                    }
                    card.querySelectorAll('.is-drag-over').forEach(function (el) {
                        if (el !== over) {
                            el.classList.remove('is-drag-over');
                        }
                    });
                    over.classList.add('is-drag-over');
                    const rect = over.getBoundingClientRect();
                    const before = (e.clientX - rect.left) < rect.width / 2;
                    if (before) {
                        over.before(dragImageItem);
                    } else {
                        over.after(dragImageItem);
                    }
                    return;
                }

                if (!dragCard) {
                    return;
                }
                e.preventDefault();
                const over = e.target.closest('[data-gallery-card]');
                if (!over || over === dragCard || !grid.contains(over)) {
                    return;
                }
                grid.querySelectorAll('[data-gallery-card].is-drag-over').forEach(function (el) {
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
                if (!dragCard && !dragImageItem) {
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
