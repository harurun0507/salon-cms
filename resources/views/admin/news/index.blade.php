@extends('layouts.admin')

@section('heading', 'お知らせ')

@section('save-bar')
    <div class="flex min-w-0 flex-wrap items-center gap-3">
        <button
            type="button"
            class="admin-btn shadow-md shrink-0"
            data-admin-confirm-trigger
            data-confirm-form="news-bulk-form"
            data-confirm-title="お知らせ保存の確認"
            data-confirm-message="変更内容を保存します。&#10;よろしいですか？"
            data-confirm-note="タイトル、本文、公開日時、公開状態、表示順、削除など、現在入力されている内容が反映されます。"
            data-confirm-submit-label="保存する"
        >保存する</button>
        <p class="text-sm text-admin-muted">
            公開サイトに表示するお知らせを登録・編集します。
        </p>
    </div>

@endsection

@section('content')
    @php
        $maxOrder = (int) ($newsList->max('display_order') ?? 0);

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

        $oldNewNews = old('new_news', []);
        if (! is_array($oldNewNews)) {
            $oldNewNews = [];
        }
        $nextNewIndex = 1;
        foreach (array_keys($oldNewNews) as $key) {
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
        action="{{ route('admin.news.update') }}"
        id="news-bulk-form"
        data-news-workspace
        data-next-new-index="{{ $nextNewIndex }}"
        data-max-order="{{ $maxOrder }}"
    >
        @csrf
        @method('PUT')

        <div id="news-deleted-ids"></div>

        <div id="news-grid" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3" data-news-grid>
            @foreach($newsList as $news)
                @php
                    $prefix = 'news.'.$news->id;
                    $publishedOld = old($prefix.'.is_published', $news->is_published ? '1' : '0');
                    $isPublished = in_array((string) $publishedOld, ['1', 'true', 'on'], true);
                    $publishedAt = old($prefix.'.published_at', $formatLocal($news->published_at));
                    $displayOrder = old($prefix.'.display_order', $news->display_order);
                    $cardTitle = trim((string) old($prefix.'.title', $news->title));
                    $headingTitle = $cardTitle !== '' ? $cardTitle : '新規お知らせ';
                @endphp
                <div
                    class="admin-card news-card"
                    data-news-card
                    data-news-id="{{ $news->id }}"
                    data-news-existing
                >
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-2">
                            <span
                                class="news-drag-handle"
                                data-news-drag-handle
                                draggable="true"
                                role="button"
                                tabindex="0"
                                aria-label="お知らせを並び替え"
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
                            <p class="news-card-label truncate text-sm font-medium text-gray-800" data-news-card-title title="{{ $headingTitle }}">{{ $headingTitle }}</p>
                        </div>
                        <button
                            type="button"
                            class="admin-icon-btn admin-icon-btn-delete"
                            data-news-remove
                            aria-label="削除"
                            title="削除"
                        >
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <input type="hidden" name="news[{{ $news->id }}][display_order]" value="{{ $displayOrder }}" data-news-order>

                    <div class="space-y-3">
                        <div>
                            <label for="news_title_{{ $news->id }}" class="admin-label">タイトル <span class="admin-required-badge">必須</span></label>
                            <input
                                type="text"
                                name="news[{{ $news->id }}][title]"
                                id="news_title_{{ $news->id }}"
                                value="{{ old($prefix.'.title', $news->title) }}"
                                maxlength="255"
                                required
                                class="admin-input"
                                data-news-title-input
                            >
                            @error($prefix.'.title')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="news_body_{{ $news->id }}" class="admin-label">本文 <span class="admin-required-badge">必須</span></label>
                            <textarea
                                name="news[{{ $news->id }}][body]"
                                id="news_body_{{ $news->id }}"
                                rows="6"
                                required
                                class="admin-input"
                            >{{ old($prefix.'.body', $news->body) }}</textarea>
                            @error($prefix.'.body')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="news_published_at_{{ $news->id }}" class="admin-label">公開日時</label>
                            <input
                                type="datetime-local"
                                name="news[{{ $news->id }}][published_at]"
                                id="news_published_at_{{ $news->id }}"
                                value="{{ $publishedAt }}"
                                class="admin-input"
                            >
                            <p class="mt-1 text-xs text-admin-muted">空欄＝制限なし（公開中ならすぐ表示）</p>
                            @error($prefix.'.published_at')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <span class="admin-label">公開</span>
                            <div class="admin-segmented mt-1" role="radiogroup" aria-label="公開状態">
                                <label class="admin-segmented-option">
                                    <input type="radio" name="news[{{ $news->id }}][is_published]" value="1" class="admin-segmented-input" @checked($isPublished)>
                                    <span class="admin-segmented-face">
                                        <svg class="admin-segmented-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                            <path d="M1.5 8s2.5-4.5 6.5-4.5S14.5 8 14.5 8s-2.5 4.5-6.5 4.5S1.5 8 1.5 8z" stroke="currentColor" stroke-width="1.35" stroke-linejoin="round"/>
                                            <circle cx="8" cy="8" r="2" stroke="currentColor" stroke-width="1.35"/>
                                        </svg>
                                        <span class="admin-segmented-text">公開</span>
                                    </span>
                                </label>
                                <label class="admin-segmented-option">
                                    <input type="radio" name="news[{{ $news->id }}][is_published]" value="0" class="admin-segmented-input" @checked(!$isPublished)>
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

            @foreach($oldNewNews as $key => $newItem)
                @php
                    if (! is_array($newItem)) {
                        continue;
                    }
                    $prefix = 'new_news.'.$key;
                    $publishedOld = old($prefix.'.is_published', $newItem['is_published'] ?? '1');
                    $isPublished = in_array((string) $publishedOld, ['1', 'true', 'on'], true);
                    $publishedAt = old($prefix.'.published_at', $newItem['published_at'] ?? '');
                    $displayOrder = old($prefix.'.display_order', $newItem['display_order'] ?? 0);
                    $cardTitle = trim((string) old($prefix.'.title', $newItem['title'] ?? ''));
                    $headingTitle = $cardTitle !== '' ? $cardTitle : '新規お知らせ';
                    $body = old($prefix.'.body', $newItem['body'] ?? '');
                @endphp
                <div
                    class="admin-card news-card"
                    data-news-card
                    data-news-new="1"
                >
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-2">
                            <span
                                class="news-drag-handle"
                                data-news-drag-handle
                                draggable="true"
                                role="button"
                                tabindex="0"
                                aria-label="お知らせを並び替え"
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
                            <p class="news-card-label truncate text-sm font-medium text-gray-800" data-news-card-title title="{{ $headingTitle }}">{{ $headingTitle }}</p>
                        </div>
                        <button
                            type="button"
                            class="admin-icon-btn admin-icon-btn-delete"
                            data-news-remove
                            aria-label="削除"
                            title="削除"
                        >
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <input type="hidden" name="new_news[{{ $key }}][display_order]" value="{{ $displayOrder }}" data-news-order>

                    <div class="space-y-3">
                        <div>
                            <label class="admin-label">タイトル <span class="admin-required-badge">必須</span></label>
                            <input
                                type="text"
                                name="new_news[{{ $key }}][title]"
                                value="{{ $cardTitle }}"
                                maxlength="255"
                                required
                                class="admin-input"
                                data-news-title-input
                            >
                            @error($prefix.'.title')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="admin-label">本文 <span class="admin-required-badge">必須</span></label>
                            <textarea
                                name="new_news[{{ $key }}][body]"
                                rows="6"
                                required
                                class="admin-input"
                            >{{ $body }}</textarea>
                            @error($prefix.'.body')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="admin-label">公開日時</label>
                            <input
                                type="datetime-local"
                                name="new_news[{{ $key }}][published_at]"
                                value="{{ $publishedAt }}"
                                class="admin-input"
                            >
                            <p class="mt-1 text-xs text-admin-muted">空欄＝制限なし（公開中ならすぐ表示）</p>
                        </div>
                        <div>
                            <span class="admin-label">公開</span>
                            <div class="admin-segmented mt-1" role="radiogroup" aria-label="公開状態">
                                <label class="admin-segmented-option">
                                    <input type="radio" name="new_news[{{ $key }}][is_published]" value="1" class="admin-segmented-input" @checked($isPublished)>
                                    <span class="admin-segmented-face">
                                        <svg class="admin-segmented-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                            <path d="M1.5 8s2.5-4.5 6.5-4.5S14.5 8 14.5 8s-2.5 4.5-6.5 4.5S1.5 8 1.5 8z" stroke="currentColor" stroke-width="1.35" stroke-linejoin="round"/>
                                            <circle cx="8" cy="8" r="2" stroke="currentColor" stroke-width="1.35"/>
                                        </svg>
                                        <span class="admin-segmented-text">公開</span>
                                    </span>
                                </label>
                                <label class="admin-segmented-option">
                                    <input type="radio" name="new_news[{{ $key }}][is_published]" value="0" class="admin-segmented-input" @checked(!$isPublished)>
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
                id="news-add-card"
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
                <x-admin.create-button data-news-add>
                    お知らせを追加
                </x-admin.create-button>
                <p class="mt-3 text-xs text-admin-muted">カードを追加し、保存で登録できます。</p>
            </div>
        </div>
    </form>

    <style>
        .news-drag-handle {
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
        .news-drag-handle:hover,
        .news-drag-handle:focus-visible {
            color: #556344;
        }
        .news-drag-handle:focus {
            outline: none;
        }
        .news-drag-handle:focus-visible {
            box-shadow: inset 0 0 0 2px rgba(105, 122, 85, 0.35);
            border-radius: 0.25rem;
        }
        .news-drag-handle:active,
        .news-card.is-dragging .news-drag-handle {
            cursor: grabbing;
        }
        .news-card.is-dragging {
            opacity: 0.55;
        }
        .news-card.is-drag-over {
            outline: 2px dashed rgba(105, 122, 85, 0.45);
            outline-offset: 2px;
        }
    </style>

    <script>
        (function () {
            const form = document.getElementById('news-bulk-form');
            const grid = document.getElementById('news-grid');
            const deletedIdsWrap = document.getElementById('news-deleted-ids');
            const addCard = document.getElementById('news-add-card');
            const addButton = addCard ? addCard.querySelector('[data-news-add]') : null;
            const emptyHeading = '新規お知らせ';

            if (!form || !grid || !addCard || !addButton) {
                return;
            }

            let nextNewIndex = parseInt(form.getAttribute('data-next-new-index') || '1', 10);
            let maxOrder = parseInt(form.getAttribute('data-max-order') || '0', 10);
            let dragCard = null;

            function syncCardHeading(card) {
                const label = card.querySelector('[data-news-card-title]');
                const input = card.querySelector('[data-news-title-input]');
                if (!label || !input) {
                    return;
                }
                const value = (input.value || '').trim();
                const text = value !== '' ? value : emptyHeading;
                label.textContent = text;
                label.setAttribute('title', text);
            }

            function syncDisplayOrders() {
                grid.querySelectorAll('[data-news-card]').forEach(function (card, index) {
                    const orderInput = card.querySelector('[data-news-order]');
                    if (orderInput) {
                        orderInput.value = String(index + 1);
                    }
                });
                maxOrder = grid.querySelectorAll('[data-news-card]').length;
                form.setAttribute('data-max-order', String(maxOrder));
            }

            function bindCard(card) {
                const removeBtn = card.querySelector('[data-news-remove]');
                const titleInput = card.querySelector('[data-news-title-input]');

                titleInput?.addEventListener('input', function () {
                    syncCardHeading(card);
                });
                syncCardHeading(card);

                removeBtn?.addEventListener('click', function () {
                    const existingId = card.getAttribute('data-news-id');
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

                const order = grid.querySelectorAll('[data-news-card]').length + 1;
                maxOrder = Math.max(maxOrder, order);
                form.setAttribute('data-max-order', String(maxOrder));

                const card = document.createElement('div');
                card.className = 'admin-card news-card';
                card.setAttribute('data-news-card', '');
                card.setAttribute('data-news-new', '1');
                card.innerHTML =
                    '<div class="mb-3 flex items-center justify-between gap-3">' +
                        '<div class="flex min-w-0 items-center gap-2">' +
                            '<span class="news-drag-handle" data-news-drag-handle draggable="true" role="button" tabindex="0" aria-label="お知らせを並び替え" title="ドラッグして並び替え">' +
                                '<svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">' +
                                    '<circle cx="7" cy="5" r="1.25"/><circle cx="13" cy="5" r="1.25"/>' +
                                    '<circle cx="7" cy="10" r="1.25"/><circle cx="13" cy="10" r="1.25"/>' +
                                    '<circle cx="7" cy="15" r="1.25"/><circle cx="13" cy="15" r="1.25"/>' +
                                '</svg>' +
                            '</span>' +
                            '<p class="news-card-label truncate text-sm font-medium text-gray-800" data-news-card-title title="' + emptyHeading + '">' + emptyHeading + '</p>' +
                        '</div>' +
                        '<button type="button" class="admin-icon-btn admin-icon-btn-delete" data-news-remove aria-label="削除" title="削除">' +
                            '<span aria-hidden="true">&times;</span>' +
                        '</button>' +
                    '</div>' +
                    '<input type="hidden" name="new_news[' + key + '][display_order]" value="' + order + '" data-news-order>' +
                    '<div class="space-y-3">' +
                        '<div>' +
                            '<label class="admin-label">タイトル <span class="admin-required-badge">必須</span></label>' +
                            '<input type="text" name="new_news[' + key + '][title]" value="" maxlength="255" required class="admin-input" data-news-title-input>' +
                        '</div>' +
                        '<div>' +
                            '<label class="admin-label">本文 <span class="admin-required-badge">必須</span></label>' +
                            '<textarea name="new_news[' + key + '][body]" rows="6" required class="admin-input"></textarea>' +
                        '</div>' +
                        '<div>' +
                            '<label class="admin-label">公開日時</label>' +
                            '<input type="datetime-local" name="new_news[' + key + '][published_at]" value="" class="admin-input">' +
                            '<p class="mt-1 text-xs text-admin-muted">空欄＝制限なし（公開中ならすぐ表示）</p>' +
                        '</div>' +
                        '<div>' +
                            '<span class="admin-label">公開</span>' +
                            '<div class="admin-segmented mt-1" role="radiogroup" aria-label="公開状態">' +
                                '<label class="admin-segmented-option">' +
                                    '<input type="radio" name="new_news[' + key + '][is_published]" value="1" class="admin-segmented-input" checked>' +
                                    '<span class="admin-segmented-face">' +
                                        '<svg class="admin-segmented-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">' +
                                            '<path d="M1.5 8s2.5-4.5 6.5-4.5S14.5 8 14.5 8s-2.5 4.5-6.5 4.5S1.5 8 1.5 8z" stroke="currentColor" stroke-width="1.35" stroke-linejoin="round"/>' +
                                            '<circle cx="8" cy="8" r="2" stroke="currentColor" stroke-width="1.35"/>' +
                                        '</svg>' +
                                        '<span class="admin-segmented-text">公開</span>' +
                                    '</span>' +
                                '</label>' +
                                '<label class="admin-segmented-option">' +
                                    '<input type="radio" name="new_news[' + key + '][is_published]" value="0" class="admin-segmented-input">' +
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
                const handle = e.target.closest('[data-news-drag-handle]');
                if (!handle || !grid.contains(handle)) {
                    return;
                }
                const card = handle.closest('[data-news-card]');
                if (!card) {
                    e.preventDefault();
                    return;
                }
                dragCard = card;
                card.classList.add('is-dragging');
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', card.getAttribute('data-news-id') || 'new');
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
                const over = e.target.closest('[data-news-card]');
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

            grid.querySelectorAll('[data-news-card]').forEach(bindCard);
            syncDisplayOrders();

            addButton.addEventListener('click', function (e) {
                e.preventDefault();
                createEmptyCard();
            });
        })();
    </script>
@endsection
