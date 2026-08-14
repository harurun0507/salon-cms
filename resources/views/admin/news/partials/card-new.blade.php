                @php
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
                    $hoursChangeDate = old($prefix.'.hours_change_date', $newItem['hours_change_date'] ?? '');
                    $hoursStartTime = old($prefix.'.hours_start_time', $formatTime($newItem['hours_start_time'] ?? null));
                    $hoursEndTime = old($prefix.'.hours_end_time', $formatTime($newItem['hours_end_time'] ?? null));
                    $isHoursCategory = \App\Models\News::usesHoursChangeFields($category);
                    // New card only: seed from salon regular hours when date is known and times are still blank.
                    if (
                        $isHoursCategory
                        && filled($hoursChangeDate)
                        && $hoursStartTime === ''
                        && $hoursEndTime === ''
                        && empty(session()->getOldInput())
                    ) {
                        try {
                            $regularHours = \App\Models\SalonSetting::current()->regularHoursForDate(
                                \Illuminate\Support\Carbon::parse((string) $hoursChangeDate)
                            );
                            $hoursStartTime = $regularHours['open'];
                            $hoursEndTime = $regularHours['close'];
                        } catch (\Throwable) {
                            // keep empty
                        }
                    }
                    $hoursTimesAreAuto = $isHoursCategory
                        && $hoursStartTime !== ''
                        && $hoursEndTime !== ''
                        && empty(session()->getOldInput());
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
                        <p
                            class="text-xs text-admin-muted"
                            data-news-holiday-hint
                            @if((string) $category !== \App\Models\News::CATEGORY_HOLIDAY) hidden @endif
                        >
                            通常の定休日は「店舗情報」の基本情報で設定します。こちらは告知用のお知らせです。
                        </p>
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
                        <div
                            class="space-y-3"
                            data-news-hours-wrap
                            @if(! $isHoursCategory) hidden @endif
                        >
                            <div>
                                <label class="admin-label">変更日 <span class="admin-required-badge">必須</span></label>
                                <input
                                    type="date"
                                    name="new_news[{{ $key }}][hours_change_date]"
                                    value="{{ $hoursChangeDate }}"
                                    class="admin-input"
                                    data-news-hours-change-date
                                >
                            </div>
                            <div>
                                <span class="admin-label">営業時間 <span class="admin-required-badge">必須</span></span>
                                <div class="mt-1 flex flex-wrap items-center gap-2">
                                    <input
                                        type="time"
                                        name="new_news[{{ $key }}][hours_start_time]"
                                        value="{{ $hoursStartTime }}"
                                        class="admin-input max-w-[9rem]"
                                        data-news-hours-start
                                        @if(! empty($hoursTimesAreAuto)) data-hours-auto="1" @endif
                                        aria-label="開始時間"
                                    >
                                    <span class="text-sm text-admin-muted" aria-hidden="true">〜</span>
                                    <input
                                        type="time"
                                        name="new_news[{{ $key }}][hours_end_time]"
                                        value="{{ $hoursEndTime }}"
                                        class="admin-input max-w-[9rem]"
                                        data-news-hours-end
                                        @if(! empty($hoursTimesAreAuto)) data-hours-auto="1" @endif
                                        aria-label="終了時間"
                                    >
                                </div>
                                <p class="mt-1 text-xs text-admin-muted">変更日に応じて基本情報の通常営業時間（平日／土日祝）を初期表示します。この日だけ変える場合に編集してください。公開カレンダーには変更後の時間が表示されます。</p>
                            </div>
                        </div>
                        <div data-news-body-wrap>
                            <label class="admin-label" data-news-body-label>
                                {{ $isHoursCategory ? '補足説明（任意）' : '本文' }}
                            </label>
                            <textarea
                                name="new_news[{{ $key }}][body]"
                                rows="6"
                                class="admin-input"
                            >{{ $body }}</textarea>
                            <p class="mt-1 text-xs text-admin-muted" data-news-body-hint @if(! $isHoursCategory) hidden @endif>
                                営業時間は上の専用項目で登録します。追加の案内がある場合のみ入力してください。
                            </p>
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
