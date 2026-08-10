@extends('layouts.admin')

@section('heading', 'お知らせ')

@section('save-bar')
    <div class="flex min-w-0 flex-wrap items-center justify-between gap-3">
        <div class="flex min-w-0 flex-wrap items-center gap-3">
            <button
                type="button"
                class="admin-btn shadow-md shrink-0"
                data-admin-confirm-trigger
                data-confirm-form="news-bulk-form"
                data-confirm-title="お知らせ保存の確認"
                data-confirm-message="変更内容を保存します。&#10;よろしいですか？"
                data-confirm-note="タイトル、お知らせの種類、休業日、本文、公開日時、公開状態、表示順、削除など、現在入力されている内容が反映されます。"
                data-confirm-submit-label="保存する"
            >保存する</button>
            <p class="text-sm text-admin-muted">
                公開サイトに表示するお知らせを登録・編集します。
            </p>
        </div>
        <x-admin.create-button data-news-add-top class="shrink-0">
            お知らせを追加
        </x-admin.create-button>
    </div>

@endsection

@section('content')
    @php
        $maxOrder = (int) ($newsList->max('display_order') ?? 0);
        $closedDateCategoryKeys = \App\Models\News::closedDateCategoryKeys();
        $closedWeekdayCategoryKeys = \App\Models\News::closedWeekdayCategoryKeys();
        $usesClosedDates = fn ($value) => \App\Models\News::usesClosedDates(is_string($value) ? $value : null);
        $usesClosedWeekdays = fn ($value) => \App\Models\News::usesClosedWeekdays(is_string($value) ? $value : null);
        $weekdayLabels = $weekdayLabels ?? \App\Models\News::WEEKDAY_SHORT_LABELS;

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

        $normalizeClosedDates = function ($raw) {
            if (! is_array($raw)) {
                return [];
            }

            return collect($raw)
                ->filter(fn ($v) => is_string($v) && $v !== '')
                ->map(function ($v) {
                    try {
                        return \Illuminate\Support\Carbon::parse($v)->toDateString();
                    } catch (\Throwable) {
                        return null;
                    }
                })
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->all();
        };

        $normalizeClosedWeekdays = function ($raw) {
            if (! is_array($raw)) {
                return [];
            }

            return collect($raw)
                ->filter(fn ($v) => $v !== null && $v !== '')
                ->map(fn ($v) => (int) $v)
                ->filter(fn ($v) => $v >= 0 && $v <= 6)
                ->unique()
                ->sort()
                ->values()
                ->all();
        };
    @endphp

    
    <form
        method="POST"
        action="{{ route('admin.news.update') }}"
        id="news-bulk-form"
        novalidate
        data-news-workspace
        data-next-new-index="{{ $nextNewIndex }}"
        data-max-order="{{ $maxOrder }}"
        data-closed-date-categories="{{ implode(',', $closedDateCategoryKeys) }}"
        data-closed-weekday-categories="{{ implode(',', $closedWeekdayCategoryKeys) }}"
    >
        @csrf
        @method('PUT')

        <div id="news-deleted-ids"></div>

        <div id="news-grid" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3" data-news-grid>
            @foreach($newsList as $news)
                @php
                    $prefix = 'news.'.$news->id;
                    $publishedOld = old($prefix.'.is_published', $news->is_published ? '1' : '0');
                    $isPublished = (string) $publishedOld === '1';
                    $isUnpublished = (string) $publishedOld === '0';
                    $publishedAt = old($prefix.'.published_at', $formatLocal($news->published_at));
                    $displayOrder = old($prefix.'.display_order', $news->display_order);
                    $cardTitle = trim((string) old($prefix.'.title', $news->title));
                    $headingTitle = $cardTitle !== '' ? $cardTitle : '新規お知らせ';
                    $category = old($prefix.'.category', $news->category ?: \App\Models\News::CATEGORY_OTHER);
                    $closedDates = $normalizeClosedDates(
                        old($prefix.'.closed_dates', $news->closedDates->pluck('closed_date')->map->toDateString()->all())
                    );
                    $closedWeekdays = $normalizeClosedWeekdays(
                        old($prefix.'.closed_weekdays', $news->closedWeekdays->pluck('weekday')->all())
                    );
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
                            <span class="admin-label" id="news_category_label_{{ $news->id }}">お知らせの種類 <span class="admin-required-badge">必須</span></span>
                            <div
                                class="news-category-choices mt-1"
                                role="radiogroup"
                                aria-labelledby="news_category_label_{{ $news->id }}"
                                data-news-category-group
                            >
                                @foreach($categories as $value => $label)
                                    <label class="news-category-option">
                                        <input
                                            type="radio"
                                            name="news[{{ $news->id }}][category]"
                                            value="{{ $value }}"
                                            class="news-category-input"
                                            data-news-category
                                            @checked((string) $category === (string) $value)
                                        >
                                        <span class="news-category-face">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <label for="news_title_{{ $news->id }}" class="admin-label">タイトル <span class="admin-required-badge">必須</span></label>
                            <input
                                type="text"
                                name="news[{{ $news->id }}][title]"
                                id="news_title_{{ $news->id }}"
                                value="{{ old($prefix.'.title', $news->title) }}"
                                maxlength="255"
                                class="admin-input"
                                data-news-title-input
                            >
                        </div>
                        <div
                            data-news-weekday-wrap
                            @if(! $usesClosedWeekdays($category)) hidden @endif
                        >
                            <span class="admin-label">定休日 <span class="admin-required-badge">必須</span></span>
                            <div class="news-weekday-choices mt-1" role="group" aria-label="定休日">
                                @foreach($weekdayLabels as $weekdayValue => $weekdayLabel)
                                    <label class="news-weekday-option">
                                        <input
                                            type="checkbox"
                                            name="news[{{ $news->id }}][closed_weekdays][]"
                                            value="{{ $weekdayValue }}"
                                            class="news-weekday-input"
                                            data-news-weekday
                                            @checked(in_array((int) $weekdayValue, array_map('intval', $closedWeekdays), true))
                                        >
                                        <span class="news-weekday-face">{{ $weekdayLabel }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div
                            data-news-closed-wrap
                            @if(! $usesClosedDates($category)) hidden @endif
                        >
                            <span class="admin-label">休業日 <span class="admin-required-badge">必須</span></span>
                            <div
                                class="news-closed-calendar mt-1"
                                data-news-closed-calendar
                                data-field-prefix="news[{{ $news->id }}]"
                                data-selected-dates="{{ implode(',', $closedDates) }}"
                            ></div>
                            <p class="mt-2 text-xs text-admin-muted" data-news-closed-summary></p>
                        </div>
                        <div>
                            <label for="news_body_{{ $news->id }}" class="admin-label">本文</label>
                            <textarea
                                name="news[{{ $news->id }}][body]"
                                id="news_body_{{ $news->id }}"
                                rows="6"
                                class="admin-input"
                            >{{ old($prefix.'.body', $news->body) }}</textarea>
                        </div>
                        <div>
                            <label for="news_published_at_{{ $news->id }}" class="admin-label">公開日時 <span class="admin-required-badge">必須</span></label>
                            <input
                                type="datetime-local"
                                name="news[{{ $news->id }}][published_at]"
                                id="news_published_at_{{ $news->id }}"
                                value="{{ $publishedAt }}"
                                class="admin-input"
                                data-news-published-at
                            >
                            <p class="mt-1 text-xs text-admin-muted">公開開始日時を指定してください。</p>
                        </div>
                        <div>
                            <span class="admin-label">公開 <span class="admin-required-badge">必須</span></span>
                            <div class="admin-segmented mt-1" role="radiogroup" aria-label="公開状態">
                                <label class="admin-segmented-option">
                                    <input type="radio" name="news[{{ $news->id }}][is_published]" value="1" class="admin-segmented-input" data-news-is-published @checked($isPublished)>
                                    <span class="admin-segmented-face">
                                        <svg class="admin-segmented-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                            <path d="M1.5 8s2.5-4.5 6.5-4.5S14.5 8 14.5 8s-2.5 4.5-6.5 4.5S1.5 8 1.5 8z" stroke="currentColor" stroke-width="1.35" stroke-linejoin="round"/>
                                            <circle cx="8" cy="8" r="2" stroke="currentColor" stroke-width="1.35"/>
                                        </svg>
                                        <span class="admin-segmented-text">公開</span>
                                    </span>
                                </label>
                                <label class="admin-segmented-option">
                                    <input type="radio" name="news[{{ $news->id }}][is_published]" value="0" class="admin-segmented-input" data-news-is-published @checked($isUnpublished)>
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
                    $publishedOld = old($prefix.'.is_published', $newItem['is_published'] ?? null);
                    $isPublished = (string) $publishedOld === '1';
                    $isUnpublished = (string) $publishedOld === '0';
                    $publishedAt = old($prefix.'.published_at', $newItem['published_at'] ?? '');
                    $displayOrder = old($prefix.'.display_order', $newItem['display_order'] ?? 0);
                    $cardTitle = trim((string) old($prefix.'.title', $newItem['title'] ?? ''));
                    $headingTitle = $cardTitle !== '' ? $cardTitle : '新規お知らせ';
                    $body = old($prefix.'.body', $newItem['body'] ?? '');
                    $category = old($prefix.'.category', $newItem['category'] ?? \App\Models\News::CATEGORY_OTHER);
                    $closedDates = $normalizeClosedDates(old($prefix.'.closed_dates', $newItem['closed_dates'] ?? []));
                    $closedWeekdays = $normalizeClosedWeekdays(old($prefix.'.closed_weekdays', $newItem['closed_weekdays'] ?? []));
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
                            <span class="admin-label">お知らせの種類 <span class="admin-required-badge">必須</span></span>
                            <div
                                class="news-category-choices mt-1"
                                role="radiogroup"
                                aria-label="お知らせの種類"
                                data-news-category-group
                            >
                                @foreach($categories as $value => $label)
                                    <label class="news-category-option">
                                        <input
                                            type="radio"
                                            name="new_news[{{ $key }}][category]"
                                            value="{{ $value }}"
                                            class="news-category-input"
                                            data-news-category
                                            @checked((string) $category === (string) $value)
                                        >
                                        <span class="news-category-face">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <label class="admin-label">タイトル <span class="admin-required-badge">必須</span></label>
                            <input
                                type="text"
                                name="new_news[{{ $key }}][title]"
                                value="{{ $cardTitle }}"
                                maxlength="255"
                                class="admin-input"
                                data-news-title-input
                            >
                        </div>
                        <div
                            data-news-weekday-wrap
                            @if(! $usesClosedWeekdays($category)) hidden @endif
                        >
                            <span class="admin-label">定休日 <span class="admin-required-badge">必須</span></span>
                            <div class="news-weekday-choices mt-1" role="group" aria-label="定休日">
                                @foreach($weekdayLabels as $weekdayValue => $weekdayLabel)
                                    <label class="news-weekday-option">
                                        <input
                                            type="checkbox"
                                            name="new_news[{{ $key }}][closed_weekdays][]"
                                            value="{{ $weekdayValue }}"
                                            class="news-weekday-input"
                                            data-news-weekday
                                            @checked(in_array((int) $weekdayValue, array_map('intval', $closedWeekdays), true))
                                        >
                                        <span class="news-weekday-face">{{ $weekdayLabel }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div
                            data-news-closed-wrap
                            @if(! $usesClosedDates($category)) hidden @endif
                        >
                            <span class="admin-label">休業日 <span class="admin-required-badge">必須</span></span>
                            <div
                                class="news-closed-calendar mt-1"
                                data-news-closed-calendar
                                data-field-prefix="new_news[{{ $key }}]"
                                data-selected-dates="{{ implode(',', $closedDates) }}"
                            ></div>
                            <p class="mt-2 text-xs text-admin-muted" data-news-closed-summary></p>
                        </div>
                        <div>
                            <label class="admin-label">本文</label>
                            <textarea
                                name="new_news[{{ $key }}][body]"
                                rows="6"
                                class="admin-input"
                            >{{ $body }}</textarea>
                        </div>
                        <div>
                            <label class="admin-label">公開日時 <span class="admin-required-badge">必須</span></label>
                            <input
                                type="datetime-local"
                                name="new_news[{{ $key }}][published_at]"
                                value="{{ $publishedAt }}"
                                class="admin-input"
                                data-news-published-at
                            >
                            <p class="mt-1 text-xs text-admin-muted">公開開始日時を指定してください。</p>
                        </div>
                        <div>
                            <span class="admin-label">公開 <span class="admin-required-badge">必須</span></span>
                            <div class="admin-segmented mt-1" role="radiogroup" aria-label="公開状態">
                                <label class="admin-segmented-option">
                                    <input type="radio" name="new_news[{{ $key }}][is_published]" value="1" class="admin-segmented-input" data-news-is-published @checked($isPublished)>
                                    <span class="admin-segmented-face">
                                        <svg class="admin-segmented-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                            <path d="M1.5 8s2.5-4.5 6.5-4.5S14.5 8 14.5 8s-2.5 4.5-6.5 4.5S1.5 8 1.5 8z" stroke="currentColor" stroke-width="1.35" stroke-linejoin="round"/>
                                            <circle cx="8" cy="8" r="2" stroke="currentColor" stroke-width="1.35"/>
                                        </svg>
                                        <span class="admin-segmented-text">公開</span>
                                    </span>
                                </label>
                                <label class="admin-segmented-option">
                                    <input type="radio" name="new_news[{{ $key }}][is_published]" value="0" class="admin-segmented-input" data-news-is-published @checked($isUnpublished)>
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
        .news-closed-calendar {
            border: 1px solid #E5E0D6;
            border-radius: 0.75rem;
            background: #FBF9F5;
            padding: 0.75rem;
        }
        .news-closed-calendar-nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            margin-bottom: 0.65rem;
        }
        .news-closed-calendar-nav button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.75rem;
            height: 1.75rem;
            border-radius: 0.375rem;
            border: 1px solid #E5E0D6;
            background: #fff;
            color: #556344;
            font-size: 0.875rem;
            line-height: 1;
            cursor: pointer;
        }
        .news-closed-calendar-nav button:hover {
            background: #E5EADD;
        }
        .news-closed-calendar-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: #3F3A34;
        }
        .news-closed-calendar-weekdays,
        .news-closed-calendar-days {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 0.125rem;
        }
        .news-closed-calendar-weekdays span {
            text-align: center;
            font-size: 0.75rem;
            color: #8A8378;
            padding: 0.1rem 0 0.2rem;
            line-height: 1.2;
        }
        .news-closed-calendar-days button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 1.7rem;
            min-height: 1.7rem;
            border: 0;
            border-radius: 0.3rem;
            background: transparent;
            color: #3F3A34;
            font-size: 0.8125rem;
            line-height: 1;
            cursor: pointer;
        }
        .news-closed-calendar-days button:hover:not(:disabled) {
            background: #E5EADD;
        }
        .news-closed-calendar-days button:disabled {
            color: transparent;
            cursor: default;
        }
        .news-closed-calendar-days button.is-selected {
            background: #697A55;
            color: #fff;
            font-weight: 600;
        }
        .news-closed-calendar-days button.is-sunday:not(.is-selected) {
            color: #B45353;
        }
        .news-closed-calendar-days button.is-saturday:not(.is-selected) {
            color: #3B6EA5;
        }
        .news-category-choices {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .news-category-option {
            position: relative;
            display: block;
            margin: 0;
            cursor: pointer;
            flex: 1 1 calc(50% - 0.25rem);
            min-width: min(100%, 8.5rem);
        }
        .news-category-input {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
            appearance: none;
        }
        .news-category-face {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 2.5rem;
            height: 100%;
            padding: 0.5rem 0.625rem;
            border: 1px solid #E5E0D7;
            border-radius: 0.5rem;
            background-color: #ffffff;
            color: #3D3833;
            font-size: 0.75rem;
            line-height: 1.3;
            text-align: center;
            transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .news-category-option:hover .news-category-face {
            background-color: #F7F5F0;
        }
        .news-category-input:checked + .news-category-face {
            background-color: #E5EADD;
            border-color: #697A55;
            color: #556344;
            font-weight: 500;
            box-shadow: inset 0 0 0 1px rgba(105, 122, 85, 0.2);
        }
        .news-category-option:hover .news-category-input:checked + .news-category-face {
            background-color: #DDE5D2;
        }
        .news-category-input:focus-visible + .news-category-face {
            box-shadow: 0 0 0 2px rgba(105, 122, 85, 0.35);
        }
    </style>

    <script>
        (function () {
            const form = document.getElementById('news-bulk-form');
            const grid = document.getElementById('news-grid');
            const deletedIdsWrap = document.getElementById('news-deleted-ids');
            const addCard = document.getElementById('news-add-card');
            const addButton = addCard ? addCard.querySelector('[data-news-add]') : null;
            const addTopButton = document.querySelector('[data-news-add-top]');
            const emptyHeading = '新規お知らせ';
            const categories = @json($categories);
            const closedDateCategories = (@json($closedDateCategoryKeys)).slice();
            const closedWeekdayCategories = (@json($closedWeekdayCategoryKeys)).slice();
            const weekdayShortLabels = @json($weekdayLabels);
            const appTimezone = @json(config('app.timezone'));
            const serverNowMs = {{ (int) now()->getTimestampMs() }};
            const clientPageLoadMs = Date.now();

            if (!form || !grid || !addCard || !addButton) {
                return;
            }

            let nextNewIndex = parseInt(form.getAttribute('data-next-new-index') || '1', 10);
            let maxOrder = parseInt(form.getAttribute('data-max-order') || '0', 10);
            let dragCard = null;

            function pad2(n) {
                return String(n).padStart(2, '0');
            }

            function formatAppDateTimeLocal(date) {
                const parts = new Intl.DateTimeFormat('en-CA', {
                    timeZone: appTimezone,
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                    hourCycle: 'h23',
                }).formatToParts(date);
                const get = function (type) {
                    const part = parts.find(function (item) {
                        return item.type === type;
                    });
                    return part ? part.value : '00';
                };
                return get('year') + '-' + get('month') + '-' + get('day') + 'T' + get('hour') + ':' + get('minute');
            }

            function currentAppDateTimeLocal() {
                const elapsed = Date.now() - clientPageLoadMs;
                return formatAppDateTimeLocal(new Date(serverNowMs + elapsed));
            }

            function fillPublishedAtIfEmpty(card) {
                const publishedAtInput = card.querySelector('[data-news-published-at]');
                if (!publishedAtInput || publishedAtInput.value) {
                    return;
                }
                publishedAtInput.value = currentAppDateTimeLocal();
            }

            function toDateKey(year, monthIndex, day) {
                return year + '-' + pad2(monthIndex + 1) + '-' + pad2(day);
            }

            function formatMonthTitle(year, monthIndex) {
                return year + '年' + (monthIndex + 1) + '月';
            }

            function formatSelectedSummary(dates) {
                if (!dates.length) {
                    return '選択中：なし';
                }
                const labels = dates.map(function (key) {
                    const parts = key.split('-');
                    return parseInt(parts[1], 10) + '/' + parseInt(parts[2], 10);
                });
                return '選択中：' + labels.join('、');
            }

            function categoryChoicesHtml(fieldName, selected) {
                return '<div class="news-category-choices mt-1" role="radiogroup" aria-label="お知らせの種類" data-news-category-group">' +
                    Object.keys(categories).map(function (value) {
                        const checked = value === selected ? ' checked' : '';
                        return '<label class="news-category-option">' +
                            '<input type="radio" name="' + fieldName + '" value="' + value + '" class="news-category-input" data-news-category' + checked + '>' +
                            '<span class="news-category-face">' + categories[value] + '</span>' +
                            '</label>';
                    }).join('') +
                    '</div>';
            }

            function isTitleLocked(card) {
                const input = card.querySelector('[data-news-title-input]');
                return !!(input && input.getAttribute('data-title-locked') === '1');
            }

            function lockTitle(card) {
                const input = card.querySelector('[data-news-title-input]');
                if (input) {
                    input.setAttribute('data-title-locked', '1');
                }
            }

            function currentMonthNumber() {
                return new Date().getMonth() + 1;
            }

            function suggestedTitleForCategory(category) {
                switch (category) {
                    case 'holiday':
                        return '☆' + currentMonthNumber() + '月定休日のお知らせ☆';
                    case 'temporary_closure':
                        return '☆臨時休業のお知らせ☆';
                    case 'hours':
                        return '☆営業時間変更のお知らせ☆';
                    case 'reservation':
                        return '☆予約に関するお知らせ☆';
                    case 'price':
                        return '☆料金改定のお知らせ☆';
                    case 'new_menu':
                        return '☆新メニュー・新サービスのお知らせ☆';
                    case 'product':
                        return '☆商品入荷・取扱開始のお知らせ☆';
                    case 'seasonal':
                        return '☆年末年始・夏季休業のお知らせ☆';
                    case 'important':
                        return '☆重要なお知らせ☆';
                    default:
                        return null;
                }
            }

            function isAutoGeneratedTitle(title) {
                const value = (title || '').trim();
                if (value === '') {
                    return false;
                }
                if (/^☆([1-9]|1[0-2])月定休日のお知らせ☆$/.test(value)) {
                    return true;
                }
                const keys = Object.keys(categories);
                for (let i = 0; i < keys.length; i++) {
                    const key = keys[i];
                    if (key === 'holiday' || key === 'other') {
                        continue;
                    }
                    if (suggestedTitleForCategory(key) === value) {
                        return true;
                    }
                }
                return false;
            }

            function initializeTitleAutoState(card) {
                const titleInput = card.querySelector('[data-news-title-input]');
                if (!titleInput) {
                    return;
                }
                titleInput.removeAttribute('data-title-locked');
                const value = titleInput.value || '';
                if ((value || '').trim() === '') {
                    titleInput.removeAttribute('data-title-auto');
                    return;
                }
                if (isAutoGeneratedTitle(value)) {
                    titleInput.setAttribute('data-title-auto', value);
                    return;
                }
                lockTitle(card);
            }

            function applySuggestedTitle(card, options) {
                const opts = options || {};
                const titleInput = card.querySelector('[data-news-title-input]');
                const checked = card.querySelector('[data-news-category]:checked');
                if (!titleInput || !checked || isTitleLocked(card)) {
                    return;
                }

                const suggestion = suggestedTitleForCategory(checked.value);
                const autoValue = titleInput.getAttribute('data-title-auto') || '';

                if (opts.monthSync) {
                    return;
                }

                // 「その他」は自動入力しない（既存タイトルは維持）
                if (!suggestion) {
                    return;
                }

                titleInput.value = suggestion;
                titleInput.setAttribute('data-title-auto', suggestion);
                syncCardHeading(card);
            }

            function weekdayChoicesHtml(fieldPrefix, selected) {
                const selectedSet = new Set((selected || []).map(function (v) { return String(v); }));
                return '<div class="news-weekday-choices mt-1" role="group" aria-label="定休日">' +
                    Object.keys(weekdayShortLabels).map(function (value) {
                        const checked = selectedSet.has(String(value)) ? ' checked' : '';
                        return '<label class="news-weekday-option">' +
                            '<input type="checkbox" name="' + fieldPrefix + '[closed_weekdays][]" value="' + value + '" class="news-weekday-input" data-news-weekday' + checked + '>' +
                            '<span class="news-weekday-face">' + weekdayShortLabels[value] + '</span>' +
                            '</label>';
                    }).join('') +
                    '</div>';
            }

            function syncClosureVisibility(card) {
                const checked = card.querySelector('[data-news-category]:checked');
                const weekdayWrap = card.querySelector('[data-news-weekday-wrap]');
                const dateWrap = card.querySelector('[data-news-closed-wrap]');
                const value = checked ? checked.value : '';
                if (weekdayWrap) {
                    weekdayWrap.hidden = closedWeekdayCategories.indexOf(value) === -1;
                }
                if (dateWrap) {
                    dateWrap.hidden = closedDateCategories.indexOf(value) === -1;
                }
            }

            function initClosedCalendar(card) {
                const mount = card.querySelector('[data-news-closed-calendar]');
                const summary = card.querySelector('[data-news-closed-summary]');
                if (!mount || mount.getAttribute('data-calendar-ready') === '1') {
                    syncClosureVisibility(card);
                    return;
                }

                const fieldPrefix = mount.getAttribute('data-field-prefix') || '';
                const initial = (mount.getAttribute('data-selected-dates') || '')
                    .split(',')
                    .map(function (v) { return v.trim(); })
                    .filter(Boolean);
                const selected = new Set(initial);
                const now = new Date();
                let viewYear = now.getFullYear();
                let viewMonth = now.getMonth();

                if (initial.length) {
                    const first = initial[0].split('-');
                    viewYear = parseInt(first[0], 10);
                    viewMonth = parseInt(first[1], 10) - 1;
                }

                mount.setAttribute('data-calendar-ready', '1');
                mount.innerHTML =
                    '<div class="news-closed-calendar-nav">' +
                        '<button type="button" data-cal-prev aria-label="前月">‹</button>' +
                        '<div class="news-closed-calendar-title" data-cal-title></div>' +
                        '<button type="button" data-cal-next aria-label="翌月">›</button>' +
                    '</div>' +
                    '<div class="news-closed-calendar-weekdays">' +
                        '<span>日</span><span>月</span><span>火</span><span>水</span><span>木</span><span>金</span><span>土</span>' +
                    '</div>' +
                    '<div class="news-closed-calendar-days" data-cal-days></div>' +
                    '<div data-cal-inputs></div>';

                const titleEl = mount.querySelector('[data-cal-title]');
                const daysEl = mount.querySelector('[data-cal-days]');
                const inputsEl = mount.querySelector('[data-cal-inputs]');

                function syncInputsAndSummary() {
                    const sorted = Array.from(selected).sort();
                    inputsEl.innerHTML = sorted.map(function (date) {
                        return '<input type="hidden" name="' + fieldPrefix + '[closed_dates][]" value="' + date + '">';
                    }).join('');
                    if (summary) {
                        summary.textContent = formatSelectedSummary(sorted);
                    }
                    mount.setAttribute('data-selected-dates', sorted.join(','));
                }

                function render(fromMonthNav) {
                    mount.setAttribute('data-view-year', String(viewYear));
                    mount.setAttribute('data-view-month', String(viewMonth + 1));
                    titleEl.textContent = formatMonthTitle(viewYear, viewMonth);
                    const firstDow = new Date(viewYear, viewMonth, 1).getDay();
                    const daysInMonth = new Date(viewYear, viewMonth + 1, 0).getDate();
                    let html = '';
                    for (let i = 0; i < firstDow; i++) {
                        html += '<button type="button" disabled aria-hidden="true"></button>';
                    }
                    for (let day = 1; day <= daysInMonth; day++) {
                        const key = toDateKey(viewYear, viewMonth, day);
                        const dow = (firstDow + day - 1) % 7;
                        const classes = [];
                        if (selected.has(key)) {
                            classes.push('is-selected');
                        }
                        if (dow === 0) {
                            classes.push('is-sunday');
                        }
                        if (dow === 6) {
                            classes.push('is-saturday');
                        }
                        html +=
                            '<button type="button" data-cal-day="' + key + '"' +
                            (classes.length ? ' class="' + classes.join(' ') + '"' : '') +
                            ' aria-pressed="' + (selected.has(key) ? 'true' : 'false') + '">' +
                            day +
                            '</button>';
                    }
                    daysEl.innerHTML = html;
                    syncInputsAndSummary();
                    if (fromMonthNav) {
                        applySuggestedTitle(card, { monthSync: true });
                    }
                }

                mount.querySelector('[data-cal-prev]').addEventListener('click', function () {
                    viewMonth -= 1;
                    if (viewMonth < 0) {
                        viewMonth = 11;
                        viewYear -= 1;
                    }
                    render(true);
                });
                mount.querySelector('[data-cal-next]').addEventListener('click', function () {
                    viewMonth += 1;
                    if (viewMonth > 11) {
                        viewMonth = 0;
                        viewYear += 1;
                    }
                    render(true);
                });
                daysEl.addEventListener('click', function (e) {
                    const btn = e.target.closest('[data-cal-day]');
                    if (!btn || !daysEl.contains(btn)) {
                        return;
                    }
                    const key = btn.getAttribute('data-cal-day');
                    if (selected.has(key)) {
                        selected.delete(key);
                    } else {
                        selected.add(key);
                    }
                    render(false);
                });

                render(false);
                syncClosureVisibility(card);
            }

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
                const categoryInputs = card.querySelectorAll('[data-news-category]');
                const publishInputs = card.querySelectorAll('[data-news-is-published]');

                titleInput?.addEventListener('input', function () {
                    lockTitle(card);
                    syncCardHeading(card);
                });
                syncCardHeading(card);

                categoryInputs.forEach(function (input) {
                    input.addEventListener('change', function () {
                        syncClosureVisibility(card);
                        applySuggestedTitle(card);
                    });
                });

                publishInputs.forEach(function (input) {
                    input.addEventListener('change', function () {
                        if (input.value === '1' && input.checked) {
                            fillPublishedAtIfEmpty(card);
                        }
                    });
                });

                initClosedCalendar(card);
                initializeTitleAutoState(card);

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

            function createEmptyCard(placement) {
                const insertAtStart = placement === 'start';
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
                            '<span class="admin-label">お知らせの種類 <span class="admin-required-badge">必須</span></span>' +
                            categoryChoicesHtml('new_news[' + key + '][category]', 'other') +
                        '</div>' +
                        '<div>' +
                            '<label class="admin-label">タイトル <span class="admin-required-badge">必須</span></label>' +
                            '<input type="text" name="new_news[' + key + '][title]" value="" maxlength="255" class="admin-input" data-news-title-input>' +
                        '</div>' +
                        '<div data-news-weekday-wrap hidden>' +
                            '<span class="admin-label">定休日 <span class="admin-required-badge">必須</span></span>' +
                            weekdayChoicesHtml('new_news[' + key + ']', []) +
                        '</div>' +
                        '<div data-news-closed-wrap hidden>' +
                            '<span class="admin-label">休業日 <span class="admin-required-badge">必須</span></span>' +
                            '<div class="news-closed-calendar mt-1" data-news-closed-calendar data-field-prefix="new_news[' + key + ']" data-selected-dates=""></div>' +
                            '<p class="mt-2 text-xs text-admin-muted" data-news-closed-summary></p>' +
                        '</div>' +
                        '<div>' +
                            '<label class="admin-label">本文</label>' +
                            '<textarea name="new_news[' + key + '][body]" rows="6" class="admin-input"></textarea>' +
                        '</div>' +
                        '<div>' +
                            '<label class="admin-label">公開日時 <span class="admin-required-badge">必須</span></label>' +
                            '<input type="datetime-local" name="new_news[' + key + '][published_at]" value="" class="admin-input" data-news-published-at>' +
                            '<p class="mt-1 text-xs text-admin-muted">公開開始日時を指定してください。</p>' +
                        '</div>' +
                        '<div>' +
                            '<span class="admin-label">公開 <span class="admin-required-badge">必須</span></span>' +
                            '<div class="admin-segmented mt-1" role="radiogroup" aria-label="公開状態">' +
                                '<label class="admin-segmented-option">' +
                                    '<input type="radio" name="new_news[' + key + '][is_published]" value="1" class="admin-segmented-input" data-news-is-published>' +
                                    '<span class="admin-segmented-face">' +
                                        '<svg class="admin-segmented-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">' +
                                            '<path d="M1.5 8s2.5-4.5 6.5-4.5S14.5 8 14.5 8s-2.5 4.5-6.5 4.5S1.5 8 1.5 8z" stroke="currentColor" stroke-width="1.35" stroke-linejoin="round"/>' +
                                            '<circle cx="8" cy="8" r="2" stroke="currentColor" stroke-width="1.35"/>' +
                                        '</svg>' +
                                        '<span class="admin-segmented-text">公開</span>' +
                                    '</span>' +
                                '</label>' +
                                '<label class="admin-segmented-option">' +
                                    '<input type="radio" name="new_news[' + key + '][is_published]" value="0" class="admin-segmented-input" data-news-is-published>' +
                                    '<span class="admin-segmented-face">' +
                                        '<svg class="admin-segmented-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">' +
                                            '<path d="M2 2.5 13.5 13.5" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/>' +
                                            '<path d="M6.7 4.1A6.4 6.4 0 0 1 8 3.5c4 0 6.5 4.5 6.5 4.5a10.3 10.3 0 0 1-2.15 2.55M4.2 5.85A10.2 10.2 0 0 0 1.5 8S4 12.5 8 12.5c.7 0 1.35-.12 1.95-.34" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/>' +
                                            '<path d="M6.65 7.1a2 2 0 0 0 2.35 2.35" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/>' +
                                        '</svg>' +
                                        '<span class="admin-segmented-text">非公開</span>' +
                                    '</span>' +
                                '</label>' +
                            '</div>' +
                        '</div>' +
                    '</div>';

                if (insertAtStart) {
                    const firstCard = grid.querySelector('[data-news-card]');
                    if (firstCard) {
                        firstCard.before(card);
                    } else {
                        addCard.before(card);
                    }
                } else {
                    addCard.before(card);
                }

                bindCard(card);
                syncDisplayOrders();

                if (insertAtStart) {
                    requestAnimationFrame(function () {
                        card.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
                    });
                }
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
                createEmptyCard('end');
            });

            if (addTopButton) {
                addTopButton.addEventListener('click', function (e) {
                    e.preventDefault();
                    createEmptyCard('start');
                });
            }
        })();
    </script>
@endsection
