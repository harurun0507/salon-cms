@php
    $weekdayLabels = $weekdayLabels ?? \App\Models\News::WEEKDAY_SHORT_LABELS;
    $selectedClosedWeekdays = collect($closedWeekdays ?? [])
        ->map(fn ($v) => (int) $v)
        ->filter(fn (int $v) => $v >= 0 && $v <= 6)
        ->unique()
        ->values()
        ->all();
    $selectedClosedNth = is_array($closedNth ?? null) ? $closedNth : [];
@endphp

<div class="space-y-3" data-news-holiday-closed-days>
    <span class="admin-label">定休日 <span class="admin-required-badge">必須</span></span>
    <div class="mt-1 space-y-4">
        <div>
            <p class="text-sm text-admin-text">毎週</p>
            <div class="news-weekday-choices mt-1 notranslate" role="group" aria-label="毎週の定休日" translate="no" lang="ja">
                @foreach($weekdayLabels as $weekdayValue => $weekdayLabel)
                    <label class="news-weekday-option">
                        <input
                            type="checkbox"
                            name="{{ $fieldPrefix }}[closed_weekdays][]"
                            value="{{ $weekdayValue }}"
                            class="news-weekday-input"
                            data-news-holiday-weekday
                            @checked(in_array((int) $weekdayValue, $selectedClosedWeekdays, true))
                        >
                        <span class="news-weekday-face">{{ $weekdayLabel }}</span>
                    </label>
                @endforeach
            </div>
        </div>
        <div>
            <p class="text-sm text-admin-text">追加定休日（第○週の○曜日）</p>
            <div class="mt-2 space-y-2 notranslate" data-news-closed-nth-list translate="no" lang="ja">
                @foreach($selectedClosedNth as $index => $rule)
                    <div class="flex flex-wrap items-center gap-2" data-news-closed-nth-row>
                        <select
                            name="{{ $fieldPrefix }}[closed_nth][{{ $index }}][week]"
                            class="admin-input max-w-[7.5rem] notranslate"
                            aria-label="週"
                            translate="no"
                            lang="ja"
                        >
                            @for($week = 1; $week <= 5; $week++)
                                <option value="{{ $week }}" @selected((int) ($rule['week'] ?? 0) === $week)>第{{ $week }}週</option>
                            @endfor
                        </select>
                        <select
                            name="{{ $fieldPrefix }}[closed_nth][{{ $index }}][weekday]"
                            class="admin-input max-w-[7rem] notranslate"
                            aria-label="曜日"
                            translate="no"
                            lang="ja"
                        >
                            @foreach($weekdayLabels as $weekdayValue => $weekdayLabel)
                                <option value="{{ $weekdayValue }}" @selected((int) ($rule['weekday'] ?? -1) === (int) $weekdayValue)>{{ $weekdayLabel }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="admin-icon-btn admin-icon-btn-delete" data-news-closed-nth-remove aria-label="削除" title="削除">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endforeach
            </div>
            <select class="sr-only notranslate" aria-hidden="true" tabindex="-1" translate="no" lang="ja" data-news-closed-nth-week-labels>
                @for($week = 1; $week <= 5; $week++)
                    <option value="{{ $week }}">第{{ $week }}週</option>
                @endfor
            </select>
            <button type="button" class="admin-btn-secondary mt-2 text-sm" data-news-closed-nth-add>＋ 追加定休日を追加</button>
            <p class="mt-1 text-xs text-admin-muted">例：第3水曜日、第1・第3水曜日。初期値は店舗情報の基本情報です。</p>
        </div>
    </div>
</div>
