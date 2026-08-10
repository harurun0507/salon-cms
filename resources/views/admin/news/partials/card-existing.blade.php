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
