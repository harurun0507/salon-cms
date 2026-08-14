<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalonClosedWeekday extends Model
{
    protected $fillable = [
        'salon_setting_id',
        'weekday',
    ];

    protected $casts = [
        'weekday' => 'integer',
    ];

    public function salonSetting(): BelongsTo
    {
        return $this->belongsTo(SalonSetting::class);
    }
}
