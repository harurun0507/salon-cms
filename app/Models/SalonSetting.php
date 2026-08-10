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
        'access_directions',
        'business_hours',
        'closed_days',
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
            ['label' => '営業時間', 'value' => $this->business_hours, 'multiline' => true],
            ['label' => '定休日', 'value' => $this->closed_days, 'multiline' => false],
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
