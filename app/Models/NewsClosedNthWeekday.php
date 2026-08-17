<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsClosedNthWeekday extends Model
{
    protected $fillable = [
        'news_id',
        'week_of_month',
        'weekday',
    ];

    protected $casts = [
        'week_of_month' => 'integer',
        'weekday' => 'integer',
    ];

    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class);
    }
}
