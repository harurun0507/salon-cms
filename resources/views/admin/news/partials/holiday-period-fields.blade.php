@php
    $isHolidayCategory = \App\Models\News::usesHolidayPeriodFields($category);
    $periodType = old($prefix.'.holiday_period_type', $holidayPeriodType ?? \App\Models\News::HOLIDAY_PERIOD_1_YEAR);
    $periodFrom = old($prefix.'.holiday_period_from', $holidayPeriodFrom ?? now()->startOfMonth()->toDateString());
    $periodTo = old($prefix.'.holiday_period_to', $holidayPeriodTo ?? '');
    $salon = \App\Models\SalonSetting::current();
    $defaultWeekdays = $salon->closedWeekdayValues();
    $defaultNth = $salon->closedNthWeekdayRules();
    $closedWeekdays = old($prefix.'.closed_weekdays', $closedWeekdays ?? $defaultWeekdays);
    $closedNth = old($prefix.'.closed_nth', $closedNth ?? $defaultNth);
    if (! is_array($closedNth)) {
        $closedNth = $defaultNth;
    }
@endphp

<div
    class="space-y-3"
    data-news-holiday-period-wrap
    @if(! $isHolidayCategory) hidden @endif
>
    <div>
        <span class="admin-label">対象期間 <span class="admin-required-badge">必須</span></span>
        <div
            class="admin-segmented mt-1"
            role="radiogroup"
            aria-label="対象期間"
            data-news-holiday-period-type-group
        >
            @foreach (\App\Models\News::HOLIDAY_PERIOD_TYPES as $value => $label)
                <label class="admin-segmented-option">
                    <input
                        type="radio"
                        name="{{ $fieldPrefix }}[holiday_period_type]"
                        value="{{ $value }}"
                        class="admin-segmented-input"
                        data-news-holiday-period-type
                        {{ (string) $periodType === (string) $value ? 'checked' : '' }}
                    >
                    <span class="admin-segmented-face">
                        <span class="admin-segmented-text">{{ $label }}</span>
                    </span>
                </label>
            @endforeach
        </div>
    </div>

    <div class="space-y-2" data-news-holiday-period-range>
        <span class="admin-label">From ～ To</span>
        <div class="flex flex-wrap items-center gap-2">
            <input
                type="date"
                name="{{ $fieldPrefix }}[holiday_period_from]"
                value="{{ $periodFrom }}"
                class="admin-input max-w-[11rem]"
                data-news-holiday-period-from
                aria-label="開始日"
            >
            <span class="text-sm text-admin-muted" aria-hidden="true">～</span>
            <input
                type="date"
                name="{{ $fieldPrefix }}[holiday_period_to]"
                value="{{ $periodTo }}"
                class="admin-input max-w-[11rem]"
                data-news-holiday-period-to
                @if((string) $periodType !== \App\Models\News::HOLIDAY_PERIOD_CUSTOM) data-period-auto="1" @endif
                aria-label="終了日"
            >
        </div>
        <p class="text-sm text-admin-text" data-news-holiday-period-preview></p>
    </div>

    @include('admin.news.partials.holiday-closed-days-fields', [
        'fieldPrefix' => $fieldPrefix,
        'closedWeekdays' => $closedWeekdays,
        'closedNth' => $closedNth,
        'weekdayLabels' => $weekdayLabels ?? \App\Models\News::WEEKDAY_SHORT_LABELS,
    ])
</div>
