<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class News extends Model
{
    public const CATEGORY_HOLIDAY = 'holiday';

    public const CATEGORY_TEMPORARY_CLOSURE = 'temporary_closure';

    /** @deprecated Legacy value; migrated to {@see CATEGORY_HOLIDAY}. */
    public const CATEGORY_CLOSED = 'closed';

    public const CATEGORY_HOURS = 'hours';

    public const CATEGORY_RESERVATION = 'reservation';

    public const CATEGORY_PRICE = 'price';

    public const CATEGORY_NEW_MENU = 'new_menu';

    public const CATEGORY_PRODUCT = 'product';

    public const CATEGORY_SEASONAL = 'seasonal';

    public const CATEGORY_IMPORTANT = 'important';

    public const CATEGORY_OTHER = 'other';

    public const HOLIDAY_PERIOD_3_MONTHS = '3_months';

    public const HOLIDAY_PERIOD_6_MONTHS = '6_months';

    public const HOLIDAY_PERIOD_1_YEAR = '1_year';

    public const HOLIDAY_PERIOD_CUSTOM = 'custom';

    /**
     * @var array<string, string>
     */
    public const HOLIDAY_PERIOD_TYPES = [
        self::HOLIDAY_PERIOD_3_MONTHS => '3ヶ月',
        self::HOLIDAY_PERIOD_6_MONTHS => '6ヶ月',
        self::HOLIDAY_PERIOD_1_YEAR => '1年',
        self::HOLIDAY_PERIOD_CUSTOM => '任意期間',
    ];

    /**
     * Months added for preset period types (custom is manual).
     *
     * @var array<string, int>
     */
    public const HOLIDAY_PERIOD_MONTHS = [
        self::HOLIDAY_PERIOD_3_MONTHS => 3,
        self::HOLIDAY_PERIOD_6_MONTHS => 6,
        self::HOLIDAY_PERIOD_1_YEAR => 12,
    ];

    /**
     * @var array<string, string>
     */
    public const CATEGORIES = [
        self::CATEGORY_HOLIDAY => '定休日',
        self::CATEGORY_TEMPORARY_CLOSURE => '臨時休業',
        self::CATEGORY_HOURS => '営業時間変更',
        self::CATEGORY_RESERVATION => '予約に関する案内',
        self::CATEGORY_PRICE => '料金改定',
        self::CATEGORY_NEW_MENU => '新メニュー・新サービス',
        self::CATEGORY_PRODUCT => '商品入荷・取扱開始',
        self::CATEGORY_SEASONAL => '年末年始・夏季休業',
        self::CATEGORY_IMPORTANT => '重要なお知らせ',
        self::CATEGORY_OTHER => 'その他',
    ];

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
        'title',
        'slug',
        'body',
        'category',
        'hours_change_date',
        'hours_start_time',
        'hours_end_time',
        'holiday_period_type',
        'holiday_period_from',
        'holiday_period_to',
        'published_at',
        'is_published',
        'display_order',
    ];

    protected $casts = [
        'hours_change_date' => 'date',
        'holiday_period_from' => 'date',
        'holiday_period_to' => 'date',
        'published_at' => 'datetime',
        'is_published' => 'boolean',
        'display_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (News $news) {
            if (empty($news->slug)) {
                $news->slug = Str::slug($news->title);
            }
            if (empty($news->category)) {
                $news->category = self::CATEGORY_OTHER;
            }
        });
    }

    public function closedDates(): HasMany
    {
        return $this->hasMany(NewsClosedDate::class)->orderBy('closed_date');
    }

    public function closedWeekdays(): HasMany
    {
        return $this->hasMany(NewsClosedWeekday::class)->orderBy('weekday');
    }

    public function closedNthWeekdays(): HasMany
    {
        return $this->hasMany(NewsClosedNthWeekday::class)
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
            ->map(fn (NewsClosedNthWeekday $rule) => [
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

    public function hasClosedDayRules(): bool
    {
        return $this->closedWeekdayValues() !== [] || $this->closedNthWeekdayRules() !== [];
    }

    /**
     * e.g. "毎週火曜日・第3水曜日"
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
     * ISO dates (Y-m-d) in the month that match this news' closed-day rules and From–To.
     *
     * @return list<string>
     */
    public function regularClosedDatesForMonthWithinPeriod(int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $daysInMonth = (int) $start->daysInMonth;
        $dates = [];

        $periodFrom = $this->hasHolidayPeriod() ? $this->holiday_period_from->copy()->startOfDay() : null;
        $periodTo = $this->hasHolidayPeriod() ? $this->holiday_period_to->copy()->startOfDay() : null;

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::create($year, $month, $day)->startOfDay();
            if ($periodFrom && $date->lt($periodFrom)) {
                continue;
            }
            if ($periodTo && $date->gt($periodTo)) {
                continue;
            }
            if ($this->isRegularClosedDate($date)) {
                $dates[] = $date->format('Y-m-d');
            }
        }

        return $dates;
    }

    /**
     * Categories that use the date calendar / news_closed_dates.
     *
     * @return list<string>
     */
    public static function closedDateCategoryKeys(): array
    {
        return [
            self::CATEGORY_TEMPORARY_CLOSURE,
        ];
    }

    /**
     * Categories that use weekday selection / news_closed_weekdays.
     *
     * @return list<string>
     */
    public static function closedWeekdayCategoryKeys(): array
    {
        // Regular closed weekdays are managed in SalonSetting (store basics).
        return [];
    }

    public static function usesClosedDates(?string $category): bool
    {
        return in_array((string) $category, self::closedDateCategoryKeys(), true);
    }

    public static function usesClosedWeekdays(?string $category): bool
    {
        return in_array((string) $category, self::closedWeekdayCategoryKeys(), true);
    }

    public function isHolidayAnnouncement(): bool
    {
        return in_array((string) $this->category, [
            self::CATEGORY_HOLIDAY,
            self::CATEGORY_CLOSED,
        ], true);
    }

    public function isTemporaryClosureAnnouncement(): bool
    {
        return self::usesClosedDates($this->category);
    }

    public function isClosedAnnouncement(): bool
    {
        return $this->isHolidayAnnouncement() || $this->isTemporaryClosureAnnouncement();
    }

    public function isHoursAnnouncement(): bool
    {
        return (string) $this->category === self::CATEGORY_HOURS;
    }

    public static function usesHoursChangeFields(?string $category): bool
    {
        return (string) $category === self::CATEGORY_HOURS;
    }

    public static function usesHolidayPeriodFields(?string $category): bool
    {
        return in_array((string) $category, [
            self::CATEGORY_HOLIDAY,
            self::CATEGORY_CLOSED,
        ], true);
    }

    /**
     * @return list<string>
     */
    public static function holidayPeriodTypeKeys(): array
    {
        return array_keys(self::HOLIDAY_PERIOD_TYPES);
    }

    /**
     * Compute To date from From + preset (inclusive end = From + N months − 1 day).
     */
    public static function computeHolidayPeriodTo(CarbonInterface|string $from, string $periodType): ?CarbonInterface
    {
        $months = self::HOLIDAY_PERIOD_MONTHS[$periodType] ?? null;
        if ($months === null) {
            return null;
        }

        $start = $from instanceof CarbonInterface
            ? $from->copy()->startOfDay()
            : Carbon::parse((string) $from)->startOfDay();

        return $start->copy()->addMonths($months)->subDay()->startOfDay();
    }

    public function hasHolidayPeriod(): bool
    {
        return $this->isHolidayAnnouncement()
            && $this->holiday_period_from
            && $this->holiday_period_to
            && $this->holiday_period_to->gte($this->holiday_period_from);
    }

    public function holidayPeriodLabel(): ?string
    {
        if (! $this->hasHolidayPeriod()) {
            return null;
        }

        return $this->holiday_period_from->format('Y/m/d')
            .' ～ '
            .$this->holiday_period_to->format('Y/m/d');
    }

    /**
     * Whether this holiday announcement covers any day in the given calendar month.
     */
    public function coversBusinessCalendarMonth(int $year, int $month): bool
    {
        if (! $this->isHolidayAnnouncement()) {
            return false;
        }

        if ($this->hasHolidayPeriod()) {
            $monthStart = Carbon::create($year, $month, 1)->startOfDay();
            $monthEnd = $monthStart->copy()->endOfMonth();

            return $this->holiday_period_from->lte($monthEnd)
                && $this->holiday_period_to->gte($monthStart);
        }

        // Legacy holiday news without period: match published month.
        $published = $this->published_at ?? null;

        return $published
            && (int) $published->format('Y') === $year
            && (int) $published->format('n') === $month;
    }

    /**
     * Month keys (Y-m) covered by the holiday period (or published month for legacy).
     *
     * @return list<string>
     */
    public function holidayPeriodMonthKeys(): array
    {
        if (! $this->isHolidayAnnouncement()) {
            return [];
        }

        if ($this->hasHolidayPeriod()) {
            $cursor = $this->holiday_period_from->copy()->startOfMonth();
            $end = $this->holiday_period_to->copy()->startOfMonth();
            $keys = [];
            while ($cursor->lte($end)) {
                $keys[] = $cursor->format('Y-m');
                $cursor->addMonth();
            }

            return $keys;
        }

        $published = $this->published_at ?? now();

        return [$published->copy()->startOfMonth()->format('Y-m')];
    }

    /**
     * Categories shown together in the public monthly business calendar modal (VI).
     *
     * @return list<string>
     */
    public static function businessCalendarCategoryKeys(): array
    {
        return [
            self::CATEGORY_HOLIDAY,
            self::CATEGORY_CLOSED,
            self::CATEGORY_TEMPORARY_CLOSURE,
            self::CATEGORY_HOURS,
        ];
    }

    public function isBusinessCalendarAnnouncement(): bool
    {
        return in_array(
            $this->category === self::CATEGORY_CLOSED
                ? self::CATEGORY_HOLIDAY
                : (string) $this->category,
            [
                self::CATEGORY_HOLIDAY,
                self::CATEGORY_TEMPORARY_CLOSURE,
                self::CATEGORY_HOURS,
            ],
            true
        );
    }

    public function businessCalendarPeriod(): ?CarbonInterface
    {
        if (! $this->isBusinessCalendarAnnouncement()) {
            return null;
        }

        $this->loadMissing(['closedDates']);

        if ($this->isTemporaryClosureAnnouncement()) {
            $first = $this->closedDates
                ->map(fn ($closedDate) => $closedDate->closed_date)
                ->filter()
                ->sortBy(fn (CarbonInterface $date) => $date->timestamp)
                ->first();

            if ($first) {
                return $first->copy()->startOfMonth();
            }
        }

        if ($this->isHoursAnnouncement() && $this->hours_change_date) {
            return $this->hours_change_date->copy()->startOfMonth();
        }

        if ($this->isHolidayAnnouncement()) {
            if ($this->hasHolidayPeriod()) {
                return $this->holiday_period_from->copy()->startOfMonth();
            }

            return ($this->published_at ?? now())->copy()->startOfMonth();
        }

        return ($this->published_at ?? now())->copy()->startOfMonth();
    }

    public function hoursStartTimeLabel(): ?string
    {
        return $this->formatClockValue($this->hours_start_time);
    }

    public function hoursEndTimeLabel(): ?string
    {
        return $this->formatClockValue($this->hours_end_time);
    }

    /**
     * e.g. "10:00〜19:00" from structured hours fields (never from body text).
     */
    public function hoursChangeRangeLabel(): ?string
    {
        $start = $this->hoursStartTimeLabel();
        $end = $this->hoursEndTimeLabel();
        if ($start === null || $end === null) {
            return null;
        }

        return $start.'〜'.$end;
    }

    /**
     * e.g. "8月12日　営業時間変更　10:00〜19:00"
     */
    public function hoursChangeNoteDetail(): ?string
    {
        if (! $this->isHoursAnnouncement() || ! $this->hours_change_date) {
            return null;
        }

        $detail = '営業時間変更';
        $range = $this->hoursChangeRangeLabel();
        if ($range !== null) {
            $detail .= '　'.$range;
        }

        return $detail;
    }

    private function formatClockValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if ($value instanceof CarbonInterface) {
                return $value->format('H:i');
            }

            return Carbon::parse((string) $value)->format('H:i');
        } catch (\Throwable) {
            return null;
        }
    }

    public function closedWeekdaysSentence(): ?string
    {
        return $this->closedDaysAnnouncementSentence();
    }

    public function categoryLabel(): string
    {
        if ($this->category === self::CATEGORY_CLOSED) {
            return self::CATEGORIES[self::CATEGORY_HOLIDAY];
        }

        return self::CATEGORIES[$this->category] ?? self::CATEGORIES[self::CATEGORY_OTHER];
    }

    /**
     * @return list<string>
     */
    public static function categoryKeys(): array
    {
        return array_keys(self::CATEGORIES);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order')->orderBy('id');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->where(function ($q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->orderBy('display_order')
            ->orderByDesc('published_at')
            ->orderByDesc('id');
    }

    /**
     * Payload for the public in-page news modal.
     *
     * @return array{
     *     id: int,
     *     slug: string,
     *     url: string,
     *     title: string,
     *     date: ?string,
     *     category: string,
     *     categoryKey: string,
     *     holidaySentence: ?string,
     *     temporaryDates: list<string>,
     *     closedWeekdays: list<int>,
     *     closedDates: list<string>,
     *     calendarYear: ?int,
     *     calendarMonth: ?int,
     *     showCalendar: bool,
     *     businessCalendarKey: ?string,
     *     body: ?string
     * }
     */
    public function toPublicModalData(): array
    {
        $this->loadMissing(['closedDates', 'closedWeekdays', 'closedNthWeekdays']);

        $temporaryDates = [];
        $closedDateStrings = [];
        if ($this->isTemporaryClosureAnnouncement()) {
            $temporaryDates = $this->closedDates
                ->map(function ($closedDate) {
                    $date = $closedDate->closed_date;
                    if (! $date) {
                        return null;
                    }

                    return $date->format('n月j日').'（'.self::WEEKDAY_SHORT_LABELS[$date->dayOfWeek].'）';
                })
                ->filter()
                ->values()
                ->all();

            $closedDateStrings = $this->closedDates
                ->map(fn ($closedDate) => $closedDate->closed_date?->format('Y-m-d'))
                ->filter()
                ->values()
                ->all();
        }

        $salon = SalonSetting::current();
        $closedWeekdays = $this->isHolidayAnnouncement()
            ? ($this->hasClosedDayRules() ? $this->closedWeekdayValues() : $salon->closedWeekdayValues())
            : [];

        $calendarYear = null;
        $calendarMonth = null;
        $showCalendar = false;
        $holidayPeriodKeys = [];
        $holidaySentence = null;

        if ($this->isHolidayAnnouncement()) {
            $holidaySentence = $this->closedDaysAnnouncementSentence()
                ?? $salon->closedDaysAnnouncementSentence();
            if ($this->hasClosedDayRules() || $salon->hasClosedDays()) {
                $showCalendar = true;
                $holidayPeriodKeys = $this->holidayPeriodMonthKeys();
                $initialKey = $holidayPeriodKeys[0] ?? null;
                if ($initialKey) {
                    [$calendarYear, $calendarMonth] = array_map('intval', explode('-', $initialKey));
                } else {
                    $base = $this->published_at ?? now();
                    $calendarYear = (int) $base->format('Y');
                    $calendarMonth = (int) $base->format('n');
                }
            }
        } elseif ($this->isTemporaryClosureAnnouncement() && $closedDateStrings !== []) {
            $showCalendar = true;
            $base = Carbon::parse($closedDateStrings[0])->startOfDay();
            $calendarYear = (int) $base->format('Y');
            $calendarMonth = (int) $base->format('n');
        }

        $businessPeriod = $this->businessCalendarPeriod();

        return [
            'id' => (int) $this->id,
            'slug' => (string) $this->slug,
            'url' => route('news.show', $this->slug),
            'title' => (string) $this->title,
            'date' => $this->published_at?->format('Y年n月j日'),
            'category' => $this->categoryLabel(),
            'categoryKey' => (string) ($this->category === self::CATEGORY_CLOSED
                ? self::CATEGORY_HOLIDAY
                : ($this->category ?: self::CATEGORY_OTHER)),
            'holidaySentence' => $holidaySentence,
            'holidayPeriodLabel' => $this->holidayPeriodLabel(),
            'holidayPeriodFrom' => $this->hasHolidayPeriod()
                ? $this->holiday_period_from->format('Y-m-d')
                : null,
            'holidayPeriodTo' => $this->hasHolidayPeriod()
                ? $this->holiday_period_to->format('Y-m-d')
                : null,
            'holidayPeriodKeys' => $holidayPeriodKeys,
            'temporaryDates' => $temporaryDates,
            'closedWeekdays' => $closedWeekdays,
            'closedDates' => $closedDateStrings,
            'hoursChangeDate' => $this->isHoursAnnouncement() && $this->hours_change_date
                ? $this->hours_change_date->format('Y-m-d')
                : null,
            'calendarYear' => $calendarYear,
            'calendarMonth' => $calendarMonth,
            'showCalendar' => $showCalendar,
            'businessCalendarKey' => $businessPeriod?->format('Y-m'),
            'body' => filled($this->body) ? (string) $this->body : null,
        ];
    }

    /**
     * Build monthly business-calendar payloads for VI news modals.
     *
     * @param  Collection<int, self>  $newsItems
     * @return array<string, array<string, mixed>>
     */
    public static function businessCalendarsForPublicModal(Collection $newsItems): array
    {
        $keys = collect();

        foreach ($newsItems as $news) {
            if (! ($news instanceof self) || ! $news->isBusinessCalendarAnnouncement()) {
                continue;
            }

            if ($news->isHolidayAnnouncement()) {
                foreach ($news->holidayPeriodMonthKeys() as $key) {
                    $keys->push($key);
                }
                continue;
            }

            $periodKey = $news->businessCalendarPeriod()?->format('Y-m');
            if ($periodKey) {
                $keys->push($periodKey);
            }
        }

        $payloads = [];
        foreach ($keys->unique()->sort()->values() as $key) {
            [$year, $month] = array_map('intval', explode('-', (string) $key));
            $payloads[$key] = static::buildBusinessCalendarPayload($year, $month);
        }

        return $payloads;
    }

    /**
     * @return array{
     *     key: string,
     *     year: int,
     *     month: int,
     *     title: string,
     *     footDate: string,
     *     days: array<string, list<string>>,
     *     notes: list<array{date: ?string, label: string, detail: string, categoryKey: string, newsId: ?int}>,
     *     holidaySentence: ?string
     * }
     */
    public static function buildBusinessCalendarPayload(int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();
        $key = $start->format('Y-m');

        $items = static::published()
            ->whereIn('category', static::businessCalendarCategoryKeys())
            ->with(['closedDates', 'closedWeekdays', 'closedNthWeekdays'])
            ->get()
            ->filter(function (self $news) use ($start, $end, $year, $month) {
                if ($news->isHolidayAnnouncement()) {
                    return $news->coversBusinessCalendarMonth($year, $month);
                }

                if ($news->isTemporaryClosureAnnouncement()) {
                    return $news->closedDates->contains(
                        function ($closedDate) use ($start, $end) {
                            $date = $closedDate->closed_date;

                            return $date && $date->betweenIncluded($start, $end);
                        }
                    );
                }

                if ($news->isHoursAnnouncement()) {
                    $changeDate = $news->hours_change_date;

                    return $changeDate && $changeDate->betweenIncluded($start, $end);
                }

                return false;
            })
            ->values();

        /** @var array<string, list<string>> $days */
        $days = [];
        $notes = [];
        $salon = SalonSetting::current();

        $holidayNewsItems = $items->filter(fn (self $news) => $news->isHolidayAnnouncement())->values();
        $holidaySentence = null;

        if ($holidayNewsItems->isNotEmpty()) {
            foreach ($holidayNewsItems as $news) {
                $rulesNews = $news->hasClosedDayRules() ? $news : null;
                if ($rulesNews) {
                    foreach ($rulesNews->regularClosedDatesForMonthWithinPeriod($year, $month) as $iso) {
                        $days[$iso] = array_values(array_unique([
                            ...($days[$iso] ?? []),
                            'holiday',
                        ]));
                    }
                    $holidaySentence = $holidaySentence ?? $rulesNews->closedDaysAnnouncementSentence();
                } else {
                    foreach ($salon->regularClosedDatesForMonth($year, $month) as $iso) {
                        if ($news->hasHolidayPeriod()) {
                            $date = Carbon::parse($iso)->startOfDay();
                            if ($date->lt($news->holiday_period_from->copy()->startOfDay())
                                || $date->gt($news->holiday_period_to->copy()->startOfDay())) {
                                continue;
                            }
                        }
                        $days[$iso] = array_values(array_unique([
                            ...($days[$iso] ?? []),
                            'holiday',
                        ]));
                    }
                    $holidaySentence = $holidaySentence ?? $salon->closedDaysAnnouncementSentence();
                }
            }
        } else {
            foreach ($salon->regularClosedDatesForMonth($year, $month) as $iso) {
                $days[$iso] = array_values(array_unique([
                    ...($days[$iso] ?? []),
                    'holiday',
                ]));
            }
            $holidaySentence = $salon->closedDaysAnnouncementSentence();
        }

        foreach ($items as $news) {
            if ($news->isTemporaryClosureAnnouncement()) {
                foreach ($news->closedDates as $closedDate) {
                    $date = $closedDate->closed_date;
                    if (! $date || ! $date->betweenIncluded($start, $end)) {
                        continue;
                    }
                    $iso = $date->format('Y-m-d');
                    $days[$iso] = array_values(array_unique([
                        ...($days[$iso] ?? []),
                        'temporary',
                    ]));
                    $notes[] = [
                        'date' => $iso,
                        'label' => $date->format('n月j日'),
                        'detail' => '臨時休業',
                        'categoryKey' => self::CATEGORY_TEMPORARY_CLOSURE,
                        'newsId' => (int) $news->id,
                    ];
                }
            }

            if ($news->isHoursAnnouncement() && $news->hours_change_date) {
                $date = $news->hours_change_date->copy()->startOfDay();
                if (! $date->betweenIncluded($start, $end)) {
                    continue;
                }
                $iso = $date->format('Y-m-d');
                $days[$iso] = array_values(array_unique([
                    ...($days[$iso] ?? []),
                    'hours',
                ]));
                $detail = $news->hoursChangeNoteDetail() ?? '営業時間変更';
                $notes[] = [
                    'date' => $iso,
                    'label' => $date->format('n月j日'),
                    'detail' => $detail,
                    'categoryKey' => self::CATEGORY_HOURS,
                    'newsId' => (int) $news->id,
                ];
            }
        }

        usort($notes, function (array $a, array $b) {
            return strcmp((string) ($a['date'] ?? ''), (string) ($b['date'] ?? ''));
        });

        if ($holidaySentence) {
            array_unshift($notes, [
                'date' => null,
                'label' => '',
                'detail' => $holidaySentence,
                'categoryKey' => self::CATEGORY_HOLIDAY,
                'newsId' => null,
            ]);
        }

        return [
            'key' => $key,
            'year' => $year,
            'month' => $month,
            'title' => $year.'年'.$month.'月 営業カレンダー',
            'footDate' => $year.'年'.$month.'月',
            'days' => $days,
            'notes' => $notes,
            'holidaySentence' => $holidaySentence,
        ];
    }
}
