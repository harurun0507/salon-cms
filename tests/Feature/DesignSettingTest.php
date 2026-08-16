<?php

namespace Tests\Feature;

use App\Models\DesignSetting;
use App\Models\SalonSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DesignSettingTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge(DesignSetting::DEFAULTS, $overrides);
    }

    public function test_design_page_shows_with_defaults(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('admin.system.design'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('デザイン設定', $html);
        $this->assertStringContainsString('id="design-form"', $html);
        $this->assertStringContainsString('カラー', $html);
        $this->assertStringContainsString('フォント', $html);
        $this->assertStringContainsString('角丸', $html);
        $this->assertStringContainsString('レイアウト', $html);
        $this->assertStringContainsString('プレビュー', $html);
        $this->assertStringContainsString('初期値に戻す', $html);
        $this->assertStringContainsString('name="primary_color"', $html);
        $this->assertStringContainsString('name="secondary_color"', $html);
        $this->assertStringContainsString('name="background_color"', $html);
        $this->assertStringContainsString('name="text_color"', $html);
        $this->assertStringContainsString('name="scrollbar_thumb_color"', $html);
        $this->assertStringContainsString('name="scrollbar_track_color"', $html);
        $this->assertStringContainsString('name="scrollbar_thumb_hover_color"', $html);
        $this->assertStringContainsString('name="footer_background_color"', $html);
        $this->assertStringContainsString('name="footer_text_color"', $html);
        $this->assertStringContainsString('name="footer_link_color"', $html);
        $this->assertStringContainsString('name="footer_link_hover_color"', $html);
        $this->assertStringContainsString('フッター', $html);
        $this->assertStringContainsString('name="scroll_display_type"', $html);
        $this->assertStringContainsString('スクロール表示', $html);
        $this->assertStringContainsString('カラースクロールバー', $html);
        $this->assertStringContainsString('縦インジケーター', $html);
        $this->assertStringContainsString('詳細ページの表示方法', $html);
        $this->assertStringContainsString('name="news_detail_display"', $html);
        $this->assertStringContainsString('name="blog_detail_display"', $html);
        $this->assertStringContainsString('name="gallery_detail_display"', $html);
        $this->assertStringContainsString('画面遷移', $html);
        $this->assertStringContainsString('モーダル', $html);
        $this->assertStringContainsString('モーダル背景', $html);
        $this->assertStringContainsString('name="modal_overlay_style"', $html);
        $this->assertStringContainsString('name="modal_overlay_color"', $html);
        $this->assertStringContainsString('薄い', $html);
        $this->assertStringContainsString('標準', $html);
        $this->assertStringContainsString('濃い', $html);
        $this->assertStringContainsString('ぼかしあり', $html);
        $this->assertStringContainsString('value="'.DesignSetting::MODAL_OVERLAY_LIGHT.'"', $html);
        $this->assertStringContainsString('value="'.DesignSetting::MODAL_OVERLAY_BLUR.'"', $html);
        $this->assertStringContainsString('value="#1e1a16"', $html);
        $this->assertStringContainsString('スクロールバーの色', $html);
        $this->assertStringContainsString('スクロールバー表示例', $html);
        $this->assertStringContainsString('縦インジケーター表示例', $html);
        $this->assertStringContainsString('value="'.DesignSetting::SCROLL_COLORED_SCROLLBAR.'"', $html);
        $this->assertStringContainsString('value="'.DesignSetting::SCROLL_VERTICAL_INDICATOR.'"', $html);
        $this->assertStringContainsString('value="#5f6f52"', $html);
        $this->assertStringContainsString('value="#7c8a6a"', $html);
        $this->assertStringContainsString('value="#faf7f1"', $html);
        $this->assertStringContainsString('value="#3a332e"', $html);
        $this->assertStringContainsString('value="#c8c0b2"', $html);
        $this->assertStringContainsString('value="#f1ece3"', $html);
        $this->assertStringContainsString('value="#afa692"', $html);
        $this->assertStringContainsString('明朝体', $html);
        $this->assertStringContainsString('ゴシック体', $html);
        $this->assertStringContainsString('丸ゴシック体', $html);
        $this->assertStringContainsString('admin-segmented', $html);
        $this->assertStringContainsString('data-design-preview', $html);
        $this->assertStringContainsString('data-admin-confirm-trigger', $html);
        $this->assertStringContainsString('data-confirm-form="design-form"', $html);
        $this->assertStringContainsString('data-confirm-callback="design-settings-reset"', $html);
        $this->assertStringContainsString('デザイン設定を初期値へ戻します。保存するまでは公開サイトへ反映されません。', $html);
        $this->assertStringNotContainsString('この機能は現在準備中です。', $html);
    }

    public function test_design_save_persists_settings(): void
    {
        SalonSetting::current()->update(['shop_name' => 'Keep Shop']);

        $this->actingAs($this->admin())
            ->put(route('admin.system.design.update'), $this->validPayload([
                'primary_color' => '#AABBCC',
                'secondary_color' => '#DDEEFF',
                'background_color' => '#112233',
                'text_color' => '#445566',
                'scrollbar_thumb_color' => '#B1B2B3',
                'scrollbar_track_color' => '#C4C5C6',
                'scrollbar_thumb_hover_color' => '#D7D8D9',
                'scroll_display_type' => DesignSetting::SCROLL_VERTICAL_INDICATOR,
                'footer_background_color' => '#2A221E',
                'footer_text_color' => '#F5F0E8',
                'footer_link_color' => '#E0D8CC',
                'footer_link_hover_color' => '#FFFFFF',
                'news_detail_display' => DesignSetting::DETAIL_DISPLAY_MODAL,
                'blog_detail_display' => DesignSetting::DETAIL_DISPLAY_MODAL,
                'gallery_detail_display' => DesignSetting::DETAIL_DISPLAY_PAGE,
                'modal_overlay_style' => DesignSetting::MODAL_OVERLAY_LIGHT,
                'modal_overlay_color' => '#223344',
                'heading_font' => DesignSetting::FONT_SANS,
                'body_font' => DesignSetting::FONT_ROUNDED,
                'button_radius' => DesignSetting::RADIUS_SMALL,
                'card_radius' => DesignSetting::RADIUS_LARGE,
                'layout_density' => DesignSetting::DENSITY_COMPACT,
            ]))
            ->assertRedirect(route('admin.system.design'));

        $design = DesignSetting::current()->fresh();
        $this->assertSame('#aabbcc', $design->primary_color);
        $this->assertSame('#ddeeff', $design->secondary_color);
        $this->assertSame('#112233', $design->background_color);
        $this->assertSame('#445566', $design->text_color);
        $this->assertSame('#b1b2b3', $design->scrollbar_thumb_color);
        $this->assertSame('#c4c5c6', $design->scrollbar_track_color);
        $this->assertSame('#d7d8d9', $design->scrollbar_thumb_hover_color);
        $this->assertSame('#2a221e', $design->footer_background_color);
        $this->assertSame('#f5f0e8', $design->footer_text_color);
        $this->assertSame('#e0d8cc', $design->footer_link_color);
        $this->assertSame('#ffffff', $design->footer_link_hover_color);
        $this->assertSame(DesignSetting::SCROLL_VERTICAL_INDICATOR, $design->scroll_display_type);
        $this->assertTrue($design->usesVerticalScrollIndicator());
        $this->assertSame(DesignSetting::DETAIL_DISPLAY_MODAL, $design->news_detail_display);
        $this->assertSame(DesignSetting::DETAIL_DISPLAY_MODAL, $design->blog_detail_display);
        $this->assertSame(DesignSetting::DETAIL_DISPLAY_PAGE, $design->gallery_detail_display);
        $this->assertTrue($design->usesNewsDetailModal());
        $this->assertTrue($design->usesBlogDetailModal());
        $this->assertFalse($design->usesGalleryDetailModal());
        $this->assertSame(DesignSetting::MODAL_OVERLAY_LIGHT, $design->modal_overlay_style);
        $this->assertSame('#223344', $design->modal_overlay_color);
        $this->assertSame('0.28', $design->resolvedModalOverlayOpacity());
        $this->assertSame('0px', $design->resolvedModalOverlayBlur());
        $this->assertSame(DesignSetting::FONT_SANS, $design->heading_font);
        $this->assertSame(DesignSetting::FONT_ROUNDED, $design->body_font);
        $this->assertSame(DesignSetting::RADIUS_SMALL, $design->button_radius);
        $this->assertSame(DesignSetting::RADIUS_LARGE, $design->card_radius);
        $this->assertSame(DesignSetting::DENSITY_COMPACT, $design->layout_density);
        $this->assertSame('Keep Shop', SalonSetting::current()->fresh()->shop_name);
    }

    public function test_design_rejects_invalid_color(): void
    {
        DesignSetting::current()->update(['primary_color' => '#5f6f52']);

        $this->actingAs($this->admin())
            ->from(route('admin.system.design'))
            ->put(route('admin.system.design.update'), $this->validPayload([
                'primary_color' => 'red',
            ]))
            ->assertRedirect(route('admin.system.design'))
            ->assertSessionHasErrors('primary_color');

        $this->actingAs($this->admin())
            ->from(route('admin.system.design'))
            ->put(route('admin.system.design.update'), $this->validPayload([
                'primary_color' => '#fff',
            ]))
            ->assertRedirect(route('admin.system.design'))
            ->assertSessionHasErrors('primary_color');

        $this->actingAs($this->admin())
            ->from(route('admin.system.design'))
            ->put(route('admin.system.design.update'), $this->validPayload([
                'primary_color' => '#GGGGGG',
            ]))
            ->assertRedirect(route('admin.system.design'))
            ->assertSessionHasErrors('primary_color');

        $this->actingAs($this->admin())
            ->from(route('admin.system.design'))
            ->put(route('admin.system.design.update'), $this->validPayload([
                'scrollbar_thumb_color' => 'green',
            ]))
            ->assertRedirect(route('admin.system.design'))
            ->assertSessionHasErrors('scrollbar_thumb_color');

        $this->assertSame('#5f6f52', DesignSetting::current()->fresh()->primary_color);
    }

    public function test_design_rejects_invalid_enums(): void
    {
        $this->actingAs($this->admin())
            ->from(route('admin.system.design'))
            ->put(route('admin.system.design.update'), $this->validPayload([
                'heading_font' => 'comic',
                'body_font' => '',
                'button_radius' => 'huge',
                'card_radius' => 'tiny',
                'layout_density' => 'loose',
                'scroll_display_type' => 'invalid_type',
                'news_detail_display' => 'popup',
                'blog_detail_display' => 'window',
                'gallery_detail_display' => 'overlay',
                'modal_overlay_style' => 'neon',
            ]))
            ->assertRedirect(route('admin.system.design'))
            ->assertSessionHasErrors([
                'heading_font',
                'body_font',
                'button_radius',
                'card_radius',
                'layout_density',
                'scroll_display_type',
                'news_detail_display',
                'blog_detail_display',
                'gallery_detail_display',
                'modal_overlay_style',
            ]);
    }

    public function test_design_reset_is_client_side_only_until_save(): void
    {
        DesignSetting::current()->update([
            'primary_color' => '#112233',
            'secondary_color' => '#445566',
            'background_color' => '#778899',
            'text_color' => '#aabbcc',
            'heading_font' => DesignSetting::FONT_SANS,
            'body_font' => DesignSetting::FONT_ROUNDED,
            'button_radius' => DesignSetting::RADIUS_SMALL,
            'card_radius' => DesignSetting::RADIUS_LARGE,
            'layout_density' => DesignSetting::DENSITY_RELAXED,
        ]);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.system.design'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-confirm-callback="design-settings-reset"', $html);
        $this->assertStringContainsString('design-settings-reset', $html);
        $this->assertStringContainsString('"primary_color":"#5f6f52"', $html);
        $this->assertStringContainsString('"scrollbar_thumb_color":"#c8c0b2"', $html);
        $this->assertStringContainsString('"scrollbar_track_color":"#f1ece3"', $html);
        $this->assertStringContainsString('"scrollbar_thumb_hover_color":"#afa692"', $html);
        $this->assertStringContainsString('"footer_background_color":"#322824"', $html);
        $this->assertStringContainsString('"footer_text_color":"#f3eee6"', $html);
        $this->assertStringContainsString('"footer_link_color":"#e8e0d4"', $html);
        $this->assertStringContainsString('"footer_link_hover_color":"#ffffff"', $html);
        $this->assertStringContainsString('"scroll_display_type":"colored_scrollbar"', $html);
        $this->assertStringContainsString('"news_detail_display":"page"', $html);
        $this->assertStringContainsString('"blog_detail_display":"page"', $html);
        $this->assertStringContainsString('"gallery_detail_display":"page"', $html);

        $saved = DesignSetting::current()->fresh();
        $this->assertSame('#112233', $saved->primary_color);
        $this->assertSame(DesignSetting::FONT_SANS, $saved->heading_font);
        $this->assertSame(DesignSetting::DENSITY_RELAXED, $saved->layout_density);
    }

    public function test_public_page_includes_css_vars_when_design_set(): void
    {
        DesignSetting::current()->update([
            'primary_color' => '#abcdef',
            'secondary_color' => '#fedcba',
            'background_color' => '#010203',
            'text_color' => '#040506',
            'scrollbar_thumb_color' => '#111213',
            'scrollbar_track_color' => '#141516',
            'scrollbar_thumb_hover_color' => '#171819',
            'scroll_display_type' => DesignSetting::SCROLL_COLORED_SCROLLBAR,
            'heading_font' => DesignSetting::FONT_SANS,
            'body_font' => DesignSetting::FONT_SERIF,
            'button_radius' => DesignSetting::RADIUS_SMALL,
            'card_radius' => DesignSetting::RADIUS_LARGE,
            'layout_density' => DesignSetting::DENSITY_COMPACT,
        ]);

        $html = $this->get(route('home'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="site-design-vars"', $html);
        $this->assertStringContainsString('--site-primary: #abcdef;', $html);
        $this->assertStringContainsString('--site-secondary: #fedcba;', $html);
        $this->assertStringContainsString(
            '--site-secondary-soft: '.DesignSetting::secondarySoftFromAccent('#fedcba').';',
            $html
        );
        $this->assertStringContainsString('--site-background: #010203;', $html);
        $this->assertStringContainsString('--site-text: #040506;', $html);
        $this->assertStringContainsString('--site-scrollbar-thumb: #111213;', $html);
        $this->assertStringContainsString('--site-scrollbar-track: #141516;', $html);
        $this->assertStringContainsString('--site-scrollbar-thumb-hover: #171819;', $html);
        $this->assertStringContainsString('--site-footer-bg: #322824;', $html);
        $this->assertStringContainsString('--site-footer-text: #f3eee6;', $html);
        $this->assertStringContainsString('--site-footer-link: #e8e0d4;', $html);
        $this->assertStringContainsString('--site-footer-link-hover: #ffffff;', $html);
        $this->assertStringContainsString('--site-modal-overlay-color: #1e1a16;', $html);
        $this->assertStringContainsString('--site-modal-overlay-rgb: 30, 26, 22;', $html);
        $this->assertStringContainsString('--site-modal-overlay-opacity: 0.62;', $html);
        $this->assertStringContainsString('--site-modal-overlay-filter: blur(2px);', $html);
        $this->assertStringContainsString('--site-button-radius: 8px;', $html);
        $this->assertStringContainsString('--site-card-radius: 16px;', $html);
        $this->assertStringContainsString('--site-section-spacing: 3rem;', $html);
        $this->assertStringContainsString('--site-card-padding: 1rem;', $html);
        $this->assertStringContainsString('--color-salon-button: #abcdef;', $html);
        $this->assertStringContainsString('Noto Sans JP', $html);
        $this->assertStringContainsString('Noto Serif JP', $html);
        $this->assertStringContainsString('data-scroll-display="colored_scrollbar"', $html);
        $this->assertStringNotContainsString('data-site-section-dots', $html);
        $this->assertStringContainsString('site-mobile-bottom-bar', $html);
    }

    public function test_public_page_includes_default_css_vars(): void
    {
        $html = $this->get(route('home'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('--site-primary: #5f6f52;', $html);
        $this->assertStringContainsString(
            '--site-secondary-soft: '.DesignSetting::secondarySoftFromAccent(DesignSetting::DEFAULTS['secondary_color']).';',
            $html
        );
        $this->assertStringContainsString('--site-scrollbar-thumb: #c8c0b2;', $html);
        $this->assertStringContainsString('--site-scrollbar-track: #f1ece3;', $html);
        $this->assertStringContainsString('--site-scrollbar-thumb-hover: #afa692;', $html);
        $this->assertStringContainsString('--site-footer-bg: #322824;', $html);
        $this->assertStringContainsString('--site-footer-text: #f3eee6;', $html);
        $this->assertStringContainsString('--site-footer-link: #e8e0d4;', $html);
        $this->assertStringContainsString('--site-footer-link-hover: #ffffff;', $html);
        $this->assertStringContainsString('--site-modal-overlay-color: #1e1a16;', $html);
        $this->assertStringContainsString('--site-modal-overlay-opacity: 0.62;', $html);
        $this->assertStringContainsString('--site-modal-overlay-filter: blur(2px);', $html);
        $this->assertStringContainsString('--site-button-radius: 9999px;', $html);
        $this->assertStringContainsString('--site-section-spacing: 5rem;', $html);
        $this->assertStringContainsString('data-scroll-display="colored_scrollbar"', $html);
        $this->assertStringContainsString('site-mobile-bottom-bar', $html);
        $this->assertStringNotContainsString('data-site-section-dots', $html);
    }

    public function test_public_page_applies_selected_modal_overlay_css_vars(): void
    {
        DesignSetting::current()->update([
            'modal_overlay_style' => DesignSetting::MODAL_OVERLAY_STANDARD,
            'modal_overlay_color' => '#334455',
        ]);

        $html = $this->get(route('home'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('--site-modal-overlay-color: #334455;', $html);
        $this->assertStringContainsString('--site-modal-overlay-rgb: 51, 68, 85;', $html);
        $this->assertStringContainsString('--site-modal-overlay-opacity: 0.45;', $html);
        $this->assertStringContainsString('--site-modal-overlay-filter: none;', $html);
        $this->assertSame(DesignSetting::MODAL_OVERLAY_STANDARD, DesignSetting::current()->fresh()->resolvedModalOverlayStyle());
    }

    public function test_unset_modal_overlay_settings_use_compatible_defaults(): void
    {
        $design = new DesignSetting([
            'modal_overlay_style' => null,
            'modal_overlay_color' => null,
        ]);

        $this->assertSame(DesignSetting::MODAL_OVERLAY_BLUR, $design->resolvedModalOverlayStyle());
        $this->assertSame('#1e1a16', $design->resolvedModalOverlayColor());
        $this->assertSame('0.62', $design->resolvedModalOverlayOpacity());
        $this->assertSame('2px', $design->resolvedModalOverlayBlur());
        $this->assertSame('30, 26, 22', DesignSetting::hexToRgbChannels($design->resolvedModalOverlayColor()));
    }

    public function test_public_page_shows_vertical_indicator_when_selected(): void
    {
        SalonSetting::current()->update(['shop_name' => 'Indicator Salon']);
        DesignSetting::current()->update([
            'scroll_display_type' => DesignSetting::SCROLL_VERTICAL_INDICATOR,
        ]);

        $html = $this->get(route('home'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-scroll-display="vertical_indicator"', $html);
        $this->assertStringContainsString('data-site-section-dots', $html);
        $this->assertStringContainsString('site-section-dots', $html);
        $this->assertStringContainsString('セクションナビゲーション', $html);
        $this->assertStringContainsString('site-mobile-bottom-bar', $html);
        $this->assertStringContainsString('Indicator Salon', $html);
        $this->assertStringContainsString('id="hero-slider"', $html);
        $this->assertStringContainsString('hero-slider-panel', $html);
        $this->assertStringContainsString('data-site-header', $html);
        $this->assertStringContainsString('home-vi-concept', $html);
        $this->assertStringContainsString('home-vi-access', $html);
        $this->assertStringContainsString('home-vi-access__cards', $html);
        $this->assertStringContainsString('site-business-calendar', $html);
        $this->assertStringContainsString('site-business-calendar--access', $html);
        $this->assertStringContainsString('site-business-calendar__access-foot', $html);
        $this->assertStringContainsString('content-modal__calendar-legend', $html);
        $this->assertStringNotContainsString('content-modal__calendar-notes', $html);
        $this->assertStringContainsString('site-header-tools', $html);
        $this->assertStringContainsString('site-mobile-nav-meta', $html);
        $this->assertStringContainsString('Privacy Policy', $html);
        $this->assertStringContainsString('site-footer', $html);
        $this->assertStringContainsString('site-footer--vi', $html);
        $this->assertStringContainsString('--site-footer-bg:', $html);
        $this->assertStringContainsString('site-footer__link', $html);
        $this->assertStringNotContainsString('rounded-full bg-salon-line', $html);
        // VI hash landing: no Tailwind scroll-smooth; instant jump helpers present
        $this->assertStringNotContainsString('class="scroll-smooth"', $html);
        $this->assertStringContainsString("html[data-scroll-display='vertical_indicator']", $html);
        $this->assertStringContainsString('scroll-behavior: auto', $html);
        $this->assertStringContainsString('function jumpToSectionInstant', $html);
        $this->assertStringContainsString('function indexForHash', $html);
        $this->assertStringContainsString("applyHashTarget({ instant: true })", $html);
        $this->assertTrue(DesignSetting::current()->fresh()->usesVerticalScrollIndicator());
        $this->assertFalse(DesignSetting::current()->fresh()->usesColoredScrollbar());
    }

    public function test_colored_scrollbar_home_keeps_classic_section_layout(): void
    {
        DesignSetting::current()->update([
            'scroll_display_type' => DesignSetting::SCROLL_COLORED_SCROLLBAR,
        ]);

        $html = $this->get(route('home'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="concept"', $html);
        $this->assertStringContainsString('site-section', $html);
        $this->assertStringNotContainsString('home-vi-concept', $html);
        $this->assertStringNotContainsString('home-vi-gallery', $html);
        $this->assertStringNotContainsString('home-vi-access', $html);
    }

    public function test_unset_scroll_display_type_defaults_to_colored_scrollbar(): void
    {
        $design = new DesignSetting(['scroll_display_type' => null]);

        $this->assertSame(DesignSetting::SCROLL_COLORED_SCROLLBAR, $design->resolvedScrollDisplayType());
        $this->assertTrue($design->usesColoredScrollbar());
        $this->assertFalse($design->usesVerticalScrollIndicator());
    }

    public function test_unset_detail_display_defaults_to_page(): void
    {
        $design = new DesignSetting([
            'news_detail_display' => null,
            'blog_detail_display' => null,
            'gallery_detail_display' => null,
        ]);

        $this->assertSame(DesignSetting::DETAIL_DISPLAY_PAGE, $design->resolvedNewsDetailDisplay());
        $this->assertSame(DesignSetting::DETAIL_DISPLAY_PAGE, $design->resolvedBlogDetailDisplay());
        $this->assertSame(DesignSetting::DETAIL_DISPLAY_PAGE, $design->resolvedGalleryDetailDisplay());
        $this->assertFalse($design->usesNewsDetailModal());
        $this->assertFalse($design->usesBlogDetailModal());
        $this->assertFalse($design->usesGalleryDetailModal());
    }

    public function test_editor_cannot_access_design_settings(): void
    {
        $editor = User::factory()->editor()->create();

        $this->actingAs($editor)
            ->get(route('admin.system.design'))
            ->assertForbidden();
    }
}
