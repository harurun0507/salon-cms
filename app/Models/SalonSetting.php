<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalonSetting extends Model
{
    public const DISPLAY_TYPE_TEXT = 'text';

    public const DISPLAY_TYPE_LOGO = 'logo';

    protected $fillable = [
        'shop_name',
        'shop_name_display_type',
        'logo_image',
        'logo_alt_text',
        'hero_label',
        'hero_title',
        'concept_title',
        'concept',
        'address',
        'business_hours',
        'closed_days',
        'phone',
        'google_map_url',
        'google_map_embed_url',
        'instagram_url',
        'hot_pepper_url',
        'hero_image',
    ];

    public function heroImages(): HasMany
    {
        return $this->hasMany(HeroImage::class)->ordered();
    }

    public function publishedHeroImages(): HasMany
    {
        return $this->hasMany(HeroImage::class)->published();
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

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'shop_name' => 'Sun＆ Me',
            'shop_name_display_type' => self::DISPLAY_TYPE_TEXT,
            'concept' => '一人ひとりの髪質やライフスタイルに合わせた、丁寧なカウンセリングと施術を大切にしています。',
        ]);
    }
}
