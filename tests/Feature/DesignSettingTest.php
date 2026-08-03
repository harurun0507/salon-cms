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
        $this->assertStringContainsString('スクロールバー', $html);
        $this->assertStringContainsString('スクロールバー表示例', $html);
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
            ]))
            ->assertRedirect(route('admin.system.design'))
            ->assertSessionHasErrors([
                'heading_font',
                'body_font',
                'button_radius',
                'card_radius',
                'layout_density',
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
        $this->assertStringContainsString('--site-background: #010203;', $html);
        $this->assertStringContainsString('--site-text: #040506;', $html);
        $this->assertStringContainsString('--site-scrollbar-thumb: #111213;', $html);
        $this->assertStringContainsString('--site-scrollbar-track: #141516;', $html);
        $this->assertStringContainsString('--site-scrollbar-thumb-hover: #171819;', $html);
        $this->assertStringContainsString('--site-button-radius: 8px;', $html);
        $this->assertStringContainsString('--site-card-radius: 16px;', $html);
        $this->assertStringContainsString('--site-section-spacing: 3rem;', $html);
        $this->assertStringContainsString('--site-card-padding: 1rem;', $html);
        $this->assertStringContainsString('--color-salon-button: #abcdef;', $html);
        $this->assertStringContainsString('Noto Sans JP', $html);
        $this->assertStringContainsString('Noto Serif JP', $html);
    }

    public function test_public_page_includes_default_css_vars(): void
    {
        $html = $this->get(route('home'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('--site-primary: #5f6f52;', $html);
        $this->assertStringContainsString('--site-scrollbar-thumb: #c8c0b2;', $html);
        $this->assertStringContainsString('--site-scrollbar-track: #f1ece3;', $html);
        $this->assertStringContainsString('--site-scrollbar-thumb-hover: #afa692;', $html);
        $this->assertStringContainsString('--site-button-radius: 9999px;', $html);
        $this->assertStringContainsString('--site-section-spacing: 5rem;', $html);
    }

    public function test_editor_cannot_access_design_settings(): void
    {
        $editor = User::factory()->editor()->create();

        $this->actingAs($editor)
            ->get(route('admin.system.design'))
            ->assertForbidden();
    }
}
