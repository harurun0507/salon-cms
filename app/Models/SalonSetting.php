<?php

namespace App\Models;

use App\Support\JapanesePublicHolidays;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalonSetting extends Model
{
    public const DISPLAY_TYPE_TEXT = 'text';

    public const DISPLAY_TYPE_LOGO = 'logo';

    public const TWITTER_CARD_SUMMARY = 'summary';

    public const TWITTER_CARD_SUMMARY_LARGE_IMAGE = 'summary_large_image';

    /**
     * Carbon / JS day indexes: 0 = Sunday … 6 = Saturday.
     *
     * @var array<int, string>
     */
    public const WEEKDAY_LABELS = [
        0 => '日曜日',
        1 => '月曜日',
        2 => '火曜日',
        3 => '水曜日',
        4 => '木曜日',
        5 => '金曜日',
        6 => '土曜日',
    ];

    /**
     * @var array<int, string>
     */
    public const WEEKDAY_SHORT_LABELS = [
        0 => '日',
        1 => '月',
        2 => '火',
        3 => '水',
        4 => '木',
        5 => '金',
        6 => '土',
    ];

    protected $fillable = [
        'shop_name',
        'shop_name_display_type',
        'logo_image',
        'logo_alt_text',
        'hero_label',
        'hero_title',
        'concept_title',
        'concept',
        'concept_image',
        'address',
        'access_directions',
        'weekday_open_time',
        'weekday_close_time',
        'weekend_open_time',
        'weekend_close_time',
        'phone',
        'payment_methods',
        'cut_price',
        'seat_count',
        'staff_count',
        'parking',
        'commitment_conditions',
        'notes',
        'other_info',
        'google_map_url',
        'google_map_embed_url',
        'instagram_url',
        'hot_pepper_url',
        'hero_image',
        'site_title',
        'meta_description',
        'meta_keywords',
        'og_title',
        'og_description',
        'og_image',
        'twitter_card',
        'noindex',
        'favicon_path',
        'ga_measurement_id',
    ];

    protected $casts = [
        'noindex' => 'boolean',
    ];

    public function heroImages(): HasMany
    {
        return $this->hasMany(HeroImage::class)->ordered();
    }

    public function publishedHeroImages(): HasMany
    {
        return $this->hasMany(HeroImage::class)->published();
    }

    public function closedWeekdays(): HasMany
    {
        return $this->hasMany(SalonClosedWeekday::class)->orderBy('weekday');
    }

    public function closedNthWeekdays(): HasMany
    {
        return $this->hasMany(SalonClosedNthWeekday::class)
            ->orderBy('weekday')
            ->orderBy('week_of_month');
    }

    /**
     * @return list<int>
     */
    public function closedWeekdayValues(): array
    {
        $this->loadMissing('closedWeekdays');

        return $this->closedWeekdays
            ->pluck('weekday')
            ->map(fn ($day) => (int) $day)
            ->filter(fn (int $day) => array_key_exists($day, self::WEEKDAY_LABELS))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @return list<array{week: int, weekday: int}>
     */
    public function closedNthWeekdayRules(): array
    {
        $this->loadMissing('closedNthWeekdays');

        return $this->closedNthWeekdays
            ->map(fn (SalonClosedNthWeekday $rule) => [
                'week' => (int) $rule->week_of_month,
                'weekday' => (int) $rule->weekday,
            ])
            ->filter(fn (array $rule) => $rule['week'] >= 1
                && $rule['week'] <= 5
                && array_key_exists($rule['weekday'], self::WEEKDAY_LABELS))
            ->unique(fn (array $rule) => $rule['week'].'-'.$rule['weekday'])
            ->values()
            ->all();
    }

    public function hasClosedDays(): bool
    {
        return filled($this->closedDaysDisplayText());
    }

    /**
     * e.g. "毎週火曜日・第3水曜日" / "第1・第3水曜日"
     */
    public function closedDaysDisplayText(): ?string
    {
        $parts = [];

        $weekdays = $this->closedWeekdayValues();
        if ($weekdays !== []) {
            $labels = array_map(
                fn (int $day) => self::WEEKDAY_LABELS[$day],
                $weekdays
            );
            $parts[] = '毎週'.implode('・', $labels);
        }

        $grouped = [];
        foreach ($this->closedNthWeekdayRules() as $rule) {
            $grouped[$rule['weekday']][] = $rule['week'];
        }
        ksort($grouped);
        foreach ($grouped as $weekday => $weeks) {
            $weeks = array_values(array_unique($weeks));
            sort($weeks);
            $weekLabels = array_map(fn (int $week) => '第'.$week, $weeks);
            $parts[] = implode('・', $weekLabels).self::WEEKDAY_LABELS[$weekday];
        }

        return $parts === [] ? null : implode('・', $parts);
    }

    public function closedDaysAnnouncementSentence(): ?string
    {
        $text = $this->closedDaysDisplayText();
        if ($text === null) {
            return null;
        }

        return '定休日は'.$text.'です。';
    }

    public function isRegularClosedDate(CarbonInterface $date): bool
    {
        $weekday = (int) $date->dayOfWeek;
        if (in_array($weekday, $this->closedWeekdayValues(), true)) {
            return true;
        }

        $occurrence = (int) ceil($date->day / 7);
        foreach ($this->closedNthWeekdayRules() as $rule) {
            if ($rule['weekday'] === $weekday && $rule['week'] === $occurrence) {
                return true;
            }
        }

        return false;
    }

    /**
     * Mark ISO dates (Y-m-d) in the given month that are regular closed days.
     *
     * @return list<string>
     */
    public function regularClosedDatesForMonth(int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $daysInMonth = (int) $start->daysInMonth;
        $dates = [];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::create($year, $month, $day);
            if ($this->isRegularClosedDate($date)) {
                $dates[] = $date->format('Y-m-d');
            }
        }

        return $dates;
    }

    public function usesLogoInHeader(): bool
    {
        return $this->shop_name_display_type === self::DISPLAY_TYPE_LOGO
            && filled($this->logo_image);
    }

    public function logoAlt(): string
    {
        return filled($this->logo_alt_text) ? (string) $this->logo_alt_text : (string) $this->shop_name;
    }

    public function hasConceptImage(): bool
    {
        return filled($this->concept_image);
    }

    public function conceptImagePath(): ?string
    {
        return $this->hasConceptImage() ? (string) $this->concept_image : null;
    }

    public function conceptImageAlt(): string
    {
        return filled($this->concept_title) ? (string) $this->concept_title : 'Concept';
    }

    public function seoSiteTitle(): string
    {
        if (filled($this->site_title)) {
            return (string) $this->site_title;
        }

        return filled($this->shop_name) ? (string) $this->shop_name : 'Sun＆ Me';
    }

    public function seoOgTitle(): string
    {
        return filled($this->og_title) ? (string) $this->og_title : $this->seoSiteTitle();
    }

    public function seoOgDescription(): ?string
    {
        if (filled($this->og_description)) {
            return (string) $this->og_description;
        }

        return filled($this->meta_description) ? (string) $this->meta_description : null;
    }

    public function seoTwitterCard(): string
    {
        return filled($this->twitter_card)
            ? (string) $this->twitter_card
            : self::TWITTER_CARD_SUMMARY_LARGE_IMAGE;
    }

    public function seoOgImageUrl(): ?string
    {
        $path = null;

        if (filled($this->og_image)) {
            $path = (string) $this->og_image;
        } elseif (filled($this->logo_image)) {
            $path = (string) $this->logo_image;
        }

        if ($path === null) {
            return null;
        }

        return asset('storage/'.$path);
    }

    public function robotsMetaContent(): string
    {
        return $this->noindex ? 'noindex, nofollow' : 'index, follow';
    }

    public function faviconUrl(): string
    {
        if (filled($this->favicon_path)) {
            return asset('storage/'.$this->favicon_path);
        }

        return asset('images/favicon-site.png');
    }

    public function hasCustomFavicon(): bool
    {
        return filled($this->favicon_path);
    }

    public function hasGaMeasurementId(): bool
    {
        return filled($this->ga_measurement_id);
    }

    public function hasBusinessHours(): bool
    {
        return filled($this->businessHoursDisplayText());
    }

    /**
     * Public display, e.g. "平日 10:00 - 20:00\n土日祝 9:00 - 19:00".
     */
    public function businessHoursDisplayText(): ?string
    {
        $lines = [];

        $weekday = $this->formatBusinessHoursRange(
            $this->weekday_open_time,
            $this->weekday_close_time
        );
        if ($weekday !== null) {
            $lines[] = '平日 '.$weekday;
        }

        $weekend = $this->formatBusinessHoursRange(
            $this->weekend_open_time,
            $this->weekend_close_time
        );
        if ($weekend !== null) {
            $lines[] = '土日祝 '.$weekend;
        }

        return $lines === [] ? null : implode("\n", $lines);
    }

    public function weekdayOpenTimeInputValue(): string
    {
        return $this->formatTimeInputValue($this->weekday_open_time);
    }

    public function weekdayCloseTimeInputValue(): string
    {
        return $this->formatTimeInputValue($this->weekday_close_time);
    }

    public function weekendOpenTimeInputValue(): string
    {
        return $this->formatTimeInputValue($this->weekend_open_time);
    }

    public function weekendCloseTimeInputValue(): string
    {
        return $this->formatTimeInputValue($this->weekend_close_time);
    }

    /**
     * Whether the date should use the 「土日祝」 regular hours bucket.
     */
    public function usesWeekendBusinessHoursOn(CarbonInterface $date): bool
    {
        return $date->isWeekend() || JapanesePublicHolidays::isHoliday($date);
    }

    /**
     * Regular open/close for a calendar date from basic salon settings.
     *
     * @return array{open: string, close: string, bucket: 'weekday'|'weekend'}
     */
    public function regularHoursForDate(CarbonInterface $date): array
    {
        if ($this->usesWeekendBusinessHoursOn($date)) {
            return [
                'open' => $this->weekendOpenTimeInputValue(),
                'close' => $this->weekendCloseTimeInputValue(),
                'bucket' => 'weekend',
            ];
        }

        return [
            'open' => $this->weekdayOpenTimeInputValue(),
            'close' => $this->weekdayCloseTimeInputValue(),
            'bucket' => 'weekday',
        ];
    }

    /**
     * Payload for admin UI defaults when creating hours-change news.
     *
     * @return array{
     *     weekday: array{open: string, close: string},
     *     weekend: array{open: string, close: string},
     *     holidayDates: list<string>
     * }
     */
    public function regularBusinessHoursAdminPayload(): array
    {
        $year = (int) now()->year;

        return [
            'weekday' => [
                'open' => $this->weekdayOpenTimeInputValue(),
                'close' => $this->weekdayCloseTimeInputValue(),
            ],
            'weekend' => [
                'open' => $this->weekendOpenTimeInputValue(),
                'close' => $this->weekendCloseTimeInputValue(),
            ],
            'holidayDates' => JapanesePublicHolidays::datesBetweenYears($year - 1, $year + 2),
        ];
    }

    private function formatBusinessHoursRange(mixed $open, mixed $close): ?string
    {
        $openLabel = $this->formatPublicClock($open);
        $closeLabel = $this->formatPublicClock($close);
        if ($openLabel === null || $closeLabel === null) {
            return null;
        }

        return $openLabel.' - '.$closeLabel;
    }

    private function formatPublicClock(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if ($value instanceof CarbonInterface) {
                return $value->format('G:i');
            }

            return Carbon::parse((string) $value)->format('G:i');
        } catch (\Throwable) {
            return null;
        }
    }

    private function formatTimeInputValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            if ($value instanceof CarbonInterface) {
                return $value->format('H:i');
            }

            return Carbon::parse((string) $value)->format('H:i');
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * Public store-info rows in display order. Empty values are omitted.
     *
     * @return list<array{label: string, value: string, multiline: bool}>
     */
    public function publicStoreInfoItems(): array
    {
        $items = [
            ['label' => '住所', 'value' => $this->address, 'multiline' => false],
            ['label' => 'アクセス・道案内', 'value' => $this->access_directions, 'multiline' => true],
            ['label' => '営業時間', 'value' => $this->businessHoursDisplayText(), 'multiline' => true],
            ['label' => '定休日', 'value' => $this->closedDaysDisplayText(), 'multiline' => false],
            ['label' => '電話番号', 'value' => $this->phone, 'multiline' => false],
            ['label' => '支払い方法', 'value' => $this->payment_methods, 'multiline' => true],
            ['label' => 'カット価格', 'value' => $this->cut_price, 'multiline' => false],
            ['label' => '席数', 'value' => $this->seat_count, 'multiline' => false],
            ['label' => 'スタッフ数', 'value' => $this->staff_count, 'multiline' => false],
            ['label' => '駐車場', 'value' => $this->parking, 'multiline' => true],
            ['label' => 'こだわり条件', 'value' => $this->commitment_conditions, 'multiline' => true],
            ['label' => 'その他', 'value' => $this->other_info, 'multiline' => true],
        ];

        return array_values(array_filter(
            $items,
            static fn (array $item): bool => filled($item['value'])
        ));
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'shop_name' => 'Sun＆ Me',
            'shop_name_display_type' => self::DISPLAY_TYPE_TEXT,
            'concept' => '一人ひとりの髪質やライフスタイルに合わせた、丁寧なカウンセリングと施術を大切にしています。',
            'twitter_card' => self::TWITTER_CARD_SUMMARY_LARGE_IMAGE,
            'noindex' => false,
        ]);
    }
}
