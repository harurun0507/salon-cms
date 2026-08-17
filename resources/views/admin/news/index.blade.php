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
                data-confirm-note="営業カレンダーの色、タイトル、お知らせの種類、定休日／休業日／営業時間変更、本文、公開日時、公開状態、表示順、削除など、現在入力されている内容が反映されます。"
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

        $formatTime = function ($value) {
            if ($value === null || $value === '') {
                return '';
            }
            try {
                return \Illuminate\Support\Carbon::parse($value)->format('H:i');
            } catch (\Throwable) {
                return '';
            }
        };

        $calendarColorDefaults = [
            'calendar_holiday_color' => \App\Models\DesignSetting::DEFAULTS['calendar_holiday_color'],
            'calendar_temporary_color' => \App\Models\DesignSetting::DEFAULTS['calendar_temporary_color'],
            'calendar_hours_color' => \App\Models\DesignSetting::DEFAULTS['calendar_hours_color'],
        ];

        $regularBusinessHours = $regularBusinessHours ?? [
            'weekday' => ['open' => '', 'close' => ''],
            'weekend' => ['open' => '', 'close' => ''],
            'holidayDates' => [],
        ];

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

        $deletedIds = collect(old('deleted_ids', []))
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values()
            ->all();

        // Preserve submitted card order (display_order) across validation redirects.
        $workspaceCards = [];
        $workspaceIndex = 0;
        foreach ($newsList as $news) {
            if (in_array((string) $news->id, $deletedIds, true)) {
                continue;
            }

            $workspaceCards[] = [
                'kind' => 'existing',
                'order' => (int) old('news.'.$news->id.'.display_order', $news->display_order),
                'index' => $workspaceIndex++,
                'news' => $news,
            ];
        }
        foreach ($oldNewNews as $key => $newItem) {
            if (! is_array($newItem)) {
                continue;
            }

            $workspaceCards[] = [
                'kind' => 'new',
                'order' => (int) old('new_news.'.$key.'.display_order', $newItem['display_order'] ?? PHP_INT_MAX),
                'index' => $workspaceIndex++,
                'key' => (string) $key,
                'newItem' => $newItem,
            ];
        }
        usort($workspaceCards, function (array $a, array $b): int {
            if ($a['order'] !== $b['order']) {
                return $a['order'] <=> $b['order'];
            }

            return $a['index'] <=> $b['index'];
        });

        $maxOrder = max($maxOrder, count($workspaceCards));
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

        <div id="news-deleted-ids">
            @foreach($deletedIds as $deletedId)
                <input type="hidden" name="deleted_ids[]" value="{{ $deletedId }}">
            @endforeach
        </div>

        @include('admin.news.partials.calendar-settings', [
            'calendarColors' => $calendarColors ?? \App\Models\DesignSetting::current()->resolvedBusinessCalendarColors(),
        ])

        <div id="news-grid" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3" data-news-grid>
            @foreach($workspaceCards as $workspaceCard)
                @if($workspaceCard['kind'] === 'existing')
                    @include('admin.news.partials.card-existing', [
                        'news' => $workspaceCard['news'],
                        'formatLocal' => $formatLocal,
                        'formatTime' => $formatTime,
                        'categories' => $categories,
                        'weekdayLabels' => $weekdayLabels,
                        'normalizeClosedDates' => $normalizeClosedDates,
                        'normalizeClosedWeekdays' => $normalizeClosedWeekdays,
                        'usesClosedDates' => $usesClosedDates,
                        'usesClosedWeekdays' => $usesClosedWeekdays,
                    ])
                @else
                    @include('admin.news.partials.card-new', [
                        'key' => $workspaceCard['key'],
                        'newItem' => $workspaceCard['newItem'],
                        'formatTime' => $formatTime,
                        'categories' => $categories,
                        'weekdayLabels' => $weekdayLabels,
                        'normalizeClosedDates' => $normalizeClosedDates,
                        'normalizeClosedWeekdays' => $normalizeClosedWeekdays,
                        'usesClosedDates' => $usesClosedDates,
                        'usesClosedWeekdays' => $usesClosedWeekdays,
                    ])
                @endif
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
            const holidayPeriodTypes = @json(\App\Models\News::HOLIDAY_PERIOD_TYPES);
            const holidayPeriodMonths = @json(\App\Models\News::HOLIDAY_PERIOD_MONTHS);
            const salonClosedWeekdays = @json(\App\Models\SalonSetting::current()->closedWeekdayValues());
            const salonClosedNth = @json(\App\Models\SalonSetting::current()->closedNthWeekdayRules());
            const calendarColorDefaults = @json($calendarColorDefaults);
            const regularBusinessHours = @json($regularBusinessHours);
            const holidayDateSet = new Set((regularBusinessHours.holidayDates || []).map(String));
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

            function usesWeekendBusinessHours(dateValue) {
                if (!dateValue || !/^\d{4}-\d{2}-\d{2}$/.test(dateValue)) {
                    return false;
                }
                if (holidayDateSet.has(dateValue)) {
                    return true;
                }
                const parts = dateValue.split('-');
                const date = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
                const day = date.getDay();
                return day === 0 || day === 6;
            }

            function regularHoursForDate(dateValue) {
                const bucket = usesWeekendBusinessHours(dateValue) ? 'weekend' : 'weekday';
                const hours = regularBusinessHours[bucket] || {};
                return {
                    open: hours.open || '',
                    close: hours.close || '',
                    bucket: bucket,
                };
            }

            function isNewNewsCard(card) {
                return card.getAttribute('data-news-new') === '1';
            }

            function areHoursTimesManual(card) {
                const start = card.querySelector('[data-news-hours-start]');
                const end = card.querySelector('[data-news-hours-end]');
                if (!start || !end) {
                    return true;
                }
                return start.getAttribute('data-hours-manual') === '1'
                    || end.getAttribute('data-hours-manual') === '1';
            }

            function markHoursTimesAuto(card, isAuto) {
                const start = card.querySelector('[data-news-hours-start]');
                const end = card.querySelector('[data-news-hours-end]');
                [start, end].forEach(function (input) {
                    if (!input) {
                        return;
                    }
                    if (isAuto) {
                        input.setAttribute('data-hours-auto', '1');
                        input.removeAttribute('data-hours-manual');
                    } else {
                        input.removeAttribute('data-hours-auto');
                    }
                });
            }

            function lockHoursTimes(card) {
                const start = card.querySelector('[data-news-hours-start]');
                const end = card.querySelector('[data-news-hours-end]');
                [start, end].forEach(function (input) {
                    if (!input) {
                        return;
                    }
                    input.setAttribute('data-hours-manual', '1');
                    input.removeAttribute('data-hours-auto');
                });
            }

            function applyRegularHoursDefaults(card, options) {
                const opts = options || {};
                if (!isNewNewsCard(card)) {
                    return;
                }
                const checked = card.querySelector('[data-news-category]:checked');
                if (!checked || checked.value !== 'hours') {
                    return;
                }
                const dateInput = card.querySelector('[data-news-hours-change-date]');
                const start = card.querySelector('[data-news-hours-start]');
                const end = card.querySelector('[data-news-hours-end]');
                if (!dateInput || !start || !end || !dateInput.value) {
                    return;
                }
                if (!opts.force && areHoursTimesManual(card)) {
                    return;
                }
                const bothEmpty = !start.value && !end.value;
                const wasAuto = start.getAttribute('data-hours-auto') === '1'
                    && end.getAttribute('data-hours-auto') === '1';
                if (!opts.force && !bothEmpty && !wasAuto) {
                    return;
                }

                const hours = regularHoursForDate(dateInput.value);
                if (!hours.open && !hours.close) {
                    return;
                }
                start.value = hours.open;
                end.value = hours.close;
                markHoursTimesAuto(card, true);
            }

            function formatDateYmd(date) {
                return date.getFullYear() + '-' + pad2(date.getMonth() + 1) + '-' + pad2(date.getDate());
            }

            function formatDateSlash(ymd) {
                if (!ymd || !/^\d{4}-\d{2}-\d{2}$/.test(ymd)) {
                    return '';
                }
                return ymd.replace(/-/g, '/');
            }

            function defaultHolidayPeriodFrom() {
                const now = new Date();
                return now.getFullYear() + '-' + pad2(now.getMonth() + 1) + '-01';
            }

            function computeHolidayPeriodTo(fromYmd, periodType) {
                const months = holidayPeriodMonths[periodType];
                if (!months || !fromYmd) {
                    return '';
                }
                const parts = fromYmd.split('-').map(Number);
                if (parts.length !== 3) {
                    return '';
                }
                const from = new Date(parts[0], parts[1] - 1, parts[2]);
                const to = new Date(from.getFullYear(), from.getMonth() + months, from.getDate());
                to.setDate(to.getDate() - 1);
                return formatDateYmd(to);
            }

            function holidayPeriodTypeChoicesHtml(fieldName, selected) {
                const selectedValue = selected || '1_year';
                let html = '';
                Object.keys(holidayPeriodTypes).forEach(function (value) {
                    const checked = value === selectedValue ? ' checked' : '';
                    html += '<label class="admin-segmented-option">' +
                        '<input type="radio" name="' + fieldName + '" value="' + value + '" class="admin-segmented-input" data-news-holiday-period-type' + checked + '>' +
                        '<span class="admin-segmented-face"><span class="admin-segmented-text">' + holidayPeriodTypes[value] + '</span></span>' +
                        '</label>';
                });
                return '<div class="admin-segmented mt-1" role="radiogroup" aria-label="対象期間" data-news-holiday-period-type-group">' + html + '</div>';
            }

            function holidayClosedDaysFieldsHtml(fieldPrefix) {
                const weekdays = Array.isArray(salonClosedWeekdays) ? salonClosedWeekdays : [];
                const nthRules = Array.isArray(salonClosedNth) ? salonClosedNth : [];
                let weekdayHtml = '<div class="news-weekday-choices mt-1 notranslate" role="group" aria-label="毎週の定休日" translate="no" lang="ja">';
                Object.keys(weekdayShortLabels).forEach(function (key) {
                    const value = Number(key);
                    const checked = weekdays.indexOf(value) !== -1 ? ' checked' : '';
                    weekdayHtml += '<label class="news-weekday-option">' +
                        '<input type="checkbox" name="' + fieldPrefix + '[closed_weekdays][]" value="' + value + '" class="news-weekday-input" data-news-holiday-weekday' + checked + '>' +
                        '<span class="news-weekday-face">' + weekdayShortLabels[key] + '</span>' +
                        '</label>';
                });
                weekdayHtml += '</div>';

                let nthHtml = '';
                nthRules.forEach(function (rule, index) {
                    nthHtml += holidayClosedNthRowHtml(fieldPrefix, index, rule.week, rule.weekday);
                });

                return '<div class="space-y-3" data-news-holiday-closed-days>' +
                    '<span class="admin-label">定休日 <span class="admin-required-badge">必須</span></span>' +
                    '<div class="mt-1 space-y-4">' +
                        '<div><p class="text-sm text-admin-text">毎週</p>' + weekdayHtml + '</div>' +
                        '<div>' +
                            '<p class="text-sm text-admin-text">追加定休日（第○週の○曜日）</p>' +
                            '<div class="mt-2 space-y-2 notranslate" data-news-closed-nth-list translate="no" lang="ja">' + nthHtml + '</div>' +
                            '<select class="sr-only notranslate" aria-hidden="true" tabindex="-1" translate="no" lang="ja" data-news-closed-nth-week-labels>' +
                                '<option value="1">第1週</option><option value="2">第2週</option><option value="3">第3週</option>' +
                                '<option value="4">第4週</option><option value="5">第5週</option>' +
                            '</select>' +
                            '<button type="button" class="admin-btn-secondary mt-2 text-sm" data-news-closed-nth-add>＋ 追加定休日を追加</button>' +
                            '<p class="mt-1 text-xs text-admin-muted">例：第3水曜日、第1・第3水曜日。初期値は店舗情報の基本情報です。</p>' +
                        '</div>' +
                    '</div>' +
                '</div>';
            }

            function holidayClosedNthRowHtml(fieldPrefix, index, week, weekday) {
                const selectedWeek = Number(week) || 1;
                const selectedWeekday = Number(weekday);
                let weekOptions = '';
                for (let w = 1; w <= 5; w += 1) {
                    weekOptions += '<option value="' + w + '"' + (w === selectedWeek ? ' selected' : '') + '>第' + w + '週</option>';
                }
                let weekdayOptions = '';
                Object.keys(weekdayShortLabels).forEach(function (key) {
                    const value = Number(key);
                    weekdayOptions += '<option value="' + value + '"' + (value === selectedWeekday ? ' selected' : '') + '>' + weekdayShortLabels[key] + '</option>';
                });
                return '<div class="flex flex-wrap items-center gap-2" data-news-closed-nth-row>' +
                    '<select name="' + fieldPrefix + '[closed_nth][' + index + '][week]" class="admin-input max-w-[7.5rem] notranslate" aria-label="週" translate="no" lang="ja">' + weekOptions + '</select>' +
                    '<select name="' + fieldPrefix + '[closed_nth][' + index + '][weekday]" class="admin-input max-w-[7rem] notranslate" aria-label="曜日" translate="no" lang="ja">' + weekdayOptions + '</select>' +
                    '<button type="button" class="admin-icon-btn admin-icon-btn-delete" data-news-closed-nth-remove aria-label="削除" title="削除"><span aria-hidden="true">&times;</span></button>' +
                '</div>';
            }

            function holidayPeriodFieldsHtml(fieldPrefix) {
                const from = defaultHolidayPeriodFrom();
                const to = computeHolidayPeriodTo(from, '1_year');
                return '<div class="space-y-3" data-news-holiday-period-wrap hidden>' +
                    '<div>' +
                        '<span class="admin-label">対象期間 <span class="admin-required-badge">必須</span></span>' +
                        holidayPeriodTypeChoicesHtml(fieldPrefix + '[holiday_period_type]', '1_year') +
                    '</div>' +
                    '<div class="space-y-2" data-news-holiday-period-range>' +
                        '<span class="admin-label">From ～ To</span>' +
                        '<div class="flex flex-wrap items-center gap-2">' +
                            '<input type="date" name="' + fieldPrefix + '[holiday_period_from]" value="' + from + '" class="admin-input max-w-[11rem]" data-news-holiday-period-from aria-label="開始日">' +
                            '<span class="text-sm text-admin-muted" aria-hidden="true">～</span>' +
                            '<input type="date" name="' + fieldPrefix + '[holiday_period_to]" value="' + to + '" class="admin-input max-w-[11rem]" data-news-holiday-period-to data-period-auto="1" aria-label="終了日">' +
                        '</div>' +
                        '<p class="text-sm text-admin-text" data-news-holiday-period-preview></p>' +
                    '</div>' +
                    holidayClosedDaysFieldsHtml(fieldPrefix) +
                '</div>';
            }

            function setWrapInputsDisabled(wrap, disabled) {
                if (!wrap) {
                    return;
                }
                wrap.querySelectorAll('input, select, textarea, button').forEach(function (el) {
                    if (el.matches('[data-news-closed-nth-add], [data-news-closed-nth-remove]')) {
                        el.disabled = disabled;
                        return;
                    }
                    if (el.tagName === 'BUTTON') {
                        return;
                    }
                    el.disabled = disabled;
                });
            }

            function syncHolidayPeriodPreview(card) {
                const fromInput = card.querySelector('[data-news-holiday-period-from]');
                const toInput = card.querySelector('[data-news-holiday-period-to]');
                const preview = card.querySelector('[data-news-holiday-period-preview]');
                if (!preview) {
                    return;
                }
                const fromLabel = formatDateSlash(fromInput ? fromInput.value : '');
                const toLabel = formatDateSlash(toInput ? toInput.value : '');
                preview.textContent = (fromLabel && toLabel) ? (fromLabel + ' ～ ' + toLabel) : '';
            }

            function applyHolidayPeriodAutoTo(card, force) {
                const wrap = card.querySelector('[data-news-holiday-period-wrap]');
                if (!wrap || wrap.hidden) {
                    return;
                }
                const typeInput = card.querySelector('[data-news-holiday-period-type]:checked');
                const fromInput = card.querySelector('[data-news-holiday-period-from]');
                const toInput = card.querySelector('[data-news-holiday-period-to]');
                if (!typeInput || !fromInput || !toInput || !fromInput.value) {
                    syncHolidayPeriodPreview(card);
                    return;
                }
                if (typeInput.value === 'custom') {
                    syncHolidayPeriodPreview(card);
                    return;
                }
                const autoTo = computeHolidayPeriodTo(fromInput.value, typeInput.value);
                if (!autoTo) {
                    syncHolidayPeriodPreview(card);
                    return;
                }
                const wasAuto = toInput.getAttribute('data-period-auto') === '1';
                if (force || !toInput.value || wasAuto) {
                    toInput.value = autoTo;
                    toInput.setAttribute('data-period-auto', '1');
                }
                syncHolidayPeriodPreview(card);
            }

            function syncClosureVisibility(card) {
                const checked = card.querySelector('[data-news-category]:checked');
                const weekdayWrap = card.querySelector('[data-news-weekday-wrap]');
                const dateWrap = card.querySelector('[data-news-closed-wrap]');
                const hoursWrap = card.querySelector('[data-news-hours-wrap]');
                const holidayPeriodWrap = card.querySelector('[data-news-holiday-period-wrap]');
                const bodyLabel = card.querySelector('[data-news-body-label]');
                const bodyHint = card.querySelector('[data-news-body-hint]');
                const value = checked ? checked.value : '';
                const isHours = value === 'hours';
                const isHoliday = value === 'holiday';
                if (weekdayWrap) {
                    weekdayWrap.hidden = closedWeekdayCategories.indexOf(value) === -1;
                    setWrapInputsDisabled(weekdayWrap, weekdayWrap.hidden);
                }
                if (dateWrap) {
                    dateWrap.hidden = closedDateCategories.indexOf(value) === -1;
                    setWrapInputsDisabled(dateWrap, dateWrap.hidden);
                }
                if (hoursWrap) {
                    hoursWrap.hidden = !isHours;
                    setWrapInputsDisabled(hoursWrap, hoursWrap.hidden);
                }
                if (holidayPeriodWrap) {
                    holidayPeriodWrap.hidden = !isHoliday;
                    setWrapInputsDisabled(holidayPeriodWrap, holidayPeriodWrap.hidden);
                    if (isHoliday) {
                        applyHolidayPeriodAutoTo(card, false);
                    }
                }
                if (bodyLabel) {
                    bodyLabel.textContent = isHours ? '補足説明（任意）' : '本文';
                }
                if (bodyHint) {
                    bodyHint.hidden = !isHours;
                }
            }

            function normalizeHex(value) {
                let hex = String(value || '').trim().toLowerCase();
                if (hex && hex.charAt(0) !== '#') {
                    hex = '#' + hex;
                }
                return hex;
            }

            function bindCalendarColorFields() {
                form.querySelectorAll('[data-news-color-field]').forEach(function (field) {
                    const swatch = field.querySelector('[data-news-color-swatch]');
                    const hex = field.querySelector('[data-news-color-hex]');
                    if (!swatch || !hex || field.getAttribute('data-color-bound') === '1') {
                        return;
                    }
                    field.setAttribute('data-color-bound', '1');
                    swatch.addEventListener('input', function () {
                        hex.value = normalizeHex(swatch.value);
                    });
                    hex.addEventListener('change', function () {
                        const normalized = normalizeHex(hex.value);
                        if (/^#[0-9a-f]{6}$/.test(normalized)) {
                            hex.value = normalized;
                            swatch.value = normalized;
                        }
                    });
                });
            }

            function resetCalendarColorFields() {
                Object.keys(calendarColorDefaults).forEach(function (name) {
                    const hexInput = form.querySelector('[data-news-color-hex][name="' + name + '"]');
                    if (!hexInput) {
                        return;
                    }
                    const value = normalizeHex(calendarColorDefaults[name]);
                    hexInput.value = value;
                    const field = hexInput.closest('[data-news-color-field]');
                    const swatch = field ? field.querySelector('[data-news-color-swatch]') : null;
                    if (swatch) {
                        swatch.value = value;
                    }
                });
            }

            document.addEventListener('news-calendar-colors-reset', function () {
                resetCalendarColorFields();
            });

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
                const hoursDateInput = card.querySelector('[data-news-hours-change-date]');
                const hoursStartInput = card.querySelector('[data-news-hours-start]');
                const hoursEndInput = card.querySelector('[data-news-hours-end]');

                titleInput?.addEventListener('input', function () {
                    lockTitle(card);
                    syncCardHeading(card);
                });
                syncCardHeading(card);

                categoryInputs.forEach(function (input) {
                    input.addEventListener('change', function () {
                        syncClosureVisibility(card);
                        applySuggestedTitle(card);
                        applyRegularHoursDefaults(card);
                    });
                });

                card.querySelectorAll('[data-news-holiday-period-type]').forEach(function (input) {
                    input.addEventListener('change', function () {
                        const toInput = card.querySelector('[data-news-holiday-period-to]');
                        if (toInput) {
                            toInput.setAttribute('data-period-auto', '1');
                        }
                        applyHolidayPeriodAutoTo(card, true);
                    });
                });
                card.querySelector('[data-news-holiday-period-from]')?.addEventListener('change', function () {
                    const toInput = card.querySelector('[data-news-holiday-period-to]');
                    if (toInput) {
                        toInput.setAttribute('data-period-auto', '1');
                    }
                    applyHolidayPeriodAutoTo(card, true);
                });
                card.querySelector('[data-news-holiday-period-to]')?.addEventListener('input', function () {
                    this.setAttribute('data-period-auto', '0');
                    syncHolidayPeriodPreview(card);
                });
                card.querySelector('[data-news-holiday-period-to]')?.addEventListener('change', function () {
                    this.setAttribute('data-period-auto', '0');
                    syncHolidayPeriodPreview(card);
                });
                applyHolidayPeriodAutoTo(card, false);

                publishInputs.forEach(function (input) {
                    input.addEventListener('change', function () {
                        if (input.value === '1' && input.checked) {
                            fillPublishedAtIfEmpty(card);
                        }
                    });
                });

                hoursDateInput?.addEventListener('change', function () {
                    applyRegularHoursDefaults(card);
                });
                [hoursStartInput, hoursEndInput].forEach(function (input) {
                    input?.addEventListener('input', function () {
                        if (!isNewNewsCard(card)) {
                            return;
                        }
                        lockHoursTimes(card);
                    });
                });

                initClosedCalendar(card);
                initializeTitleAutoState(card);
                syncClosureVisibility(card);

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
                        holidayPeriodFieldsHtml('new_news[' + key + ']') +
                        '<div data-news-closed-wrap hidden>' +
                            '<span class="admin-label">休業日 <span class="admin-required-badge">必須</span></span>' +
                            '<div class="news-closed-calendar mt-1" data-news-closed-calendar data-field-prefix="new_news[' + key + ']" data-selected-dates=""></div>' +
                            '<p class="mt-2 text-xs text-admin-muted" data-news-closed-summary></p>' +
                        '</div>' +
                        '<div class="space-y-3" data-news-hours-wrap hidden>' +
                            '<div>' +
                                '<label class="admin-label">変更日 <span class="admin-required-badge">必須</span></label>' +
                                '<input type="date" name="new_news[' + key + '][hours_change_date]" value="" class="admin-input" data-news-hours-change-date>' +
                            '</div>' +
                            '<div>' +
                                '<span class="admin-label">営業時間 <span class="admin-required-badge">必須</span></span>' +
                                '<div class="mt-1 flex flex-wrap items-center gap-2">' +
                                    '<input type="time" name="new_news[' + key + '][hours_start_time]" value="" class="admin-input max-w-[9rem]" data-news-hours-start aria-label="開始時間">' +
                                    '<span class="text-sm text-admin-muted" aria-hidden="true">〜</span>' +
                                    '<input type="time" name="new_news[' + key + '][hours_end_time]" value="" class="admin-input max-w-[9rem]" data-news-hours-end aria-label="終了時間">' +
                                '</div>' +
                                '<p class="mt-1 text-xs text-admin-muted">変更日に応じて基本情報の通常営業時間（平日／土日祝）を初期表示します。この日だけ変える場合に編集してください。公開カレンダーには変更後の時間が表示されます。</p>' +
                            '</div>' +
                        '</div>' +
                        '<div data-news-body-wrap>' +
                            '<label class="admin-label" data-news-body-label>本文</label>' +
                            '<textarea name="new_news[' + key + '][body]" rows="6" class="admin-input"></textarea>' +
                            '<p class="mt-1 text-xs text-admin-muted" data-news-body-hint hidden>営業時間は上の専用項目で登録します。追加の案内がある場合のみ入力してください。</p>' +
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

                if (window.AdminWorkspaceCards && typeof window.AdminWorkspaceCards.insert === 'function') {
                    window.AdminWorkspaceCards.insert(card, {
                        grid: grid,
                        addCard: addCard,
                        cardSelector: '[data-news-card]',
                        placement: insertAtStart ? 'start' : 'end',
                    });
                } else if (insertAtStart) {
                    const firstCard = grid.querySelector('[data-news-card]');
                    if (firstCard) {
                        firstCard.before(card);
                    } else {
                        addCard.before(card);
                    }
                    requestAnimationFrame(function () {
                        card.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
                    });
                } else {
                    addCard.before(card);
                }

                bindCard(card);
                syncDisplayOrders();
            }

            form.addEventListener('click', function (event) {
                const addBtn = event.target.closest('[data-news-closed-nth-add]');
                if (addBtn && form.contains(addBtn)) {
                    const card = addBtn.closest('[data-news-card]');
                    const list = card ? card.querySelector('[data-news-closed-nth-list]') : null;
                    const periodWrap = card ? card.querySelector('[data-news-holiday-period-wrap]') : null;
                    if (!list || !periodWrap || periodWrap.hidden) {
                        return;
                    }
                    const orderInput = card.querySelector('[data-news-order]');
                    const fieldPrefix = orderInput && orderInput.name
                        ? orderInput.name.replace(/\[display_order\]$/, '')
                        : null;
                    if (!fieldPrefix) {
                        return;
                    }
                    const index = list.querySelectorAll('[data-news-closed-nth-row]').length;
                    list.insertAdjacentHTML('beforeend', holidayClosedNthRowHtml(fieldPrefix, index, 1, 1));
                    return;
                }

                const removeBtn = event.target.closest('[data-news-closed-nth-remove]');
                if (removeBtn && form.contains(removeBtn)) {
                    const row = removeBtn.closest('[data-news-closed-nth-row]');
                    if (row) {
                        row.remove();
                    }
                }
            });

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

            bindCalendarColorFields();
        })();
    </script>
@endsection
