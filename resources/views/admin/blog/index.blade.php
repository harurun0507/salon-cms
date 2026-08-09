@extends('layouts.admin')

@section('heading', 'ブログ')

@section('save-bar')
    <div class="flex min-w-0 flex-wrap items-center gap-3">
        <button
            type="button"
            class="admin-btn shadow-md shrink-0"
            data-admin-confirm-trigger
            data-confirm-form="blog-bulk-form"
            data-confirm-title="ブログ保存の確認"
            data-confirm-message="変更内容を保存します。&#10;よろしいですか？"
            data-confirm-note="アイキャッチ画像、タイトル、本文、投稿日、公開状態、表示順、削除など、現在入力されている内容が反映されます。"
            data-confirm-submit-label="保存する"
        >保存する</button>
        <p class="text-sm text-admin-muted">
            公開サイトに表示するブログ記事を登録・編集します。アイキャッチ画像は任意です。
        </p>
    </div>

@endsection

@section('content')
    @php
        $maxOrder = (int) ($blogs->max('display_order') ?? 0);

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

        $oldNewBlogs = old('new_blogs', []);
        if (! is_array($oldNewBlogs)) {
            $oldNewBlogs = [];
        }
        $nextNewIndex = 1;
        foreach (array_keys($oldNewBlogs) as $key) {
            if (preg_match('/^new_(\d+)$/', (string) $key, $m)) {
                $nextNewIndex = max($nextNewIndex, ((int) $m[1]) + 1);
            }
        }
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
        action="{{ route('admin.blog.update') }}"
        id="blog-bulk-form"
        enctype="multipart/form-data"
        data-blog-workspace
        data-next-new-index="{{ $nextNewIndex }}"
        data-max-order="{{ $maxOrder }}"
    >
        @csrf
        @method('PUT')

        <div id="blog-deleted-ids"></div>

        <div id="blog-grid" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3" data-blog-grid>
            @foreach($blogs as $blog)
                @php
                    $prefix = 'blogs.'.$blog->id;
                    $publishedOld = old($prefix.'.is_published', $blog->is_published ? '1' : '0');
                    $isPublished = in_array((string) $publishedOld, ['1', 'true', 'on'], true);
                    $publishedAt = old($prefix.'.published_at', $formatLocal($blog->published_at));
                    $displayOrder = old($prefix.'.display_order', $blog->display_order);
                    $cardTitle = trim((string) old($prefix.'.title', $blog->title));
                    $headingTitle = $cardTitle !== '' ? $cardTitle : '新規ブログ';
                @endphp
                <div
                    class="admin-card blog-card"
                    data-blog-card
                    data-blog-id="{{ $blog->id }}"
                    data-blog-existing
                >
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-2">
                            <span
                                class="blog-drag-handle"
                                data-blog-drag-handle
                                draggable="true"
                                role="button"
                                tabindex="0"
                                aria-label="ブログを並び替え"
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
                            <p class="blog-card-label truncate text-sm font-medium text-gray-800" data-blog-card-title title="{{ $headingTitle }}">{{ $headingTitle }}</p>
                        </div>
                        <button
                            type="button"
                            class="admin-icon-btn admin-icon-btn-delete"
                            data-blog-remove
                            aria-label="削除"
                            title="削除"
                        >
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <input type="hidden" name="blogs[{{ $blog->id }}][display_order]" value="{{ $displayOrder }}" data-blog-order>
                    <input type="hidden" name="blogs[{{ $blog->id }}][remove_eye_catch]" value="0" data-blog-remove-eye-catch>

                    <div class="mb-3">
                        <span class="admin-label">アイキャッチ画像</span>
                        <div data-blog-dropzone class="banner-dropzone cursor-pointer overflow-hidden rounded-lg {{ $blog->eye_catch_image_path ? '' : 'is-empty' }}">
                            <div data-blog-preview class="{{ $blog->eye_catch_image_path ? '' : 'hidden' }}">
                                @if($blog->eye_catch_image_path)
                                    <img
                                        src="{{ asset('storage/'.$blog->eye_catch_image_path) }}"
                                        alt=""
                                        class="aspect-[16/10] w-full object-cover"
                                        data-blog-image
                                    >
                                @endif
                            </div>
                            <div data-blog-placeholder class="banner-dropzone-placeholder {{ $blog->eye_catch_image_path ? 'hidden' : '' }} min-h-[7.5rem] flex-col items-center justify-center px-4 text-center">
                                <div class="banner-dropzone-main">
                                    <svg class="banner-dropzone-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <rect x="3.5" y="5.5" width="17" height="13" rx="2" stroke="currentColor" stroke-width="1.5"/>
                                        <circle cx="9" cy="10.5" r="1.5" fill="currentColor" opacity="0.7"/>
                                        <path d="M5.5 16.5l4-3.5 2.5 2 3.5-3.5 3 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <p class="banner-dropzone-text text-sm text-gray-700">画像をドラッグ＆ドロップ、またはクリックして選択</p>
                                </div>
                                <p class="banner-dropzone-hint mt-2 text-xs text-gray-500">任意・JPEG / PNG / WebP、5MBまで</p>
                            </div>
                            <p class="banner-dropzone-drag-message" aria-hidden="true">ここに画像をドロップしてください</p>
                        </div>
                        <input
                            type="file"
                            name="blogs[{{ $blog->id }}][eye_catch]"
                            accept="image/jpeg,image/png,image/webp"
                            class="hidden"
                            data-blog-file
                        >
                        <div class="mt-2 flex flex-wrap items-center gap-3">
                            <p class="text-xs text-admin-muted">未登録でも保存できます</p>
                            <button
                                type="button"
                                class="text-xs text-admin-muted underline decoration-admin-border underline-offset-2 hover:text-admin-text {{ $blog->eye_catch_image_path ? '' : 'hidden' }}"
                                data-blog-clear-eye-catch
                            >画像を削除</button>
                        </div>
                        <p class="mt-1 hidden text-sm text-red-600" data-blog-image-error role="alert"></p>
                        @error($prefix.'.eye_catch')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label for="blog_title_{{ $blog->id }}" class="admin-label">タイトル <span class="admin-required-badge">必須</span></label>
                            <input
                                type="text"
                                name="blogs[{{ $blog->id }}][title]"
                                id="blog_title_{{ $blog->id }}"
                                value="{{ old($prefix.'.title', $blog->title) }}"
                                maxlength="255"
                                required
                                class="admin-input"
                                data-blog-title-input
                            >
                            @error($prefix.'.title')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="blog_body_{{ $blog->id }}" class="admin-label">本文 <span class="admin-required-badge">必須</span></label>
                            <textarea
                                name="blogs[{{ $blog->id }}][body]"
                                id="blog_body_{{ $blog->id }}"
                                rows="6"
                                required
                                class="admin-input"
                            >{{ old($prefix.'.body', $blog->body) }}</textarea>
                            @error($prefix.'.body')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="blog_published_at_{{ $blog->id }}" class="admin-label">投稿日 <span class="admin-required-badge">必須</span></label>
                            <input
                                type="datetime-local"
                                name="blogs[{{ $blog->id }}][published_at]"
                                id="blog_published_at_{{ $blog->id }}"
                                value="{{ $publishedAt }}"
                                required
                                class="admin-input"
                            >
                            <p class="mt-1 text-xs text-admin-muted">公開判定に使います（投稿日が未来の場合は表示されません）</p>
                            @error($prefix.'.published_at')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <span class="admin-label">公開</span>
                            <div class="admin-segmented mt-1" role="radiogroup" aria-label="公開状態">
                                <label class="admin-segmented-option">
                                    <input type="radio" name="blogs[{{ $blog->id }}][is_published]" value="1" class="admin-segmented-input" @checked($isPublished)>
                                    <span class="admin-segmented-face">
                                        <svg class="admin-segmented-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                            <path d="M1.5 8s2.5-4.5 6.5-4.5S14.5 8 14.5 8s-2.5 4.5-6.5 4.5S1.5 8 1.5 8z" stroke="currentColor" stroke-width="1.35" stroke-linejoin="round"/>
                                            <circle cx="8" cy="8" r="2" stroke="currentColor" stroke-width="1.35"/>
                                        </svg>
                                        <span class="admin-segmented-text">公開</span>
                                    </span>
                                </label>
                                <label class="admin-segmented-option">
                                    <input type="radio" name="blogs[{{ $blog->id }}][is_published]" value="0" class="admin-segmented-input" @checked(!$isPublished)>
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

            @foreach($oldNewBlogs as $key => $newItem)
                @php
                    if (! is_array($newItem)) {
                        continue;
                    }
                    $prefix = 'new_blogs.'.$key;
                    $publishedOld = old($prefix.'.is_published', $newItem['is_published'] ?? '1');
                    $isPublished = in_array((string) $publishedOld, ['1', 'true', 'on'], true);
                    $publishedAt = old($prefix.'.published_at', $newItem['published_at'] ?? '');
                    $displayOrder = old($prefix.'.display_order', $newItem['display_order'] ?? 0);
                    $cardTitle = trim((string) old($prefix.'.title', $newItem['title'] ?? ''));
                    $headingTitle = $cardTitle !== '' ? $cardTitle : '新規ブログ';
                    $body = old($prefix.'.body', $newItem['body'] ?? '');
                @endphp
                <div
                    class="admin-card blog-card"
                    data-blog-card
                    data-blog-new="1"
                >
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-2">
                            <span
                                class="blog-drag-handle"
                                data-blog-drag-handle
                                draggable="true"
                                role="button"
                                tabindex="0"
                                aria-label="ブログを並び替え"
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
                            <p class="blog-card-label truncate text-sm font-medium text-gray-800" data-blog-card-title title="{{ $headingTitle }}">{{ $headingTitle }}</p>
                        </div>
                        <button
                            type="button"
                            class="admin-icon-btn admin-icon-btn-delete"
                            data-blog-remove
                            aria-label="削除"
                            title="削除"
                        >
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <input type="hidden" name="new_blogs[{{ $key }}][display_order]" value="{{ $displayOrder }}" data-blog-order>

                    <div class="mb-3">
                        <span class="admin-label">アイキャッチ画像</span>
                        <div data-blog-dropzone class="banner-dropzone is-empty cursor-pointer overflow-hidden rounded-lg">
                            <div data-blog-preview class="hidden"></div>
                            <div data-blog-placeholder class="banner-dropzone-placeholder min-h-[7.5rem] flex-col items-center justify-center px-4 text-center">
                                <div class="banner-dropzone-main">
                                    <svg class="banner-dropzone-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <rect x="3.5" y="5.5" width="17" height="13" rx="2" stroke="currentColor" stroke-width="1.5"/>
                                        <circle cx="9" cy="10.5" r="1.5" fill="currentColor" opacity="0.7"/>
                                        <path d="M5.5 16.5l4-3.5 2.5 2 3.5-3.5 3 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <p class="banner-dropzone-text text-sm text-gray-700">画像をドラッグ＆ドロップ、またはクリックして選択</p>
                                </div>
                                <p class="banner-dropzone-hint mt-2 text-xs text-gray-500">任意・JPEG / PNG / WebP、5MBまで</p>
                            </div>
                            <p class="banner-dropzone-drag-message" aria-hidden="true">ここに画像をドロップしてください</p>
                        </div>
                        <input
                            type="file"
                            name="new_blogs[{{ $key }}][eye_catch]"
                            accept="image/jpeg,image/png,image/webp"
                            class="hidden"
                            data-blog-file
                        >
                        <div class="mt-2 flex flex-wrap items-center gap-3">
                            <p class="text-xs text-admin-muted">未登録でも保存できます</p>
                            <button type="button" class="hidden text-xs text-admin-muted underline decoration-admin-border underline-offset-2 hover:text-admin-text" data-blog-clear-eye-catch>画像を削除</button>
                        </div>
                        <p class="mt-1 hidden text-sm text-red-600" data-blog-image-error role="alert"></p>
                        @error($prefix.'.eye_catch')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label class="admin-label">タイトル <span class="admin-required-badge">必須</span></label>
                            <input
                                type="text"
                                name="new_blogs[{{ $key }}][title]"
                                value="{{ $cardTitle }}"
                                maxlength="255"
                                required
                                class="admin-input"
                                data-blog-title-input
                            >
                            @error($prefix.'.title')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="admin-label">本文 <span class="admin-required-badge">必須</span></label>
                            <textarea
                                name="new_blogs[{{ $key }}][body]"
                                rows="6"
                                required
                                class="admin-input"
                            >{{ $body }}</textarea>
                            @error($prefix.'.body')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="admin-label">投稿日 <span class="admin-required-badge">必須</span></label>
                            <input
                                type="datetime-local"
                                name="new_blogs[{{ $key }}][published_at]"
                                value="{{ $publishedAt }}"
                                required
                                class="admin-input"
                            >
                            <p class="mt-1 text-xs text-admin-muted">公開判定に使います（投稿日が未来の場合は表示されません）</p>
                            @error($prefix.'.published_at')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <span class="admin-label">公開</span>
                            <div class="admin-segmented mt-1" role="radiogroup" aria-label="公開状態">
                                <label class="admin-segmented-option">
                                    <input type="radio" name="new_blogs[{{ $key }}][is_published]" value="1" class="admin-segmented-input" @checked($isPublished)>
                                    <span class="admin-segmented-face">
                                        <svg class="admin-segmented-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                            <path d="M1.5 8s2.5-4.5 6.5-4.5S14.5 8 14.5 8s-2.5 4.5-6.5 4.5S1.5 8 1.5 8z" stroke="currentColor" stroke-width="1.35" stroke-linejoin="round"/>
                                            <circle cx="8" cy="8" r="2" stroke="currentColor" stroke-width="1.35"/>
                                        </svg>
                                        <span class="admin-segmented-text">公開</span>
                                    </span>
                                </label>
                                <label class="admin-segmented-option">
                                    <input type="radio" name="new_blogs[{{ $key }}][is_published]" value="0" class="admin-segmented-input" @checked(!$isPublished)>
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
                id="blog-add-card"
                class="admin-card flex min-h-[22rem] w-full flex-col items-center justify-center px-6 py-10 text-center"
            >
                <div class="admin-empty-state-icon !mb-4" aria-hidden="true">
                    <svg class="h-14 w-14" viewBox="0 0 80 80" fill="none" stroke="#B8B09F" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 20h36a4 4 0 0 1 4 4v36a4 4 0 0 1-4 4H22a4 4 0 0 1-4-4V24a4 4 0 0 1 4-4z" stroke-width="1.4"/>
                        <path d="M28 32h24M28 40h18M28 48h12" stroke-width="1.3" opacity="0.75"/>
                        <circle cx="56" cy="24" r="8" stroke-width="1.3" opacity="0.65"/>
                        <path d="M56 20v8M52 24h8" stroke-width="1.3" opacity="0.65"/>
                    </svg>
                </div>
                <x-admin.create-button data-blog-add>
                    ブログを追加
                </x-admin.create-button>
                <p class="mt-3 text-xs text-admin-muted">カードを追加し、保存で登録できます。</p>
            </div>
        </div>
    </form>

    <style>
        .blog-drag-handle {
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
        .blog-drag-handle:hover,
        .blog-drag-handle:focus-visible {
            color: #556344;
        }
        .blog-drag-handle:focus {
            outline: none;
        }
        .blog-drag-handle:focus-visible {
            box-shadow: inset 0 0 0 2px rgba(105, 122, 85, 0.35);
            border-radius: 0.25rem;
        }
        .blog-drag-handle:active,
        .blog-card.is-dragging .blog-drag-handle {
            cursor: grabbing;
        }
        .blog-card.is-dragging {
            opacity: 0.55;
        }
        .blog-card.is-drag-over {
            outline: 2px dashed rgba(105, 122, 85, 0.45);
            outline-offset: 2px;
        }
    </style>

    <script>
        (function () {
            const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            const maxSize = 5 * 1024 * 1024;
            const form = document.getElementById('blog-bulk-form');
            const grid = document.getElementById('blog-grid');
            const deletedIdsWrap = document.getElementById('blog-deleted-ids');
            const addCard = document.getElementById('blog-add-card');
            const addButton = addCard ? addCard.querySelector('[data-blog-add]') : null;
            const emptyHeading = '新規ブログ';
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
                const errorEl = card.querySelector('[data-blog-image-error]');
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
                const preview = card.querySelector('[data-blog-preview]');
                const placeholder = card.querySelector('[data-blog-placeholder]');
                const dropzone = card.querySelector('[data-blog-dropzone]');
                const clearBtn = card.querySelector('[data-blog-clear-eye-catch]');
                if (!preview || !placeholder) {
                    return;
                }

                function showImage(src) {
                    let img = preview.querySelector('[data-blog-image]');
                    if (!img) {
                        img = document.createElement('img');
                        img.setAttribute('data-blog-image', '');
                        img.alt = '';
                        img.className = 'aspect-[16/10] w-full object-cover';
                        preview.appendChild(img);
                    }
                    img.src = src;
                    placeholder.classList.add('hidden');
                    preview.classList.remove('hidden');
                    clearBtn?.classList.remove('hidden');
                    if (dropzone) {
                        dropzone.classList.remove('is-empty');
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

            function clearCardPreview(card) {
                const preview = card.querySelector('[data-blog-preview]');
                const placeholder = card.querySelector('[data-blog-placeholder]');
                const dropzone = card.querySelector('[data-blog-dropzone]');
                const clearBtn = card.querySelector('[data-blog-clear-eye-catch]');
                const input = card.querySelector('[data-blog-file]');
                const removeFlag = card.querySelector('[data-blog-remove-eye-catch]');
                if (preview) {
                    preview.innerHTML = '';
                    preview.classList.add('hidden');
                }
                placeholder?.classList.remove('hidden');
                dropzone?.classList.add('is-empty');
                clearBtn?.classList.add('hidden');
                if (input) {
                    input.value = '';
                }
                if (removeFlag) {
                    removeFlag.value = '1';
                }
            }

            function applyFile(card, file) {
                const input = card.querySelector('[data-blog-file]');
                const removeFlag = card.querySelector('[data-blog-remove-eye-catch]');
                const error = isValidImage(file);
                if (error) {
                    if (input) {
                        input.value = '';
                    }
                    showCardImageError(card, error);
                    return;
                }
                showCardImageError(card, '');
                if (removeFlag) {
                    removeFlag.value = '0';
                }
                if (input) {
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    input.files = dt.files;
                }
                setCardPreview(card, file);
            }

            function syncCardHeading(card) {
                const label = card.querySelector('[data-blog-card-title]');
                const input = card.querySelector('[data-blog-title-input]');
                if (!label || !input) {
                    return;
                }
                const value = (input.value || '').trim();
                const text = value !== '' ? value : emptyHeading;
                label.textContent = text;
                label.setAttribute('title', text);
            }

            function syncDisplayOrders() {
                grid.querySelectorAll('[data-blog-card]').forEach(function (card, index) {
                    const orderInput = card.querySelector('[data-blog-order]');
                    if (orderInput) {
                        orderInput.value = String(index + 1);
                    }
                });
                maxOrder = grid.querySelectorAll('[data-blog-card]').length;
                form.setAttribute('data-max-order', String(maxOrder));
            }

            function clearDropzoneDragState(dropzone) {
                if (!dropzone) {
                    return;
                }
                dropzone._blogDragCounter = 0;
                dropzone.classList.remove('is-drag-active');
            }

            function bindCard(card) {
                const removeBtn = card.querySelector('[data-blog-remove]');
                const titleInput = card.querySelector('[data-blog-title-input]');
                const dropzone = card.querySelector('[data-blog-dropzone]');
                const input = card.querySelector('[data-blog-file]');
                const clearBtn = card.querySelector('[data-blog-clear-eye-catch]');

                if (dropzone) {
                    dropzone._blogDragCounter = 0;
                }

                titleInput?.addEventListener('input', function () {
                    syncCardHeading(card);
                });
                syncCardHeading(card);

                dropzone?.addEventListener('click', function () {
                    input?.click();
                });
                input?.addEventListener('change', function () {
                    const file = input.files && input.files[0];
                    if (file) {
                        applyFile(card, file);
                    }
                });
                dropzone?.addEventListener('dragenter', function (e) {
                    e.preventDefault();
                    dropzone._blogDragCounter = (dropzone._blogDragCounter || 0) + 1;
                    dropzone.classList.add('is-drag-active');
                });
                dropzone?.addEventListener('dragleave', function (e) {
                    e.preventDefault();
                    dropzone._blogDragCounter = Math.max(0, (dropzone._blogDragCounter || 0) - 1);
                    if (dropzone._blogDragCounter === 0) {
                        dropzone.classList.remove('is-drag-active');
                    }
                });
                dropzone?.addEventListener('dragover', function (e) {
                    e.preventDefault();
                });
                dropzone?.addEventListener('drop', function (e) {
                    e.preventDefault();
                    clearDropzoneDragState(dropzone);
                    const file = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
                    if (file) {
                        applyFile(card, file);
                    }
                });
                clearBtn?.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    clearCardPreview(card);
                    showCardImageError(card, '');
                });

                removeBtn?.addEventListener('click', function () {
                    const existingId = card.getAttribute('data-blog-id');
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

                const order = grid.querySelectorAll('[data-blog-card]').length + 1;
                maxOrder = Math.max(maxOrder, order);
                form.setAttribute('data-max-order', String(maxOrder));

                const card = document.createElement('div');
                card.className = 'admin-card blog-card';
                card.setAttribute('data-blog-card', '');
                card.setAttribute('data-blog-new', '1');
                card.innerHTML =
                    '<div class="mb-3 flex items-center justify-between gap-3">' +
                        '<div class="flex min-w-0 items-center gap-2">' +
                            '<span class="blog-drag-handle" data-blog-drag-handle draggable="true" role="button" tabindex="0" aria-label="ブログを並び替え" title="ドラッグして並び替え">' +
                                '<svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">' +
                                    '<circle cx="7" cy="5" r="1.25"/><circle cx="13" cy="5" r="1.25"/>' +
                                    '<circle cx="7" cy="10" r="1.25"/><circle cx="13" cy="10" r="1.25"/>' +
                                    '<circle cx="7" cy="15" r="1.25"/><circle cx="13" cy="15" r="1.25"/>' +
                                '</svg>' +
                            '</span>' +
                            '<p class="blog-card-label truncate text-sm font-medium text-gray-800" data-blog-card-title title="' + emptyHeading + '">' + emptyHeading + '</p>' +
                        '</div>' +
                        '<button type="button" class="admin-icon-btn admin-icon-btn-delete" data-blog-remove aria-label="削除" title="削除">' +
                            '<span aria-hidden="true">&times;</span>' +
                        '</button>' +
                    '</div>' +
                    '<input type="hidden" name="new_blogs[' + key + '][display_order]" value="' + order + '" data-blog-order>' +
                    '<div class="mb-3">' +
                        '<span class="admin-label">アイキャッチ画像</span>' +
                        '<div data-blog-dropzone class="banner-dropzone is-empty cursor-pointer overflow-hidden rounded-lg">' +
                            '<div data-blog-preview class="hidden"></div>' +
                            '<div data-blog-placeholder class="banner-dropzone-placeholder min-h-[7.5rem] flex-col items-center justify-center px-4 text-center">' +
                                dropzoneMainHtml +
                                '<p class="banner-dropzone-hint mt-2 text-xs text-gray-500">任意・JPEG / PNG / WebP、5MBまで</p>' +
                            '</div>' +
                            '<p class="banner-dropzone-drag-message" aria-hidden="true">ここに画像をドロップしてください</p>' +
                        '</div>' +
                        '<input type="file" name="new_blogs[' + key + '][eye_catch]" accept="image/jpeg,image/png,image/webp" class="hidden" data-blog-file>' +
                        '<div class="mt-2 flex flex-wrap items-center gap-3">' +
                            '<p class="text-xs text-admin-muted">未登録でも保存できます</p>' +
                            '<button type="button" class="hidden text-xs text-admin-muted underline decoration-admin-border underline-offset-2 hover:text-admin-text" data-blog-clear-eye-catch>画像を削除</button>' +
                        '</div>' +
                        '<p class="mt-1 hidden text-sm text-red-600" data-blog-image-error role="alert"></p>' +
                    '</div>' +
                    '<div class="space-y-3">' +
                        '<div>' +
                            '<label class="admin-label">タイトル <span class="admin-required-badge">必須</span></label>' +
                            '<input type="text" name="new_blogs[' + key + '][title]" value="" maxlength="255" required class="admin-input" data-blog-title-input>' +
                        '</div>' +
                        '<div>' +
                            '<label class="admin-label">本文 <span class="admin-required-badge">必須</span></label>' +
                            '<textarea name="new_blogs[' + key + '][body]" rows="6" required class="admin-input"></textarea>' +
                        '</div>' +
                        '<div>' +
                            '<label class="admin-label">投稿日 <span class="admin-required-badge">必須</span></label>' +
                            '<input type="datetime-local" name="new_blogs[' + key + '][published_at]" value="" required class="admin-input">' +
                            '<p class="mt-1 text-xs text-admin-muted">公開判定に使います（投稿日が未来の場合は表示されません）</p>' +
                        '</div>' +
                        '<div>' +
                            '<span class="admin-label">公開</span>' +
                            '<div class="admin-segmented mt-1" role="radiogroup" aria-label="公開状態">' +
                                '<label class="admin-segmented-option">' +
                                    '<input type="radio" name="new_blogs[' + key + '][is_published]" value="1" class="admin-segmented-input" checked>' +
                                    '<span class="admin-segmented-face">' +
                                        '<svg class="admin-segmented-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">' +
                                            '<path d="M1.5 8s2.5-4.5 6.5-4.5S14.5 8 14.5 8s-2.5 4.5-6.5 4.5S1.5 8 1.5 8z" stroke="currentColor" stroke-width="1.35" stroke-linejoin="round"/>' +
                                            '<circle cx="8" cy="8" r="2" stroke="currentColor" stroke-width="1.35"/>' +
                                        '</svg>' +
                                        '<span class="admin-segmented-text">公開</span>' +
                                    '</span>' +
                                '</label>' +
                                '<label class="admin-segmented-option">' +
                                    '<input type="radio" name="new_blogs[' + key + '][is_published]" value="0" class="admin-segmented-input">' +
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
                if (e.target.closest('[data-blog-dropzone]')) {
                    return;
                }
                const handle = e.target.closest('[data-blog-drag-handle]');
                if (!handle || !grid.contains(handle)) {
                    return;
                }
                const card = handle.closest('[data-blog-card]');
                if (!card) {
                    e.preventDefault();
                    return;
                }
                dragCard = card;
                card.classList.add('is-dragging');
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', card.getAttribute('data-blog-id') || 'new');
            });

            grid.addEventListener('dragend', function () {
                if (dragCard) {
                    dragCard.classList.remove('is-dragging');
                }
                grid.querySelectorAll('.is-drag-over').forEach(function (el) {
                    el.classList.remove('is-drag-over');
                });
                dragCard = null;
                syncDisplayOrders();
            });

            grid.addEventListener('dragover', function (e) {
                if (!dragCard) {
                    return;
                }
                e.preventDefault();
                const over = e.target.closest('[data-blog-card]');
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

            grid.querySelectorAll('[data-blog-card]').forEach(bindCard);
            syncDisplayOrders();

            addButton.addEventListener('click', function (e) {
                e.preventDefault();
                createEmptyCard();
            });
        })();
    </script>
@endsection
