@extends('layouts.admin')

@section('heading', 'SNS')

@section('content')
    @php
        $oldLinks = old('links', []);
        $orderedLinks = $links;
        if (is_array($oldLinks) && $oldLinks !== []) {
            $byKey = $links->keyBy('service_key');
            $sorted = collect($oldLinks)
                ->sortBy(fn ($row) => (int) ($row['display_order'] ?? PHP_INT_MAX))
                ->keys()
                ->map(fn ($key) => $byKey->get($key))
                ->filter()
                ->values();
            if ($sorted->count() === $links->count()) {
                $orderedLinks = $sorted;
            }
        }
    @endphp

    <div class="sticky top-[4.5rem] z-10 -mx-4 -mt-4 mb-6 border-b border-admin-border/50 bg-admin-bg/95 px-4 py-3 shadow-[0_1px_0_rgba(61,56,51,0.03)] backdrop-blur-sm md:-mx-8 md:-mt-8 md:px-8">
        <div class="flex min-w-0 flex-wrap items-center gap-3">
            <button
                type="button"
                class="admin-btn shadow-md shrink-0"
                data-admin-confirm-trigger
                data-confirm-form="sns-form"
                data-confirm-title="SNS設定保存の確認"
                data-confirm-message="SNS設定を保存します。&#10;よろしいですか？"
                data-confirm-note="SNS・公式アカウントのURL・表示・並び順など、現在入力されている内容が反映されます。"
                data-confirm-submit-label="保存する"
            >保存する</button>
            <p class="text-sm text-admin-muted">
                各項目を編集し、「保存する」でまとめて反映できます
            </p>
        </div>
    </div>

    <form id="sns-form" method="POST" action="{{ route('admin.store.sns.update') }}" class="space-y-5">
        @csrf @method('PUT')

        <div class="space-y-4">
            <div>
                <h2 class="text-base font-medium text-admin-text">SNS・公式アカウント</h2>
                <p class="mt-1 text-sm text-admin-muted">公開サイトに表示するSNSや公式アカウントのリンクを設定します。</p>
            </div>

            @error('links')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror

            <div id="sns-links-list" class="grid grid-cols-1 gap-4 md:grid-cols-2" data-sns-grid>
                @foreach($orderedLinks as $index => $link)
                    @php
                        $key = $link->service_key;
                        $prefix = 'links.'.$key;
                        $urlValue = old($prefix.'.url', $link->url);
                        $visibleOld = old($prefix.'.is_visible', $link->is_visible ? '1' : '0');
                        $isVisible = in_array((string) $visibleOld, ['1', 'true', 'on'], true);
                        $orderValue = old($prefix.'.display_order', $link->display_order ?: ($index + 1));
                    @endphp
                    <div
                        class="admin-card sns-card space-y-4"
                        data-sns-card
                        data-service-key="{{ $key }}"
                    >
                        <div class="flex items-center gap-2">
                            <span
                                class="sns-drag-handle"
                                data-sns-drag-handle
                                draggable="true"
                                role="button"
                                tabindex="0"
                                aria-label="{{ $link->label() }}を並び替え"
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
                            <div class="admin-service-heading !mb-0 min-w-0">
                                <x-social-icon :service="$key" class="admin-service-icon" />
                                <span class="admin-service-name">{{ $link->label() }}</span>
                            </div>
                        </div>

                        <input
                            type="hidden"
                            name="links[{{ $key }}][display_order]"
                            value="{{ $orderValue }}"
                            data-sns-order
                        >

                        <div>
                            <label for="sns-url-{{ $key }}" class="admin-label">{{ $link->urlLabel() }}</label>
                            <input
                                type="url"
                                name="links[{{ $key }}][url]"
                                id="sns-url-{{ $key }}"
                                value="{{ $urlValue }}"
                                class="admin-input"
                                @if($link->placeholder()) placeholder="{{ $link->placeholder() }}" @endif
                            >
                            @error($prefix.'.url')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <span class="admin-label">表示</span>
                            <div class="admin-segmented mt-1" role="radiogroup" aria-label="{{ $link->label() }}の表示">
                                <label class="admin-segmented-option">
                                    <input type="radio" name="links[{{ $key }}][is_visible]" value="1" class="admin-segmented-input" @checked($isVisible)>
                                    <span class="admin-segmented-face">
                                        <svg class="admin-segmented-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                            <path d="M1.5 8s2.5-4.5 6.5-4.5S14.5 8 14.5 8s-2.5 4.5-6.5 4.5S1.5 8 1.5 8z" stroke="currentColor" stroke-width="1.35" stroke-linejoin="round"/>
                                            <circle cx="8" cy="8" r="2" stroke="currentColor" stroke-width="1.35"/>
                                        </svg>
                                        <span class="admin-segmented-text">表示する</span>
                                    </span>
                                </label>
                                <label class="admin-segmented-option">
                                    <input type="radio" name="links[{{ $key }}][is_visible]" value="0" class="admin-segmented-input" @checked(!$isVisible)>
                                    <span class="admin-segmented-face">
                                        <svg class="admin-segmented-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                            <path d="M2 2.5 13.5 13.5" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/>
                                            <path d="M6.7 4.1A6.4 6.4 0 0 1 8 3.5c4 0 6.5 4.5 6.5 4.5a10.3 10.3 0 0 1-2.15 2.55M4.2 5.85A10.2 10.2 0 0 0 1.5 8S4 12.5 8 12.5c.7 0 1.35-.12 1.95-.34" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M6.65 7.1a2 2 0 0 0 2.35 2.35" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/>
                                        </svg>
                                        <span class="admin-segmented-text">表示しない</span>
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </form>

    <style>
        .sns-drag-handle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.75rem;
            height: 1.75rem;
            flex-shrink: 0;
            border-radius: 0.375rem;
            color: #8A847A;
            cursor: grab;
            touch-action: none;
        }
        .sns-drag-handle:hover,
        .sns-drag-handle:focus-visible {
            color: #697A55;
            background: rgba(105, 122, 85, 0.08);
        }
        .sns-drag-handle:focus {
            outline: none;
        }
        .sns-drag-handle:focus-visible {
            box-shadow: 0 0 0 2px rgba(105, 122, 85, 0.35);
        }
        .sns-drag-handle:active,
        .sns-card.is-dragging .sns-drag-handle {
            cursor: grabbing;
        }
        .sns-card.is-dragging {
            opacity: 0.55;
        }
        .sns-card.is-drag-over {
            box-shadow: 0 0 0 2px rgba(105, 122, 85, 0.35);
        }
    </style>

    <script>
        (function () {
            const list = document.querySelector('[data-sns-grid]');
            if (!list) {
                return;
            }

            let dragCard = null;

            function cards() {
                return Array.from(list.querySelectorAll('[data-sns-card]'));
            }

            function syncOrders() {
                cards().forEach(function (card, index) {
                    const input = card.querySelector('[data-sns-order]');
                    if (input) {
                        input.value = String(index + 1);
                    }
                });
            }

            list.addEventListener('dragstart', function (e) {
                const handle = e.target.closest('[data-sns-drag-handle]');
                if (!handle || !list.contains(handle)) {
                    return;
                }
                const card = handle.closest('[data-sns-card]');
                if (!card) {
                    e.preventDefault();
                    return;
                }
                dragCard = card;
                card.classList.add('is-dragging');
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', card.getAttribute('data-service-key') || '');
            });

            list.addEventListener('dragend', function () {
                if (dragCard) {
                    dragCard.classList.remove('is-dragging');
                }
                list.querySelectorAll('.is-drag-over').forEach(function (el) {
                    el.classList.remove('is-drag-over');
                });
                dragCard = null;
                syncOrders();
            });

            list.addEventListener('dragover', function (e) {
                if (!dragCard) {
                    return;
                }
                e.preventDefault();
                const over = e.target.closest('[data-sns-card]');
                if (!over || over === dragCard || !list.contains(over)) {
                    return;
                }
                list.querySelectorAll('.is-drag-over').forEach(function (el) {
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

            list.addEventListener('drop', function (e) {
                if (!dragCard) {
                    return;
                }
                e.preventDefault();
            });

            syncOrders();
        })();
    </script>
@endsection
