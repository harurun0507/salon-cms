@php
    $calendarColors = $calendarColors ?? \App\Models\DesignSetting::current()->resolvedBusinessCalendarColors();
    $colorFields = [
        'calendar_holiday_color' => [
            'label' => '定休日',
            'value' => old('calendar_holiday_color', $calendarColors['holiday']),
            'hint' => '営業カレンダーの定休日セル／凡例',
        ],
        'calendar_temporary_color' => [
            'label' => '臨時休業',
            'value' => old('calendar_temporary_color', $calendarColors['temporary']),
            'hint' => '営業カレンダーの臨時休業セル／凡例',
        ],
        'calendar_hours_color' => [
            'label' => '営業時間変更',
            'value' => old('calendar_hours_color', $calendarColors['hours']),
            'hint' => '営業カレンダーの営業時間変更セル／凡例',
        ],
    ];
@endphp

<section class="admin-card mb-4 space-y-4" data-news-calendar-settings>
    <div class="flex min-w-0 flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <h2 class="text-base font-medium text-admin-text">営業カレンダー設定</h2>
            <p class="mt-1 text-sm text-admin-muted">
                縦インジケーターのお知らせモーダルで表示する営業カレンダーの色です。お知らせごとではなく、種類ごとの共通設定です。
            </p>
        </div>
        <button
            type="button"
            class="admin-btn-secondary shrink-0"
            data-admin-confirm-trigger
            data-confirm-callback="news-calendar-colors-reset"
            data-confirm-title="初期値に戻す確認"
            data-confirm-message="営業カレンダーの色を初期値へ戻します。保存するまでは公開サイトへ反映されません。"
            data-confirm-submit-label="初期値に戻す"
            data-news-calendar-colors-reset
        >初期値に戻す</button>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        @foreach ($colorFields as $name => $meta)
            @php
                $hex = \App\Models\DesignSetting::normalizeHex((string) $meta['value']);
                if (! \App\Models\DesignSetting::isValidHex($hex)) {
                    $hex = \App\Models\DesignSetting::DEFAULTS[$name];
                }
            @endphp
            <div data-news-color-field>
                <label for="{{ $name }}" class="admin-label">{{ $meta['label'] }}の色</label>
                <div class="mt-1 flex flex-wrap items-center gap-3">
                    <input
                        type="color"
                        value="{{ $hex }}"
                        class="h-10 w-14 cursor-pointer rounded-lg border border-admin-border bg-admin-card p-1"
                        data-news-color-swatch
                        aria-label="{{ $meta['label'] }}のカラーピッカー"
                    >
                    <input
                        type="text"
                        name="{{ $name }}"
                        id="{{ $name }}"
                        value="{{ $hex }}"
                        class="admin-input max-w-40 font-mono uppercase"
                        maxlength="7"
                        autocomplete="off"
                        spellcheck="false"
                        pattern="#?[0-9A-Fa-f]{6}"
                        data-news-color-hex
                    >
                </div>
                <p class="mt-1 text-xs text-admin-muted">{{ $meta['hint'] }}（#RRGGBB）</p>
                @error($name)
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        @endforeach
    </div>
</section>
