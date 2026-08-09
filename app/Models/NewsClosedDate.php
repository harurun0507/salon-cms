<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class NewsClosedDate extends Model
{
    protected $fillable = [
        'news_id',
        'closed_date',
    ];

    protected $casts = [
        'closed_date' => 'date',
    ];

    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class);
    }

    /**
     * Published "closed / temporary closure" dates for reuse
     * (e.g. home / access business-day calendars).
     *
     * @return Collection<int, string> Y-m-d strings, unique & sorted
     */
    public static function publishedClosedDateValues(): Collection
    {
        return static::query()
            ->whereHas('news', function ($query) {
                $query->whereIn('category', News::closedDateCategoryKeys())
                    ->where('is_published', true)
                    ->where(function ($q) {
                        $q->whereNull('published_at')
                            ->orWhere('published_at', '<=', now());
                    });
            })
            ->orderBy('closed_date')
            ->pluck('closed_date')
            ->map(fn ($date) => $date->format('Y-m-d'))
            ->unique()
            ->values();
    }
}
