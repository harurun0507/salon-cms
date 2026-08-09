<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DesignSetting extends Model
{
    public const FONT_SERIF = 'serif';

    public const FONT_SANS = 'sans';

    public const FONT_ROUNDED = 'rounded';

    public const FONTS = [
        self::FONT_SERIF,
        self::FONT_SANS,
        self::FONT_ROUNDED,
    ];

    public const RADIUS_SMALL = 'small';

    public const RADIUS_MEDIUM = 'medium';

    public const RADIUS_LARGE = 'large';

    public const RADII = [
        self::RADIUS_SMALL,
        self::RADIUS_MEDIUM,
        self::RADIUS_LARGE,
    ];

    public const DENSITY_COMPACT = 'compact';

    public const DENSITY_STANDARD = 'standard';

    public const DENSITY_RELAXED = 'relaxed';

    public const DENSITIES = [
        self::DENSITY_COMPACT,
        self::DENSITY_STANDARD,
        self::DENSITY_RELAXED,
    ];

    /** Soft fill derived from accent: ~35% accent + ~65% white (hover backgrounds). */
    public const SECONDARY_SOFT_MIX_AMOUNT = 0.35;

    /** Current public site tokens from resources/css/app.css @theme */
    public const DEFAULTS = [
        'primary_color' => '#5f6f52',
        'secondary_color' => '#7c8a6a',
        'background_color' => '#faf7f1',
        'text_color' => '#3a332e',
        'scrollbar_thumb_color' => '#c8c0b2',
        'scrollbar_track_color' => '#f1ece3',
        'scrollbar_thumb_hover_color' => '#afa692',
        'heading_font' => self::FONT_SERIF,
        'body_font' => self::FONT_SANS,
        'button_radius' => self::RADIUS_LARGE,
        'card_radius' => self::RADIUS_MEDIUM,
        'layout_density' => self::DENSITY_STANDARD,
    ];

    public const FONT_STACKS = [
        self::FONT_SERIF => "'Noto Serif JP', 'Hiragino Mincho ProN', ui-serif, Georgia, serif",
        self::FONT_SANS => "'Noto Sans JP', 'Hiragino Sans', 'Yu Gothic', ui-sans-serif, system-ui, sans-serif",
        self::FONT_ROUNDED => "'Hiragino Maru Gothic ProN', 'Hiragino Sans', 'Yu Gothic', 'Noto Sans JP', ui-sans-serif, system-ui, sans-serif",
    ];

    /** Button: current design uses rounded-full (9999px) as large */
    public const BUTTON_RADIUS_PX = [
        self::RADIUS_SMALL => '8px',
        self::RADIUS_MEDIUM => '16px',
        self::RADIUS_LARGE => '9999px',
    ];

    /** Card: current access cards use rounded-lg (8px) as medium */
    public const CARD_RADIUS_PX = [
        self::RADIUS_SMALL => '4px',
        self::RADIUS_MEDIUM => '8px',
        self::RADIUS_LARGE => '16px',
    ];

    /** Section spacing / card padding — standard matches py-20 (5rem) / p-6 (1.5rem) */
    public const DENSITY_TOKENS = [
        self::DENSITY_COMPACT => [
            'section_spacing' => '3rem',
            'card_padding' => '1rem',
        ],
        self::DENSITY_STANDARD => [
            'section_spacing' => '5rem',
            'card_padding' => '1.5rem',
        ],
        self::DENSITY_RELAXED => [
            'section_spacing' => '7rem',
            'card_padding' => '2rem',
        ],
    ];

    protected $fillable = [
        'primary_color',
        'secondary_color',
        'background_color',
        'text_color',
        'scrollbar_thumb_color',
        'scrollbar_track_color',
        'scrollbar_thumb_hover_color',
        'heading_font',
        'body_font',
        'button_radius',
        'card_radius',
        'layout_density',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate([], self::DEFAULTS);
    }

    public static function isValidHex(string $value): bool
    {
        return (bool) preg_match('/^#[0-9a-fA-F]{6}$/', $value);
    }

    public static function normalizeHex(string $value): string
    {
        return strtolower(trim($value));
    }

    /**
     * Mix a hex color toward white. $amount is the source color weight (0–1).
     * Example: 0.35 keeps ~35% accent and ~65% white for soft hover fills.
     */
    public static function mixHexWithWhite(string $hex, float $amount): string
    {
        $normalized = self::normalizeHex($hex);
        if (! self::isValidHex($normalized)) {
            return '#ffffff';
        }

        $amount = max(0.0, min(1.0, $amount));
        $r = hexdec(substr($normalized, 1, 2));
        $g = hexdec(substr($normalized, 3, 2));
        $b = hexdec(substr($normalized, 5, 2));

        $mix = static fn (int $channel): int => (int) round(($channel * $amount) + (255 * (1 - $amount)));

        return sprintf('#%02x%02x%02x', $mix($r), $mix($g), $mix($b));
    }

    public static function secondarySoftFromAccent(string $hex): string
    {
        return self::mixHexWithWhite($hex, self::SECONDARY_SOFT_MIX_AMOUNT);
    }

    public function resolvedHeadingFontStack(): string
    {
        return self::FONT_STACKS[$this->safeEnum($this->heading_font, self::FONTS, self::DEFAULTS['heading_font'])];
    }

    public function resolvedBodyFontStack(): string
    {
        return self::FONT_STACKS[$this->safeEnum($this->body_font, self::FONTS, self::DEFAULTS['body_font'])];
    }

    public function resolvedButtonRadius(): string
    {
        return self::BUTTON_RADIUS_PX[$this->safeEnum($this->button_radius, self::RADII, self::DEFAULTS['button_radius'])];
    }

    public function resolvedCardRadius(): string
    {
        return self::CARD_RADIUS_PX[$this->safeEnum($this->card_radius, self::RADII, self::DEFAULTS['card_radius'])];
    }

    public function resolvedSectionSpacing(): string
    {
        $density = $this->safeEnum($this->layout_density, self::DENSITIES, self::DEFAULTS['layout_density']);

        return self::DENSITY_TOKENS[$density]['section_spacing'];
    }

    public function resolvedCardPadding(): string
    {
        $density = $this->safeEnum($this->layout_density, self::DENSITIES, self::DEFAULTS['layout_density']);

        return self::DENSITY_TOKENS[$density]['card_padding'];
    }

    /**
     * Whitelisted CSS custom properties for public site / admin preview.
     *
     * @return array<string, string>
     */
    public function cssVariables(): array
    {
        $primary = $this->safeHex($this->primary_color, self::DEFAULTS['primary_color']);
        $secondary = $this->safeHex($this->secondary_color, self::DEFAULTS['secondary_color']);
        $background = $this->safeHex($this->background_color, self::DEFAULTS['background_color']);
        $text = $this->safeHex($this->text_color, self::DEFAULTS['text_color']);
        $scrollbarThumb = $this->safeHex($this->scrollbar_thumb_color, self::DEFAULTS['scrollbar_thumb_color']);
        $scrollbarTrack = $this->safeHex($this->scrollbar_track_color, self::DEFAULTS['scrollbar_track_color']);
        $scrollbarThumbHover = $this->safeHex($this->scrollbar_thumb_hover_color, self::DEFAULTS['scrollbar_thumb_hover_color']);

        return [
            '--site-primary' => $primary,
            '--site-secondary' => $secondary,
            '--site-secondary-soft' => self::secondarySoftFromAccent($secondary),
            '--site-background' => $background,
            '--site-text' => $text,
            '--site-scrollbar-thumb' => $scrollbarThumb,
            '--site-scrollbar-track' => $scrollbarTrack,
            '--site-scrollbar-thumb-hover' => $scrollbarThumbHover,
            '--site-button-radius' => $this->resolvedButtonRadius(),
            '--site-card-radius' => $this->resolvedCardRadius(),
            '--site-section-spacing' => $this->resolvedSectionSpacing(),
            '--site-card-padding' => $this->resolvedCardPadding(),
            '--site-heading-font' => $this->resolvedHeadingFontStack(),
            '--site-body-font' => $this->resolvedBodyFontStack(),
            '--color-salon-button' => $primary,
            '--color-salon-accent' => $secondary,
            '--color-salon-bg' => $background,
            '--color-salon-text' => $text,
            '--font-serif' => $this->resolvedHeadingFontStack(),
            '--font-sans' => $this->resolvedBodyFontStack(),
        ];
    }

    public function cssVariablesStyleBlock(): string
    {
        $lines = [];
        foreach ($this->cssVariables() as $name => $value) {
            $lines[] = $name.': '.$value.';';
        }

        return implode("\n            ", $lines);
    }

    /**
     * @param  list<string>  $allowed
     */
    private function safeEnum(?string $value, array $allowed, string $fallback): string
    {
        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    private function safeHex(?string $value, string $fallback): string
    {
        $normalized = self::normalizeHex((string) $value);

        return self::isValidHex($normalized) ? $normalized : $fallback;
    }
}
