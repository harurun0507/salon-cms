@extends('layouts.admin')

@section('heading', 'バナー')

@section('content')
    @php
        $locationLabels = \App\Models\Banner::LOCATION_LABELS;
        $maxOrder = (int) ($banners->max('display_order') ?? 0);

        $formatLocal = function ($value) {
            if (! $value) {
                return '';
            }
            try {
                return \Illuminate\Support\Carbon::parse($value)->format('Y-m-d\TH:i');
            } catch (\Throwable) {
                return '';
            }
        };
    @endphp

    <div class="sticky top-[4.5rem] z-10 -mx-4 -mt-4 mb-6 border-b border-admin-border/50 bg-admin-bg/95 px-4 py-3 shadow-[0_1px_0_rgba(61,56,51,0.03)] backdrop-blur-sm md:-mx-8 md:-mt-8 md:px-8">
        <div class="flex min-w-0 flex-wrap items-center gap-3">
            <button
                type="button"
                class="admin-btn shadow-md shrink-0"
                data-admin-confirm-trigger
                data-confirm-form="banners-bulk-form"
                data-confirm-title="バナー保存の確認"
                data-confirm-message="変更内容を保存します。&#10;よろしいですか？"
                data-confirm-note="画像、タイトル、リンク、表示場所、公開期間、公開状態、表示順、削除など、現在入力されている内容が反映されます。"
                data-confirm-submit-label="保存する"
            >保存する</button>
            <p class="text-sm text-admin-muted">
                トップページなどに表示するバナーを登録します。推奨サイズ：1200×400px（JPEG / PNG / WebP、5MBまで）
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
        action="{{ route('admin.home.banners.update') }}"
        id="banners-bulk-form"
        enctype="multipart/form-data"
        data-banner-workspace
        data-next-new-index="1"
        data-max-order="{{ $maxOrder }}"
    >
        @csrf
        @method('PUT')

        <div id="banner-deleted-ids"></div>

        <div id="banner-grid" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3" data-banner-grid>
            @foreach($banners as $banner)
                @php
                    $prefix = 'banners.'.$banner->id;
                    $publishedOld = old($prefix.'.is_published', $banner->is_published ? '1' : '0');
                    $isPublished = in_array((string) $publishedOld, ['1', 'true', 'on'], true);
                    $linkTarget = old($prefix.'.link_target', $banner->link_target ?: '_self');
                    $displayLocation = old($prefix.'.display_location', $banner->display_location ?: 'top');
                    $publishedFrom = old($prefix.'.published_from', $formatLocal($banner->published_from));
                    $publishedUntil = old($prefix.'.published_until', $formatLocal($banner->published_until));
                    $displayOrder = old($prefix.'.display_order', $banner->display_order);
                    $cardTitle = trim((string) old($prefix.'.title', $banner->title));
                    $headingTitle = $cardTitle !== '' ? $cardTitle : '新規バナー';
                @endphp
                <div
                    class="admin-card banner-card"
                    data-banner-card
                    data-banner-id="{{ $banner->id }}"
                    data-banner-existing
                >
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-2">
                            <span
                                class="banner-drag-handle"
                                data-banner-drag-handle
                                draggable="true"
                                role="button"
                                tabindex="0"
                                aria-label="バナーを並び替え"
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
                            <p class="banner-card-label truncate text-sm font-medium text-gray-800" data-banner-card-title title="{{ $headingTitle }}">{{ $headingTitle }}</p>
                        </div>
                        <button
                            type="button"
                            class="admin-icon-btn admin-icon-btn-delete"
                            data-banner-remove
                            aria-label="削除"
                            title="削除"
                        >
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <input type="hidden" name="banners[{{ $banner->id }}][display_order]" value="{{ $displayOrder }}" data-banner-order>

                    <div class="mb-3">
                        <div data-banner-dropzone class="banner-dropzone cursor-pointer overflow-hidden rounded-lg">
                            <div data-banner-preview>
                                <img
                                    src="{{ asset('storage/'.$banner->image_path) }}"
                                    alt=""
                                    class="aspect-[3/1] w-full object-cover"
                                    data-banner-image
                                >
                            </div>
                            <div data-banner-placeholder class="banner-dropzone-placeholder hidden min-h-[7.5rem] flex-col items-center justify-center px-4 text-center">
                                <div class="banner-dropzone-main">
                                    <svg class="banner-dropzone-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <rect x="3.5" y="5.5" width="17" height="13" rx="2" stroke="currentColor" stroke-width="1.5"/>
                                        <circle cx="9" cy="10.5" r="1.5" fill="currentColor" opacity="0.7"/>
                                        <path d="M5.5 16.5l4-3.5 2.5 2 3.5-3.5 3 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <p class="banner-dropzone-text text-sm text-gray-700">画像をドラッグ＆ドロップ、またはクリックして選択</p>
                                </div>
                                <p class="banner-dropzone-hint mt-2 text-xs text-gray-500">推奨 1200×400 / JPEG・PNG・WebP・5MBまで</p>
                            </div>
                            <p class="banner-dropzone-drag-message" aria-hidden="true">ここに画像をドロップしてください</p>
                        </div>
                        <input
                            type="file"
                            name="banners[{{ $banner->id }}][image]"
                            accept="image/jpeg,image/png,image/webp"
                            class="hidden"
                            data-banner-file
                        >
                        <p class="mt-1 text-xs text-admin-muted">クリックまたは DnD で画像を変更できます</p>
                        <p class="mt-1 hidden text-sm text-red-600" data-banner-image-error role="alert"></p>
                        @error($prefix.'.image')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label for="banner_title_{{ $banner->id }}" class="admin-label">タイトル <span class="admin-required-badge">必須</span></label>
                            <input
                                type="text"
                                name="banners[{{ $banner->id }}][title]"
                                id="banner_title_{{ $banner->id }}"
                                value="{{ old($prefix.'.title', $banner->title) }}"
                                maxlength="255"
                                required
                                class="admin-input"
                                data-banner-title-input
                            >
                            @error($prefix.'.title')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="banner_description_{{ $banner->id }}" class="admin-label">説明文</label>
                            <textarea
                                name="banners[{{ $banner->id }}][description]"
                                id="banner_description_{{ $banner->id }}"
                                rows="2"
                                maxlength="2000"
                                class="admin-input"
                            >{{ old($prefix.'.description', $banner->description) }}</textarea>
                        </div>
                        <div>
                            <label for="banner_link_{{ $banner->id }}" class="admin-label">リンクURL</label>
                            <input
                                type="url"
                                name="banners[{{ $banner->id }}][link_url]"
                                id="banner_link_{{ $banner->id }}"
                                value="{{ old($prefix.'.link_url', $banner->link_url) }}"
                                maxlength="2048"
                                placeholder="https://"
                                class="admin-input"
                            >
                            @error($prefix.'.link_url')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <span class="admin-label">リンクターゲット</span>
                            <div class="admin-segmented mt-1" role="radiogroup" aria-label="リンクターゲット">
                                <label class="admin-segmented-option">
                                    <input type="radio" name="banners[{{ $banner->id }}][link_target]" value="_self" class="admin-segmented-input" @checked($linkTarget === '_self')>
                                    <span class="admin-segmented-face">
                                        <svg class="admin-segmented-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                            <rect x="2.5" y="3.5" width="11" height="9" rx="1.5" stroke="currentColor" stroke-width="1.35"/>
                                            <path d="M2.5 6.5h11" stroke="currentColor" stroke-width="1.35"/>
                                        </svg>
                                        <span class="admin-segmented-text">同じタブ</span>
                                    </span>
                                </label>
                                <label class="admin-segmented-option">
                                    <input type="radio" name="banners[{{ $banner->id }}][link_target]" value="_blank" class="admin-segmented-input" @checked($linkTarget === '_blank')>
                                    <span class="admin-segmented-face">
                                        <svg class="admin-segmented-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                            <path d="M6.5 3.5H3.75A1.25 1.25 0 0 0 2.5 4.75v7.5c0 .69.56 1.25 1.25 1.25h7.5c.69 0 1.25-.56 1.25-1.25V9.5" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/>
                                            <path d="M9.5 2.5h4v4M13.5 2.5 7.75 8.25" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        <span class="admin-segmented-text">新しいタブ</span>
                                    </span>
                                </label>
                            </div>
                        </div>
                        <div>
                            <span class="admin-label" id="banner_location_label_{{ $banner->id }}">表示場所</span>
                            <div class="banner-location-chips" role="radiogroup" aria-labelledby="banner_location_label_{{ $banner->id }}">
                                @foreach($locationLabels as $value => $label)
                                    <label class="banner-location-chip">
                                        <input
                                            type="radio"
                                            name="banners[{{ $banner->id }}][display_location]"
                                            value="{{ $value }}"
                                            class="banner-location-chip-input"
                                            @checked($displayLocation === $value)
                                        >
                                        <span class="banner-location-chip-face">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <label for="banner_from_{{ $banner->id }}" class="admin-label">公開開始</label>
                                <input
                                    type="datetime-local"
                                    name="banners[{{ $banner->id }}][published_from]"
                                    id="banner_from_{{ $banner->id }}"
                                    value="{{ $publishedFrom }}"
                                    class="admin-input"
                                >
                                <p class="mt-1 text-xs text-admin-muted">空欄＝すぐ公開</p>
                            </div>
                            <div>
                                <label for="banner_until_{{ $banner->id }}" class="admin-label">公開終了</label>
                                <input
                                    type="datetime-local"
                                    name="banners[{{ $banner->id }}][published_until]"
                                    id="banner_until_{{ $banner->id }}"
                                    value="{{ $publishedUntil }}"
                                    class="admin-input"
                                >
                                <p class="mt-1 text-xs text-admin-muted">空欄＝終了なし</p>
                                @error($prefix.'.published_until')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div>
                            <label for="banner_alt_{{ $banner->id }}" class="admin-label">altテキスト</label>
                            <input
                                type="text"
                                name="banners[{{ $banner->id }}][alt_text]"
                                id="banner_alt_{{ $banner->id }}"
                                value="{{ old($prefix.'.alt_text', $banner->alt_text) }}"
                                maxlength="255"
                                class="admin-input"
                                placeholder="未入力時はタイトルを使用"
                            >
                        </div>
                        <div>
                            <span class="admin-label">公開</span>
                            <div class="admin-segmented mt-1" role="radiogroup" aria-label="公開状態">
                                <label class="admin-segmented-option">
                                    <input type="radio" name="banners[{{ $banner->id }}][is_published]" value="1" class="admin-segmented-input" @checked($isPublished)>
                                    <span class="admin-segmented-face">
                                        <svg class="admin-segmented-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                            <path d="M1.5 8s2.5-4.5 6.5-4.5S14.5 8 14.5 8s-2.5 4.5-6.5 4.5S1.5 8 1.5 8z" stroke="currentColor" stroke-width="1.35" stroke-linejoin="round"/>
                                            <circle cx="8" cy="8" r="2" stroke="currentColor" stroke-width="1.35"/>
                                        </svg>
                                        <span class="admin-segmented-text">公開</span>
                                    </span>
                                </label>
                                <label class="admin-segmented-option">
                                    <input type="radio" name="banners[{{ $banner->id }}][is_published]" value="0" class="admin-segmented-input" @checked(!$isPublished)>
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
                id="banner-add-card"
                class="admin-card flex min-h-[22rem] w-full flex-col items-center justify-center px-6 py-10 text-center"
            >
                <div class="admin-empty-state-icon !mb-4" aria-hidden="true">
                    <svg class="h-14 w-14" viewBox="0 0 80 80" fill="none" stroke="#B8B09F" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 28h34l10 8v22a4 4 0 0 1-4 4H18a4 4 0 0 1-4-4V32a4 4 0 0 1 4-4z" stroke-width="1.4"/>
                        <path d="M28 28V22a6 6 0 0 1 6-6h12a6 6 0 0 1 6 6v6" stroke-width="1.3" opacity="0.75"/>
                        <path d="M32 44h16" stroke-width="1.4"/>
                        <path d="M32 52h10" stroke-width="1.3" opacity="0.7"/>
                        <circle cx="58" cy="24" r="8" stroke-width="1.3" opacity="0.65"/>
                        <path d="M58 20v8M54 24h8" stroke-width="1.3" opacity="0.65"/>
                    </svg>
                </div>
                <x-admin.create-button data-banner-add>
                    バナーを追加
                </x-admin.create-button>
                <p class="mt-3 text-xs text-admin-muted">カードを追加し、保存で登録できます。</p>
            </div>
        </div>
    </form>

    <style>
        .banner-drag-handle {
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
        .banner-drag-handle:hover,
        .banner-drag-handle:focus-visible {
            color: #556344;
        }
        .banner-drag-handle:focus {
            outline: none;
        }
        .banner-drag-handle:focus-visible {
            box-shadow: inset 0 0 0 2px rgba(105, 122, 85, 0.35);
            border-radius: 0.25rem;
        }
        .banner-drag-handle:active,
        .banner-card.is-dragging .banner-drag-handle {
            cursor: grabbing;
        }
        .banner-card.is-dragging {
            opacity: 0.55;
        }
        .banner-card.is-drag-over {
            outline: 2px dashed rgba(105, 122, 85, 0.45);
            outline-offset: 2px;
        }
    </style>

    <script>
        (function () {
            const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            const maxSize = 5 * 1024 * 1024;
            const form = document.getElementById('banners-bulk-form');
            const grid = document.getElementById('banner-grid');
            const deletedIdsWrap = document.getElementById('banner-deleted-ids');
            const addCard = document.getElementById('banner-add-card');
            const addButton = addCard ? addCard.querySelector('[data-banner-add]') : null;
            const locationOptions = @json($locationLabels);
            const emptyHeading = '新規バナー';
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
                const errorEl = card.querySelector('[data-banner-image-error]');
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
                const preview = card.querySelector('[data-banner-preview]');
                const placeholder = card.querySelector('[data-banner-placeholder]');
                const dropzone = card.querySelector('[data-banner-dropzone]');
                if (!preview || !placeholder) {
                    return;
                }

                function showImage(src) {
                    let img = preview.querySelector('[data-banner-image]');
                    if (!img) {
                        img = document.createElement('img');
                        img.setAttribute('data-banner-image', '');
                        img.alt = '';
                        img.className = 'aspect-[3/1] w-full object-cover';
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
                const input = card.querySelector('[data-banner-file]');
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
                const label = card.querySelector('[data-banner-card-title]');
                const input = card.querySelector('[data-banner-title-input]');
                if (!label || !input) {
                    return;
                }
                const value = (input.value || '').trim();
                const text = value !== '' ? value : emptyHeading;
                label.textContent = text;
                label.setAttribute('title', text);
            }

            function syncDisplayOrders() {
                grid.querySelectorAll('[data-banner-card]').forEach(function (card, index) {
                    const orderInput = card.querySelector('[data-banner-order]');
                    if (orderInput) {
                        orderInput.value = String(index + 1);
                    }
                });
                maxOrder = grid.querySelectorAll('[data-banner-card]').length;
                form.setAttribute('data-max-order', String(maxOrder));
            }

            function locationChipsHtml(name, selected) {
                let html = '<div class="banner-location-chips" role="radiogroup" aria-label="表示場所">';
                Object.keys(locationOptions).forEach(function (value) {
                    html +=
                        '<label class="banner-location-chip">' +
                            '<input type="radio" name="' + name + '" value="' + value + '" class="banner-location-chip-input"' +
                                (value === selected ? ' checked' : '') + '>' +
                            '<span class="banner-location-chip-face">' + locationOptions[value] + '</span>' +
                        '</label>';
                });
                html += '</div>';
                return html;
            }

            function clearDropzoneDragState(dropzone) {
                if (!dropzone) {
                    return;
                }
                dropzone._bannerDragCounter = 0;
                dropzone.classList.remove('is-drag-active');
            }

            function bindCard(card) {
                const dropzone = card.querySelector('[data-banner-dropzone]');
                const input = card.querySelector('[data-banner-file]');
                const removeBtn = card.querySelector('[data-banner-remove]');
                const titleInput = card.querySelector('[data-banner-title-input]');

                if (dropzone) {
                    dropzone._bannerDragCounter = 0;
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
                    dropzone._bannerDragCounter = (dropzone._bannerDragCounter || 0) + 1;
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
                    dropzone._bannerDragCounter = Math.max(0, (dropzone._bannerDragCounter || 0) - 1);
                    if (dropzone._bannerDragCounter === 0) {
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

                titleInput?.addEventListener('input', function () {
                    syncCardHeading(card);
                });
                syncCardHeading(card);

                removeBtn?.addEventListener('click', function () {
                    const existingId = card.getAttribute('data-banner-id');
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

                const order = grid.querySelectorAll('[data-banner-card]').length + 1;
                maxOrder = Math.max(maxOrder, order);
                form.setAttribute('data-max-order', String(maxOrder));

                const card = document.createElement('div');
                card.className = 'admin-card banner-card';
                card.setAttribute('data-banner-card', '');
                card.setAttribute('data-banner-new', '1');
                card.innerHTML =
                    '<div class="mb-3 flex items-center justify-between gap-3">' +
                        '<div class="flex min-w-0 items-center gap-2">' +
                            '<span class="banner-drag-handle" data-banner-drag-handle draggable="true" role="button" tabindex="0" aria-label="バナーを並び替え" title="ドラッグして並び替え">' +
                                '<svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">' +
                                    '<circle cx="7" cy="5" r="1.25"/><circle cx="13" cy="5" r="1.25"/>' +
                                    '<circle cx="7" cy="10" r="1.25"/><circle cx="13" cy="10" r="1.25"/>' +
                                    '<circle cx="7" cy="15" r="1.25"/><circle cx="13" cy="15" r="1.25"/>' +
                                '</svg>' +
                            '</span>' +
                            '<p class="banner-card-label truncate text-sm font-medium text-gray-800" data-banner-card-title title="' + emptyHeading + '">' + emptyHeading + '</p>' +
                        '</div>' +
                        '<button type="button" class="admin-icon-btn admin-icon-btn-delete" data-banner-remove aria-label="削除" title="削除">' +
                            '<span aria-hidden="true">&times;</span>' +
                        '</button>' +
                    '</div>' +
                    '<input type="hidden" name="new_banners[' + key + '][display_order]" value="' + order + '" data-banner-order>' +
                    '<div class="mb-3">' +
                        '<div data-banner-dropzone class="banner-dropzone is-empty cursor-pointer">' +
                            '<div data-banner-placeholder class="banner-dropzone-placeholder">' +
                                dropzoneMainHtml +
                                '<p class="banner-dropzone-hint mt-2 text-xs text-gray-500">推奨 1200×400 / JPEG・PNG・WebP・5MBまで</p>' +
                            '</div>' +
                            '<div data-banner-preview class="hidden"></div>' +
                            '<p class="banner-dropzone-drag-message" aria-hidden="true">ここに画像をドロップしてください</p>' +
                        '</div>' +
                        '<input type="file" name="new_banners[' + key + '][image]" accept="image/jpeg,image/png,image/webp" class="hidden" data-banner-file>' +
                        '<p class="mt-1 hidden text-sm text-red-600" data-banner-image-error role="alert"></p>' +
                    '</div>' +
                    '<div class="space-y-3">' +
                        '<div>' +
                            '<label class="admin-label">タイトル <span class="admin-required-badge">必須</span></label>' +
                            '<input type="text" name="new_banners[' + key + '][title]" value="" maxlength="255" required class="admin-input" data-banner-title-input>' +
                        '</div>' +
                        '<div>' +
                            '<label class="admin-label">説明文</label>' +
                            '<textarea name="new_banners[' + key + '][description]" rows="2" maxlength="2000" class="admin-input"></textarea>' +
                        '</div>' +
                        '<div>' +
                            '<label class="admin-label">リンクURL</label>' +
                            '<input type="url" name="new_banners[' + key + '][link_url]" value="" maxlength="2048" placeholder="https://" class="admin-input">' +
                        '</div>' +
                        '<div>' +
                            '<span class="admin-label">リンクターゲット</span>' +
                            '<div class="admin-segmented mt-1" role="radiogroup" aria-label="リンクターゲット">' +
                                '<label class="admin-segmented-option">' +
                                    '<input type="radio" name="new_banners[' + key + '][link_target]" value="_self" class="admin-segmented-input" checked>' +
                                    '<span class="admin-segmented-face">' +
                                        '<svg class="admin-segmented-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">' +
                                            '<rect x="2.5" y="3.5" width="11" height="9" rx="1.5" stroke="currentColor" stroke-width="1.35"/>' +
                                            '<path d="M2.5 6.5h11" stroke="currentColor" stroke-width="1.35"/>' +
                                        '</svg>' +
                                        '<span class="admin-segmented-text">同じタブ</span>' +
                                    '</span>' +
                                '</label>' +
                                '<label class="admin-segmented-option">' +
                                    '<input type="radio" name="new_banners[' + key + '][link_target]" value="_blank" class="admin-segmented-input">' +
                                    '<span class="admin-segmented-face">' +
                                        '<svg class="admin-segmented-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">' +
                                            '<path d="M6.5 3.5H3.75A1.25 1.25 0 0 0 2.5 4.75v7.5c0 .69.56 1.25 1.25 1.25h7.5c.69 0 1.25-.56 1.25-1.25V9.5" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/>' +
                                            '<path d="M9.5 2.5h4v4M13.5 2.5 7.75 8.25" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round"/>' +
                                        '</svg>' +
                                        '<span class="admin-segmented-text">新しいタブ</span>' +
                                    '</span>' +
                                '</label>' +
                            '</div>' +
                        '</div>' +
                        '<div>' +
                            '<span class="admin-label">表示場所</span>' +
                            locationChipsHtml('new_banners[' + key + '][display_location]', 'top') +
                        '</div>' +
                        '<div class="grid grid-cols-1 gap-3 sm:grid-cols-2">' +
                            '<div>' +
                                '<label class="admin-label">公開開始</label>' +
                                '<input type="datetime-local" name="new_banners[' + key + '][published_from]" value="" class="admin-input">' +
                                '<p class="mt-1 text-xs text-admin-muted">空欄＝すぐ公開</p>' +
                            '</div>' +
                            '<div>' +
                                '<label class="admin-label">公開終了</label>' +
                                '<input type="datetime-local" name="new_banners[' + key + '][published_until]" value="" class="admin-input">' +
                                '<p class="mt-1 text-xs text-admin-muted">空欄＝終了なし</p>' +
                            '</div>' +
                        '</div>' +
                        '<div>' +
                            '<label class="admin-label">altテキスト</label>' +
                            '<input type="text" name="new_banners[' + key + '][alt_text]" value="" maxlength="255" class="admin-input" placeholder="未入力時はタイトルを使用">' +
                        '</div>' +
                        '<div>' +
                            '<span class="admin-label">公開</span>' +
                            '<div class="admin-segmented mt-1" role="radiogroup" aria-label="公開状態">' +
                                '<label class="admin-segmented-option">' +
                                    '<input type="radio" name="new_banners[' + key + '][is_published]" value="1" class="admin-segmented-input" checked>' +
                                    '<span class="admin-segmented-face">' +
                                        '<svg class="admin-segmented-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">' +
                                            '<path d="M1.5 8s2.5-4.5 6.5-4.5S14.5 8 14.5 8s-2.5 4.5-6.5 4.5S1.5 8 1.5 8z" stroke="currentColor" stroke-width="1.35" stroke-linejoin="round"/>' +
                                            '<circle cx="8" cy="8" r="2" stroke="currentColor" stroke-width="1.35"/>' +
                                        '</svg>' +
                                        '<span class="admin-segmented-text">公開</span>' +
                                    '</span>' +
                                '</label>' +
                                '<label class="admin-segmented-option">' +
                                    '<input type="radio" name="new_banners[' + key + '][is_published]" value="0" class="admin-segmented-input">' +
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
                const handle = e.target.closest('[data-banner-drag-handle]');
                if (!handle || !grid.contains(handle)) {
                    return;
                }
                const card = handle.closest('[data-banner-card]');
                if (!card) {
                    e.preventDefault();
                    return;
                }
                dragCard = card;
                card.classList.add('is-dragging');
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', card.getAttribute('data-banner-id') || 'new');
            });

            grid.addEventListener('dragend', function () {
                if (dragCard) {
                    dragCard.classList.remove('is-dragging');
                }
                grid.querySelectorAll('.is-drag-over').forEach(function (el) {
                    el.classList.remove('is-drag-over');
                });
                grid.querySelectorAll('[data-banner-dropzone]').forEach(clearDropzoneDragState);
                dragCard = null;
                syncDisplayOrders();
            });

            grid.addEventListener('dragover', function (e) {
                if (!dragCard) {
                    return;
                }
                e.preventDefault();
                const over = e.target.closest('[data-banner-card]');
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

            grid.querySelectorAll('[data-banner-card]').forEach(bindCard);
            syncDisplayOrders();

            addButton.addEventListener('click', function (e) {
                e.preventDefault();
                createEmptyCard();
            });
        })();
    </script>
@endsection
