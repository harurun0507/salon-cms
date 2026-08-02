<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalonSetting extends Model
{
    public const DISPLAY_TYPE_TEXT = 'text';

    public const DISPLAY_TYPE_LOGO = 'logo';

    public const TWITTER_CARD_SUMMARY = 'summary';

    public const TWITTER_CARD_SUMMARY_LARGE_IMAGE = 'summary_large_image';

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

    public function usesLogoInHeader(): bool
    {
        return $this->shop_name_display_type === self::DISPLAY_TYPE_LOGO
            && filled($this->logo_image);
    }

    public function logoAlt(): string
    {
        return filled($this->logo_alt_text) ? (string) $this->logo_alt_text : (string) $this->shop_name;
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

        return asset('favicon.ico');
    }

    public function hasGaMeasurementId(): bool
    {
        return filled($this->ga_measurement_id);
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
