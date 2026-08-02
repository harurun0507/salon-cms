@extends('layouts.admin')

@section('heading', 'スタッフ管理')

@section('content')
    @php
        $maxOrder = (int) ($staffMembers->max('sort_order') ?? 0);

        $oldNewStaff = old('new_staff', []);
        if (! is_array($oldNewStaff)) {
            $oldNewStaff = [];
        }
        $nextNewIndex = 1;
        foreach (array_keys($oldNewStaff) as $key) {
            if (preg_match('/^new_(\d+)$/', (string) $key, $m)) {
                $nextNewIndex = max($nextNewIndex, ((int) $m[1]) + 1);
            }
        }
    @endphp

    <div class="sticky top-[4.5rem] z-10 -mx-4 -mt-4 mb-6 border-b border-admin-border/50 bg-admin-bg/95 px-4 py-3 shadow-[0_1px_0_rgba(61,56,51,0.03)] backdrop-blur-sm md:-mx-8 md:-mt-8 md:px-8">
        <div class="flex min-w-0 flex-wrap items-center gap-3">
            <button
                type="button"
                class="admin-btn shadow-md shrink-0"
                data-admin-confirm-trigger
                data-confirm-form="staff-bulk-form"
                data-confirm-title="スタッフ保存の確認"
                data-confirm-message="変更内容を保存します。&#10;よろしいですか？"
                data-confirm-note="写真、名前、役職、プロフィール、表示順、公開状態、削除など、現在入力されている内容が反映されます。"
                data-confirm-submit-label="保存する"
            >保存する</button>
            <p class="text-sm text-admin-muted">
                カードで編集し、「保存する」でまとめて反映できます
            </p>
        </div>
    </div>

    <div class="mb-6">
        <p class="text-sm text-admin-muted">スタッフ情報の管理</p>
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
        action="{{ route('admin.staff.bulk-update') }}"
        id="staff-bulk-form"
        enctype="multipart/form-data"
        data-staff-workspace
        data-next-new-index="{{ $nextNewIndex }}"
        data-max-order="{{ $maxOrder }}"
    >
        @csrf
        @method('PUT')

        <div id="staff-deleted-ids"></div>

        <div id="staff-grid" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3" data-staff-grid>
            @foreach($staffMembers as $member)
                @php
                    $prefix = 'staff.'.$member->id;
                    $publishedOld = old($prefix.'.is_published', $member->is_published ? '1' : '0');
                    $isPublished = in_array((string) $publishedOld, ['1', 'true', 'on'], true);
                    $sortOrder = old($prefix.'.sort_order', $member->sort_order);
                    $cardName = trim((string) old($prefix.'.name', $member->name));
                    $headingTitle = $cardName !== '' ? $cardName : '新規スタッフ';
                @endphp
                <div
                    class="admin-card staff-card"
                    data-staff-card
                    data-staff-id="{{ $member->id }}"
                    data-staff-existing
                >
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-2">
                            <span
                                class="staff-drag-handle"
                                data-staff-drag-handle
                                draggable="true"
                                role="button"
                                tabindex="0"
                                aria-label="スタッフを並び替え"
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
                            <p class="banner-card-label truncate text-sm font-medium text-gray-800" data-staff-card-title title="{{ $headingTitle }}">{{ $headingTitle }}</p>
                        </div>
                        <button
                            type="button"
                            class="admin-icon-btn admin-icon-btn-delete"
                            data-staff-remove
                            aria-label="削除"
                            title="削除"
                        >
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <input type="hidden" name="staff[{{ $member->id }}][sort_order]" value="{{ $sortOrder }}" data-staff-order>

                    <div class="mb-3">
                        <div data-staff-dropzone class="banner-dropzone cursor-pointer overflow-hidden rounded-lg {{ $member->photo_path ? '' : 'is-empty' }}">
                            <div data-staff-preview class="{{ $member->photo_path ? '' : 'hidden' }}">
                                @if($member->photo_path)
                                    <img
                                        src="{{ asset('storage/'.$member->photo_path) }}"
                                        alt=""
                                        class="aspect-square w-full object-cover"
                                        data-staff-image
                                    >
                                @endif
                            </div>
                            <div data-staff-placeholder class="banner-dropzone-placeholder {{ $member->photo_path ? 'hidden' : '' }} min-h-[7.5rem] flex-col items-center justify-center px-4 text-center">
                                <div class="banner-dropzone-main">
                                    <svg class="banner-dropzone-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <rect x="3.5" y="5.5" width="17" height="13" rx="2" stroke="currentColor" stroke-width="1.5"/>
                                        <circle cx="9" cy="10.5" r="1.5" fill="currentColor" opacity="0.7"/>
                                        <path d="M5.5 16.5l4-3.5 2.5 2 3.5-3.5 3 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <p class="banner-dropzone-text text-sm text-gray-700">写真をドラッグ＆ドロップ、またはクリックして選択</p>
                                </div>
                                <p class="banner-dropzone-hint mt-2 text-xs text-gray-500">JPEG / PNG / WebP、5MBまで</p>
                            </div>
                            <p class="banner-dropzone-drag-message" aria-hidden="true">ここに写真をドロップしてください</p>
                        </div>
                        <input
                            type="file"
                            name="staff[{{ $member->id }}][photo]"
                            accept="image/jpeg,image/png,image/webp"
                            class="hidden"
                            data-staff-file
                        >
                        <p class="mt-1 text-xs text-admin-muted">クリックまたは DnD で写真を変更できます</p>
                        <p class="mt-1 hidden text-sm text-red-600" data-staff-photo-error role="alert"></p>
                        @error($prefix.'.photo')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label for="staff_name_{{ $member->id }}" class="admin-label">名前 <span class="admin-required-badge">必須</span></label>
                            <input
                                type="text"
                                name="staff[{{ $member->id }}][name]"
                                id="staff_name_{{ $member->id }}"
                                value="{{ old($prefix.'.name', $member->name) }}"
                                maxlength="255"
                                required
                                class="admin-input"
                                data-staff-name-input
                            >
                            @error($prefix.'.name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="staff_role_{{ $member->id }}" class="admin-label">役職・担当</label>
                            <input
                                type="text"
                                name="staff[{{ $member->id }}][role]"
                                id="staff_role_{{ $member->id }}"
                                value="{{ old($prefix.'.role', $member->role) }}"
                                maxlength="255"
                                class="admin-input"
                            >
                            @error($prefix.'.role')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="staff_profile_{{ $member->id }}" class="admin-label">プロフィール</label>
                            <textarea
                                name="staff[{{ $member->id }}][profile]"
                                id="staff_profile_{{ $member->id }}"
                                rows="4"
                                class="admin-input"
                            >{{ old($prefix.'.profile', $member->profile) }}</textarea>
                            @error($prefix.'.profile')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <span class="admin-label">公開</span>
                            <div class="admin-segmented mt-1" role="radiogroup" aria-label="公開状態">
                                <label class="admin-segmented-option">
                                    <input type="radio" name="staff[{{ $member->id }}][is_published]" value="1" class="admin-segmented-input" @checked($isPublished)>
                                    <span class="admin-segmented-face">
                                        <svg class="admin-segmented-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                            <path d="M1.5 8s2.5-4.5 6.5-4.5S14.5 8 14.5 8s-2.5 4.5-6.5 4.5S1.5 8 1.5 8z" stroke="currentColor" stroke-width="1.35" stroke-linejoin="round"/>
                                            <circle cx="8" cy="8" r="2" stroke="currentColor" stroke-width="1.35"/>
                                        </svg>
                                        <span class="admin-segmented-text">公開</span>
                                    </span>
                                </label>
                                <label class="admin-segmented-option">
                                    <input type="radio" name="staff[{{ $member->id }}][is_published]" value="0" class="admin-segmented-input" @checked(!$isPublished)>
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

            @foreach($oldNewStaff as $key => $newItem)
                @php
                    if (! is_array($newItem)) {
                        continue;
                    }
                    $prefix = 'new_staff.'.$key;
                    $publishedOld = old($prefix.'.is_published', $newItem['is_published'] ?? '1');
                    $isPublished = in_array((string) $publishedOld, ['1', 'true', 'on'], true);
                    $sortOrder = old($prefix.'.sort_order', $newItem['sort_order'] ?? 0);
                    $cardName = trim((string) old($prefix.'.name', $newItem['name'] ?? ''));
                    $headingTitle = $cardName !== '' ? $cardName : '新規スタッフ';
                    $role = old($prefix.'.role', $newItem['role'] ?? '');
                    $profile = old($prefix.'.profile', $newItem['profile'] ?? '');
                @endphp
                <div
                    class="admin-card staff-card"
                    data-staff-card
                    data-staff-new="1"
                >
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-2">
                            <span
                                class="staff-drag-handle"
                                data-staff-drag-handle
                                draggable="true"
                                role="button"
                                tabindex="0"
                                aria-label="スタッフを並び替え"
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
                            <p class="banner-card-label truncate text-sm font-medium text-gray-800" data-staff-card-title title="{{ $headingTitle }}">{{ $headingTitle }}</p>
                        </div>
                        <button
                            type="button"
                            class="admin-icon-btn admin-icon-btn-delete"
                            data-staff-remove
                            aria-label="削除"
                            title="削除"
                        >
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <input type="hidden" name="new_staff[{{ $key }}][sort_order]" value="{{ $sortOrder }}" data-staff-order>

                    <div class="mb-3">
                        <div data-staff-dropzone class="banner-dropzone is-empty cursor-pointer">
                            <div data-staff-placeholder class="banner-dropzone-placeholder min-h-[7.5rem] flex-col items-center justify-center px-4 text-center">
                                <div class="banner-dropzone-main">
                                    <svg class="banner-dropzone-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <rect x="3.5" y="5.5" width="17" height="13" rx="2" stroke="currentColor" stroke-width="1.5"/>
                                        <circle cx="9" cy="10.5" r="1.5" fill="currentColor" opacity="0.7"/>
                                        <path d="M5.5 16.5l4-3.5 2.5 2 3.5-3.5 3 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <p class="banner-dropzone-text text-sm text-gray-700">写真をドラッグ＆ドロップ、またはクリックして選択</p>
                                </div>
                                <p class="banner-dropzone-hint mt-2 text-xs text-gray-500">JPEG / PNG / WebP、5MBまで</p>
                            </div>
                            <div data-staff-preview class="hidden"></div>
                            <p class="banner-dropzone-drag-message" aria-hidden="true">ここに写真をドロップしてください</p>
                        </div>
                        <input
                            type="file"
                            name="new_staff[{{ $key }}][photo]"
                            accept="image/jpeg,image/png,image/webp"
                            class="hidden"
                            data-staff-file
                        >
                        <p class="mt-1 hidden text-sm text-red-600" data-staff-photo-error role="alert"></p>
                        @error($prefix.'.photo')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label class="admin-label">名前 <span class="admin-required-badge">必須</span></label>
                            <input
                                type="text"
                                name="new_staff[{{ $key }}][name]"
                                value="{{ $cardName }}"
                                maxlength="255"
                                required
                                class="admin-input"
                                data-staff-name-input
                            >
                            @error($prefix.'.name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="admin-label">役職・担当</label>
                            <input
                                type="text"
                                name="new_staff[{{ $key }}][role]"
                                value="{{ $role }}"
                                maxlength="255"
                                class="admin-input"
                            >
                        </div>
                        <div>
                            <label class="admin-label">プロフィール</label>
                            <textarea
                                name="new_staff[{{ $key }}][profile]"
                                rows="4"
                                class="admin-input"
                            >{{ $profile }}</textarea>
                        </div>
                        <div>
                            <span class="admin-label">公開</span>
                            <div class="admin-segmented mt-1" role="radiogroup" aria-label="公開状態">
                                <label class="admin-segmented-option">
                                    <input type="radio" name="new_staff[{{ $key }}][is_published]" value="1" class="admin-segmented-input" @checked($isPublished)>
                                    <span class="admin-segmented-face">
                                        <svg class="admin-segmented-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                            <path d="M1.5 8s2.5-4.5 6.5-4.5S14.5 8 14.5 8s-2.5 4.5-6.5 4.5S1.5 8 1.5 8z" stroke="currentColor" stroke-width="1.35" stroke-linejoin="round"/>
                                            <circle cx="8" cy="8" r="2" stroke="currentColor" stroke-width="1.35"/>
                                        </svg>
                                        <span class="admin-segmented-text">公開</span>
                                    </span>
                                </label>
                                <label class="admin-segmented-option">
                                    <input type="radio" name="new_staff[{{ $key }}][is_published]" value="0" class="admin-segmented-input" @checked(!$isPublished)>
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
                id="staff-add-card"
                class="admin-card flex min-h-[22rem] w-full flex-col items-center justify-center px-6 py-10 text-center"
            >
                <div class="admin-empty-state-icon !mb-4" aria-hidden="true">
                    <svg class="h-14 w-14" viewBox="0 0 80 80" fill="none" stroke="#B8B09F" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M40 58V36" stroke-width="1.3"/>
                        <path d="M40 42c6-3 11-8 13-14" stroke-width="1.25" opacity="0.75"/>
                        <path d="M40 48c-6-2.5-10-7-12-12" stroke-width="1.25" opacity="0.75"/>
                        <path d="M40 36c-5-8-3-16 2-20 6 2 10 9 8 16-2 3-5 4-10 4Z" stroke-width="1.3" opacity="0.65"/>
                        <path d="M40 36c5-8 3-16-2-20-6 2-10 9-8 16 2 3 5 4 10 4Z" stroke-width="1.3" opacity="0.65"/>
                        <circle cx="32" cy="50" r="5.5" stroke-width="1.35"/>
                        <path d="M22 66c1.5-6 5-10 10-10s8.5 4 10 10" stroke-width="1.35"/>
                        <circle cx="52" cy="48" r="4.5" stroke-width="1.3" opacity="0.85"/>
                        <path d="M44 66c1-5 4-8.5 8-8.5s7 3.5 8 8.5" stroke-width="1.3" opacity="0.85"/>
                    </svg>
                </div>
                <x-admin.create-button data-staff-add>
                    スタッフを追加
                </x-admin.create-button>
                <p class="mt-3 text-xs text-admin-muted">カードを追加し、保存で登録できます。</p>
            </div>
        </div>
    </form>

    <style>
        .staff-drag-handle {
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
        .staff-drag-handle:hover,
        .staff-drag-handle:focus-visible {
            color: #556344;
        }
        .staff-drag-handle:focus {
            outline: none;
        }
        .staff-drag-handle:focus-visible {
            box-shadow: inset 0 0 0 2px rgba(105, 122, 85, 0.35);
            border-radius: 0.25rem;
        }
        .staff-drag-handle:active,
        .staff-card.is-dragging .staff-drag-handle {
            cursor: grabbing;
        }
        .staff-card.is-dragging {
            opacity: 0.55;
        }
        .staff-card.is-drag-over {
            outline: 2px dashed rgba(105, 122, 85, 0.45);
            outline-offset: 2px;
        }
    </style>

    <script>
        (function () {
            const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            const maxSize = 5 * 1024 * 1024;
            const form = document.getElementById('staff-bulk-form');
            const grid = document.getElementById('staff-grid');
            const deletedIdsWrap = document.getElementById('staff-deleted-ids');
            const addCard = document.getElementById('staff-add-card');
            const addButton = addCard ? addCard.querySelector('[data-staff-add]') : null;
            const emptyHeading = '新規スタッフ';
            const dropzoneMainHtml =
                '<div class="banner-dropzone-main">' +
                    '<svg class="banner-dropzone-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">' +
                        '<rect x="3.5" y="5.5" width="17" height="13" rx="2" stroke="currentColor" stroke-width="1.5"/>' +
                        '<circle cx="9" cy="10.5" r="1.5" fill="currentColor" opacity="0.7"/>' +
                        '<path d="M5.5 16.5l4-3.5 2.5 2 3.5-3.5 3 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>' +
                    '</svg>' +
                    '<p class="banner-dropzone-text text-sm text-gray-700">写真をドラッグ＆ドロップ、またはクリックして選択</p>' +
                '</div>';

            if (!form || !grid || !addCard || !addButton) {
                return;
            }

            let nextNewIndex = parseInt(form.getAttribute('data-next-new-index') || '1', 10);
            let maxOrder = parseInt(form.getAttribute('data-max-order') || '0', 10);
            let dragCard = null;

            function showCardPhotoError(card, message) {
                const errorEl = card.querySelector('[data-staff-photo-error]');
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
                    return '写真を選択してください。';
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
                const preview = card.querySelector('[data-staff-preview]');
                const placeholder = card.querySelector('[data-staff-placeholder]');
                const dropzone = card.querySelector('[data-staff-dropzone]');
                if (!preview || !placeholder) {
                    return;
                }

                function showImage(src) {
                    let img = preview.querySelector('[data-staff-image]');
                    if (!img) {
                        img = document.createElement('img');
                        img.setAttribute('data-staff-image', '');
                        img.alt = '';
                        img.className = 'aspect-square w-full object-cover';
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
                const input = card.querySelector('[data-staff-file]');
                const error = isValidImage(file);
                if (error) {
                    if (input) {
                        input.value = '';
                    }
                    showCardPhotoError(card, error);
                    return;
                }
                showCardPhotoError(card, '');
                if (input) {
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    input.files = dt.files;
                }
                setCardPreview(card, file);
            }

            function syncCardHeading(card) {
                const label = card.querySelector('[data-staff-card-title]');
                const input = card.querySelector('[data-staff-name-input]');
                if (!label || !input) {
                    return;
                }
                const value = (input.value || '').trim();
                const text = value !== '' ? value : emptyHeading;
                label.textContent = text;
                label.setAttribute('title', text);
            }

            function syncDisplayOrders() {
                grid.querySelectorAll('[data-staff-card]').forEach(function (card, index) {
                    const orderInput = card.querySelector('[data-staff-order]');
                    if (orderInput) {
                        orderInput.value = String(index + 1);
                    }
                });
                maxOrder = grid.querySelectorAll('[data-staff-card]').length;
                form.setAttribute('data-max-order', String(maxOrder));
            }

            function clearDropzoneDragState(dropzone) {
                if (!dropzone) {
                    return;
                }
                dropzone._staffDragCounter = 0;
                dropzone.classList.remove('is-drag-active');
            }

            function bindCard(card) {
                const dropzone = card.querySelector('[data-staff-dropzone]');
                const input = card.querySelector('[data-staff-file]');
                const removeBtn = card.querySelector('[data-staff-remove]');
                const nameInput = card.querySelector('[data-staff-name-input]');

                if (dropzone) {
                    dropzone._staffDragCounter = 0;
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
                    dropzone._staffDragCounter = (dropzone._staffDragCounter || 0) + 1;
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
                    dropzone._staffDragCounter = Math.max(0, (dropzone._staffDragCounter || 0) - 1);
                    if (dropzone._staffDragCounter === 0) {
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

                nameInput?.addEventListener('input', function () {
                    syncCardHeading(card);
                });
                syncCardHeading(card);

                removeBtn?.addEventListener('click', function () {
                    const existingId = card.getAttribute('data-staff-id');
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

                const order = grid.querySelectorAll('[data-staff-card]').length + 1;
                maxOrder = Math.max(maxOrder, order);
                form.setAttribute('data-max-order', String(maxOrder));

                const card = document.createElement('div');
                card.className = 'admin-card staff-card';
                card.setAttribute('data-staff-card', '');
                card.setAttribute('data-staff-new', '1');
                card.innerHTML =
                    '<div class="mb-3 flex items-center justify-between gap-3">' +
                        '<div class="flex min-w-0 items-center gap-2">' +
                            '<span class="staff-drag-handle" data-staff-drag-handle draggable="true" role="button" tabindex="0" aria-label="スタッフを並び替え" title="ドラッグして並び替え">' +
                                '<svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">' +
                                    '<circle cx="7" cy="5" r="1.25"/><circle cx="13" cy="5" r="1.25"/>' +
                                    '<circle cx="7" cy="10" r="1.25"/><circle cx="13" cy="10" r="1.25"/>' +
                                    '<circle cx="7" cy="15" r="1.25"/><circle cx="13" cy="15" r="1.25"/>' +
                                '</svg>' +
                            '</span>' +
                            '<p class="banner-card-label truncate text-sm font-medium text-gray-800" data-staff-card-title title="' + emptyHeading + '">' + emptyHeading + '</p>' +
                        '</div>' +
                        '<button type="button" class="admin-icon-btn admin-icon-btn-delete" data-staff-remove aria-label="削除" title="削除">' +
                            '<span aria-hidden="true">&times;</span>' +
                        '</button>' +
                    '</div>' +
                    '<input type="hidden" name="new_staff[' + key + '][sort_order]" value="' + order + '" data-staff-order>' +
                    '<div class="mb-3">' +
                        '<div data-staff-dropzone class="banner-dropzone is-empty cursor-pointer">' +
                            '<div data-staff-placeholder class="banner-dropzone-placeholder">' +
                                dropzoneMainHtml +
                                '<p class="banner-dropzone-hint mt-2 text-xs text-gray-500">JPEG / PNG / WebP、5MBまで</p>' +
                            '</div>' +
                            '<div data-staff-preview class="hidden"></div>' +
                            '<p class="banner-dropzone-drag-message" aria-hidden="true">ここに写真をドロップしてください</p>' +
                        '</div>' +
                        '<input type="file" name="new_staff[' + key + '][photo]" accept="image/jpeg,image/png,image/webp" class="hidden" data-staff-file>' +
                        '<p class="mt-1 hidden text-sm text-red-600" data-staff-photo-error role="alert"></p>' +
                    '</div>' +
                    '<div class="space-y-3">' +
                        '<div>' +
                            '<label for="staff_new_name_' + key + '" class="admin-label">名前 <span class="admin-required-badge">必須</span></label>' +
                            '<input type="text" name="new_staff[' + key + '][name]" id="staff_new_name_' + key + '" value="" maxlength="255" required class="admin-input" data-staff-name-input>' +
                        '</div>' +
                        '<div>' +
                            '<label for="staff_new_role_' + key + '" class="admin-label">役職・担当</label>' +
                            '<input type="text" name="new_staff[' + key + '][role]" id="staff_new_role_' + key + '" value="" maxlength="255" class="admin-input">' +
                        '</div>' +
                        '<div>' +
                            '<label for="staff_new_profile_' + key + '" class="admin-label">プロフィール</label>' +
                            '<textarea name="new_staff[' + key + '][profile]" id="staff_new_profile_' + key + '" rows="4" class="admin-input"></textarea>' +
                        '</div>' +
                        '<div>' +
                            '<span class="admin-label">公開</span>' +
                            '<div class="admin-segmented mt-1" role="radiogroup" aria-label="公開状態">' +
                                '<label class="admin-segmented-option">' +
                                    '<input type="radio" name="new_staff[' + key + '][is_published]" value="1" class="admin-segmented-input" checked>' +
                                    '<span class="admin-segmented-face">' +
                                        '<svg class="admin-segmented-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">' +
                                            '<path d="M1.5 8s2.5-4.5 6.5-4.5S14.5 8 14.5 8s-2.5 4.5-6.5 4.5S1.5 8 1.5 8z" stroke="currentColor" stroke-width="1.35" stroke-linejoin="round"/>' +
                                            '<circle cx="8" cy="8" r="2" stroke="currentColor" stroke-width="1.35"/>' +
                                        '</svg>' +
                                        '<span class="admin-segmented-text">公開</span>' +
                                    '</span>' +
                                '</label>' +
                                '<label class="admin-segmented-option">' +
                                    '<input type="radio" name="new_staff[' + key + '][is_published]" value="0" class="admin-segmented-input">' +
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
                const handle = e.target.closest('[data-staff-drag-handle]');
                if (!handle || !grid.contains(handle)) {
                    return;
                }
                const card = handle.closest('[data-staff-card]');
                if (!card) {
                    e.preventDefault();
                    return;
                }
                dragCard = card;
                card.classList.add('is-dragging');
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', card.getAttribute('data-staff-id') || 'new');
            });

            grid.addEventListener('dragend', function () {
                if (dragCard) {
                    dragCard.classList.remove('is-dragging');
                }
                grid.querySelectorAll('.is-drag-over').forEach(function (el) {
                    el.classList.remove('is-drag-over');
                });
                grid.querySelectorAll('[data-staff-dropzone]').forEach(clearDropzoneDragState);
                dragCard = null;
                syncDisplayOrders();
            });

            grid.addEventListener('dragover', function (e) {
                if (!dragCard) {
                    return;
                }
                e.preventDefault();
                const over = e.target.closest('[data-staff-card]');
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

            grid.querySelectorAll('[data-staff-card]').forEach(bindCard);
            syncDisplayOrders();

            addButton.addEventListener('click', function (e) {
                e.preventDefault();
                createEmptyCard();
            });
        })();
    </script>
@endsection
