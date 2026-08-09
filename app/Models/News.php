<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'published_at',
        'is_published',
        'display_order',
    ];

    protected $casts = [
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
        return [
            self::CATEGORY_HOLIDAY,
            self::CATEGORY_CLOSED,
        ];
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
        return self::usesClosedWeekdays($this->category);
    }

    public function isTemporaryClosureAnnouncement(): bool
    {
        return self::usesClosedDates($this->category);
    }

    public function isClosedAnnouncement(): bool
    {
        return $this->isHolidayAnnouncement() || $this->isTemporaryClosureAnnouncement();
    }

    public function closedWeekdaysSentence(): ?string
    {
        $weekdays = $this->closedWeekdays
            ->pluck('weekday')
            ->map(fn ($day) => (int) $day)
            ->filter(fn (int $day) => array_key_exists($day, self::WEEKDAY_LABELS))
            ->unique()
            ->sort()
            ->values();

        if ($weekdays->isEmpty()) {
            return null;
        }

        $labels = $weekdays
            ->map(fn (int $day) => self::WEEKDAY_LABELS[$day])
            ->implode('・');

        return '定休日は毎週'.$labels.'です。';
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
}
