<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsClosedWeekday extends Model
{
    protected $fillable = [
        'news_id',
        'weekday',
    ];

    protected $casts = [
        'weekday' => 'integer',
    ];

    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class);
    }
}
