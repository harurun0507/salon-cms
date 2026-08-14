<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalonClosedNthWeekday extends Model
{
    protected $fillable = [
        'salon_setting_id',
        'week_of_month',
        'weekday',
    ];

    protected $casts = [
        'week_of_month' => 'integer',
        'weekday' => 'integer',
    ];

    public function salonSetting(): BelongsTo
    {
        return $this->belongsTo(SalonSetting::class);
    }
}
