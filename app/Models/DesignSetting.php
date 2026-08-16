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

    public const SCROLL_COLORED_SCROLLBAR = 'colored_scrollbar';

    public const SCROLL_VERTICAL_INDICATOR = 'vertical_indicator';

    public const SCROLL_DISPLAY_TYPES = [
        self::SCROLL_COLORED_SCROLLBAR,
        self::SCROLL_VERTICAL_INDICATOR,
    ];

    public const DETAIL_DISPLAY_PAGE = 'page';

    public const DETAIL_DISPLAY_MODAL = 'modal';

    public const DETAIL_DISPLAY_TYPES = [
        self::DETAIL_DISPLAY_PAGE,
        self::DETAIL_DISPLAY_MODAL,
    ];

    public const MODAL_OVERLAY_LIGHT = 'light';

    public const MODAL_OVERLAY_STANDARD = 'standard';

    public const MODAL_OVERLAY_DARK = 'dark';

    public const MODAL_OVERLAY_BLUR = 'blur';

    public const MODAL_OVERLAY_STYLES = [
        self::MODAL_OVERLAY_LIGHT,
        self::MODAL_OVERLAY_STANDARD,
        self::MODAL_OVERLAY_DARK,
        self::MODAL_OVERLAY_BLUR,
    ];

    public const MODAL_OVERLAY_LABELS = [
        self::MODAL_OVERLAY_LIGHT => '薄い',
        self::MODAL_OVERLAY_STANDARD => '標準',
        self::MODAL_OVERLAY_DARK => '濃い',
        self::MODAL_OVERLAY_BLUR => 'ぼかしあり',
    ];

    /**
     * Overlay opacity / blur tokens for public content modals.
     * "blur" matches the previous hard-coded look (rgb(30 26 22 / 0.62) + blur(2px)).
     *
     * @var array<string, array{opacity: float, blur: string}>
     */
    public const MODAL_OVERLAY_TOKENS = [
        self::MODAL_OVERLAY_LIGHT => ['opacity' => 0.28, 'blur' => '0px'],
        self::MODAL_OVERLAY_STANDARD => ['opacity' => 0.45, 'blur' => '0px'],
        self::MODAL_OVERLAY_DARK => ['opacity' => 0.72, 'blur' => '0px'],
        self::MODAL_OVERLAY_BLUR => ['opacity' => 0.62, 'blur' => '2px'],
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
        'scroll_display_type' => self::SCROLL_COLORED_SCROLLBAR,
        'news_detail_display' => self::DETAIL_DISPLAY_PAGE,
        'blog_detail_display' => self::DETAIL_DISPLAY_PAGE,
        'gallery_detail_display' => self::DETAIL_DISPLAY_PAGE,
        'modal_overlay_style' => self::MODAL_OVERLAY_BLUR,
        'modal_overlay_color' => '#1e1a16',
        /** Soft fills matching the public business-calendar defaults. */
        'calendar_holiday_color' => '#d1d4c8',
        'calendar_temporary_color' => '#ded6cd',
        'calendar_hours_color' => '#d1c4b4',
        /** VI footer: warm dark brown + ivory text (LIFE LIKE–adjacent, not pure black). */
        'footer_background_color' => '#322824',
        'footer_text_color' => '#f3eee6',
        'footer_link_color' => '#e8e0d4',
        'footer_link_hover_color' => '#ffffff',
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
        'scroll_display_type',
        'news_detail_display',
        'blog_detail_display',
        'gallery_detail_display',
        'modal_overlay_style',
        'modal_overlay_color',
        'calendar_holiday_color',
        'calendar_temporary_color',
        'calendar_hours_color',
        'footer_background_color',
        'footer_text_color',
        'footer_link_color',
        'footer_link_hover_color',
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
     * Comma-separated RGB channels for use in rgba(var(--x), opacity).
     */
    public static function hexToRgbChannels(string $hex): string
    {
        $normalized = self::normalizeHex($hex);
        if (! self::isValidHex($normalized)) {
            $normalized = self::DEFAULTS['modal_overlay_color'];
        }

        return sprintf(
            '%d, %d, %d',
            hexdec(substr($normalized, 1, 2)),
            hexdec(substr($normalized, 3, 2)),
            hexdec(substr($normalized, 5, 2))
        );
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

    public function resolvedScrollDisplayType(): string
    {
        return $this->safeEnum(
            $this->scroll_display_type,
            self::SCROLL_DISPLAY_TYPES,
            self::DEFAULTS['scroll_display_type']
        );
    }

    public function usesVerticalScrollIndicator(): bool
    {
        return $this->resolvedScrollDisplayType() === self::SCROLL_VERTICAL_INDICATOR;
    }

    public function usesColoredScrollbar(): bool
    {
        return $this->resolvedScrollDisplayType() === self::SCROLL_COLORED_SCROLLBAR;
    }

    public function resolvedNewsDetailDisplay(): string
    {
        return $this->safeEnum(
            $this->news_detail_display,
            self::DETAIL_DISPLAY_TYPES,
            self::DEFAULTS['news_detail_display']
        );
    }

    public function resolvedBlogDetailDisplay(): string
    {
        return $this->safeEnum(
            $this->blog_detail_display,
            self::DETAIL_DISPLAY_TYPES,
            self::DEFAULTS['blog_detail_display']
        );
    }

    public function resolvedGalleryDetailDisplay(): string
    {
        return $this->safeEnum(
            $this->gallery_detail_display,
            self::DETAIL_DISPLAY_TYPES,
            self::DEFAULTS['gallery_detail_display']
        );
    }

    public function usesNewsDetailModal(): bool
    {
        return $this->resolvedNewsDetailDisplay() === self::DETAIL_DISPLAY_MODAL;
    }

    public function usesBlogDetailModal(): bool
    {
        return $this->resolvedBlogDetailDisplay() === self::DETAIL_DISPLAY_MODAL;
    }

    public function usesGalleryDetailModal(): bool
    {
        return $this->resolvedGalleryDetailDisplay() === self::DETAIL_DISPLAY_MODAL;
    }

    public function resolvedModalOverlayStyle(): string
    {
        return $this->safeEnum(
            $this->modal_overlay_style,
            self::MODAL_OVERLAY_STYLES,
            self::DEFAULTS['modal_overlay_style']
        );
    }

    public function resolvedModalOverlayColor(): string
    {
        return $this->safeHex($this->modal_overlay_color, self::DEFAULTS['modal_overlay_color']);
    }

    /**
     * @return array{opacity: float, blur: string}
     */
    public function resolvedModalOverlayTokens(): array
    {
        return self::MODAL_OVERLAY_TOKENS[$this->resolvedModalOverlayStyle()];
    }

    public function resolvedModalOverlayOpacity(): string
    {
        return number_format($this->resolvedModalOverlayTokens()['opacity'], 2, '.', '');
    }

    public function resolvedModalOverlayBlur(): string
    {
        return $this->resolvedModalOverlayTokens()['blur'];
    }

    /**
     * Whitelisted CSS custom properties for public site / admin preview.
     *
     * @return array<string, string>
     */
    /**
     * Resolved business-calendar fill colors (admin pickers / CSS).
     *
     * @return array{holiday: string, temporary: string, hours: string}
     */
    public function resolvedBusinessCalendarColors(): array
    {
        return [
            'holiday' => $this->safeHex(
                $this->calendar_holiday_color,
                self::DEFAULTS['calendar_holiday_color']
            ),
            'temporary' => $this->safeHex(
                $this->calendar_temporary_color,
                self::DEFAULTS['calendar_temporary_color']
            ),
            'hours' => $this->safeHex(
                $this->calendar_hours_color,
                self::DEFAULTS['calendar_hours_color']
            ),
        ];
    }

    /**
     * Derive readable text / border / accent from a calendar fill color.
     *
     * @return array{bg: string, border: string, text: string, accent: string}
     */
    public static function businessCalendarSwatchTokens(string $backgroundHex): array
    {
        $bg = self::isValidHex(self::normalizeHex($backgroundHex))
            ? self::normalizeHex($backgroundHex)
            : self::DEFAULTS['calendar_holiday_color'];
        $ink = '#3a332e';

        return [
            'bg' => $bg,
            'border' => self::mixHex($bg, $ink, 0.38),
            'text' => self::mixHex($bg, $ink, 0.62),
            'accent' => self::mixHex($bg, $ink, 0.72),
        ];
    }

    /**
     * Mix two #RRGGBB colors. $toRatio is the amount of $to (0–1).
     */
    public static function mixHex(string $from, string $to, float $toRatio): string
    {
        $from = self::normalizeHex($from);
        $to = self::normalizeHex($to);
        if (! self::isValidHex($from) || ! self::isValidHex($to)) {
            return self::DEFAULTS['text_color'];
        }

        $ratio = max(0.0, min(1.0, $toRatio));
        $fromRgb = self::hexToRgbArray($from);
        $toRgb = self::hexToRgbArray($to);

        $mixed = [
            (int) round($fromRgb[0] * (1 - $ratio) + $toRgb[0] * $ratio),
            (int) round($fromRgb[1] * (1 - $ratio) + $toRgb[1] * $ratio),
            (int) round($fromRgb[2] * (1 - $ratio) + $toRgb[2] * $ratio),
        ];

        return sprintf('#%02x%02x%02x', $mixed[0], $mixed[1], $mixed[2]);
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    public static function hexToRgbArray(string $hex): array
    {
        $hex = ltrim(self::normalizeHex($hex), '#');

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    public function cssVariables(): array
    {
        $primary = $this->safeHex($this->primary_color, self::DEFAULTS['primary_color']);
        $secondary = $this->safeHex($this->secondary_color, self::DEFAULTS['secondary_color']);
        $background = $this->safeHex($this->background_color, self::DEFAULTS['background_color']);
        $text = $this->safeHex($this->text_color, self::DEFAULTS['text_color']);
        $scrollbarThumb = $this->safeHex($this->scrollbar_thumb_color, self::DEFAULTS['scrollbar_thumb_color']);
        $scrollbarTrack = $this->safeHex($this->scrollbar_track_color, self::DEFAULTS['scrollbar_track_color']);
        $scrollbarThumbHover = $this->safeHex($this->scrollbar_thumb_hover_color, self::DEFAULTS['scrollbar_thumb_hover_color']);
        $footerBackground = $this->safeHex($this->footer_background_color, self::DEFAULTS['footer_background_color']);
        $footerText = $this->safeHex($this->footer_text_color, self::DEFAULTS['footer_text_color']);
        $footerLink = $this->safeHex($this->footer_link_color, self::DEFAULTS['footer_link_color']);
        $footerLinkHover = $this->safeHex($this->footer_link_hover_color, self::DEFAULTS['footer_link_hover_color']);
        $modalOverlayColor = $this->resolvedModalOverlayColor();
        $modalOverlayBlur = $this->resolvedModalOverlayBlur();
        $calendarColors = $this->resolvedBusinessCalendarColors();
        $holidayTokens = self::businessCalendarSwatchTokens($calendarColors['holiday']);
        $temporaryTokens = self::businessCalendarSwatchTokens($calendarColors['temporary']);
        $hoursTokens = self::businessCalendarSwatchTokens($calendarColors['hours']);

        return [
            '--site-primary' => $primary,
            '--site-secondary' => $secondary,
            '--site-secondary-soft' => self::secondarySoftFromAccent($secondary),
            '--site-background' => $background,
            '--site-text' => $text,
            '--site-scrollbar-thumb' => $scrollbarThumb,
            '--site-scrollbar-track' => $scrollbarTrack,
            '--site-scrollbar-thumb-hover' => $scrollbarThumbHover,
            '--site-footer-bg' => $footerBackground,
            '--site-footer-text' => $footerText,
            '--site-footer-link' => $footerLink,
            '--site-footer-link-hover' => $footerLinkHover,
            '--site-modal-overlay-color' => $modalOverlayColor,
            '--site-modal-overlay-rgb' => self::hexToRgbChannels($modalOverlayColor),
            '--site-modal-overlay-opacity' => $this->resolvedModalOverlayOpacity(),
            '--site-modal-overlay-blur' => $modalOverlayBlur,
            '--site-modal-overlay-filter' => $modalOverlayBlur === '0px' ? 'none' : 'blur('.$modalOverlayBlur.')',
            '--site-calendar-holiday-bg' => $holidayTokens['bg'],
            '--site-calendar-holiday-border' => $holidayTokens['border'],
            '--site-calendar-holiday-text' => $holidayTokens['text'],
            '--site-calendar-temporary-bg' => $temporaryTokens['bg'],
            '--site-calendar-temporary-border' => $temporaryTokens['border'],
            '--site-calendar-temporary-text' => $temporaryTokens['text'],
            '--site-calendar-hours-bg' => $hoursTokens['bg'],
            '--site-calendar-hours-border' => $hoursTokens['border'],
            '--site-calendar-hours-text' => $hoursTokens['text'],
            '--site-calendar-hours-accent' => $hoursTokens['accent'],
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
