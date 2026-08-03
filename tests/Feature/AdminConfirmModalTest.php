<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\MenuCategory;
use App\Models\SalonSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminConfirmModalTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    public function test_admin_layout_includes_shared_confirm_modal(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="admin-confirm-modal"', $html);
        $this->assertStringContainsString('aria-labelledby="admin-confirm-modal-title"', $html);
        $this->assertStringContainsString('id="admin-confirm-submit"', $html);
        $this->assertStringContainsString('data-admin-confirm-cancel', $html);
        $this->assertStringContainsString('id="admin-delete-modal"', $html);
        $this->assertStringContainsString('M12 21c-4.5-2.5-7.5-6.2-7.5-10.2C4.5 6.2 7.8 3 12 3c4.2 0 7.5 3.2 7.5 7.8 0 4-3 7.7-7.5 10.2Z', $html);
        $this->assertStringContainsString('admin-leaf-icon-circle', $html);
    }

    public function test_dashboard_page_title_uses_sidebar_dashboard_icon_not_leaf(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertTrue(
            (bool) preg_match('/admin-page-title-icon[^>]*>.*?<\/span>/s', $html, $matches),
            'Expected admin-page-title-icon markup'
        );

        $titleIcon = $matches[0];
        // Lucide Home path used by nav-icon name="dashboard"
        $this->assertStringContainsString('m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z', $titleIcon);
        $this->assertStringNotContainsString(
            'M12 21c-4.5-2.5-7.5-6.2-7.5-10.2C4.5 6.2 7.8 3 12 3c4.2 0 7.5 3.2 7.5 7.8 0 4-3 7.7-7.5 10.2Z',
            $titleIcon
        );
        $this->assertStringContainsString('admin-leaf-icon-circle', $html);
    }

    public function test_news_page_title_uses_sidebar_bell_icon_not_leaf(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('admin.news.index'))
            ->assertOk()
            ->getContent();

        $this->assertTrue(
            (bool) preg_match('/admin-page-title-icon[^>]*>.*?<\/span>/s', $html, $matches),
            'Expected admin-page-title-icon markup'
        );

        $titleIcon = $matches[0];
        // Lucide Bell path used by nav-icon name="bell"
        $this->assertStringContainsString('M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9', $titleIcon);
        $this->assertStringNotContainsString(
            'M12 21c-4.5-2.5-7.5-6.2-7.5-10.2C4.5 6.2 7.8 3 12 3c4.2 0 7.5 3.2 7.5 7.8 0 4-3 7.7-7.5 10.2Z',
            $titleIcon
        );
        $this->assertStringContainsString('admin-leaf-icon-circle', $html);
    }

    public function test_menus_bulk_save_uses_confirm_modal_without_browser_confirm(): void
    {
        $category = MenuCategory::query()->create(['name' => 'カット', 'sort_order' => 1]);
        Menu::query()->create([
            'menu_category_id' => $category->id,
            'name' => 'カットベーシック',
            'price' => '¥5,000',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.menus.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-admin-confirm-trigger', $html);
        $this->assertStringContainsString('data-confirm-form="menus-bulk-form"', $html);
        $this->assertStringContainsString('data-confirm-title="一括保存の確認"', $html);
        $this->assertStringContainsString('data-confirm-submit-label="一括保存する"', $html);
        $this->assertStringContainsString('カテゴリ、メニュー、料金、表示順、公開状態など', $html);
        $this->assertStringNotContainsString('return confirm(', $html);
        $this->assertStringNotContainsString("confirm('変更内容を一括保存します。よろしいですか？')", $html);
    }

    public function test_settings_save_uses_confirm_modal_and_inline_image_errors(): void
    {
        SalonSetting::current();

        $html = $this->actingAs($this->admin())
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('sticky top-0', $html);
        $this->assertStringContainsString('admin-save-bar sticky top-0', $html);
        $this->assertStringContainsString('data-admin-confirm-trigger', $html);
        $this->assertStringContainsString('data-confirm-form="settings-form"', $html);
        $this->assertStringContainsString('data-confirm-title="店舗情報保存の確認"', $html);
        $this->assertStringContainsString('data-confirm-submit-label="保存する"', $html);
        $this->assertStringContainsString('店名、ロゴ、店舗情報など、現在入力されている内容が反映されます。', $html);
        $this->assertStringContainsString('公開サイトに表示する店舗名・ロゴ・住所・営業時間などを設定します。', $html);
        $this->assertStringNotContainsString('id="hero-image-error"', $html);
        $this->assertStringContainsString('id="logo-image-error"', $html);
        $this->assertStringContainsString('showImageError', $html);
        $this->assertStringNotContainsString('return confirm(', $html);
        $this->assertStringNotContainsString('alert(', $html);
        $this->assertStringNotContainsString('mt-auto pt-2', $html);
        $this->assertSame(1, substr_count($html, 'data-confirm-form="settings-form"'));
    }

    public function test_hero_save_uses_confirm_modal_and_inline_image_errors(): void
    {
        SalonSetting::current();

        $html = $this->actingAs($this->admin())
            ->get(route('admin.home.hero'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('sticky top-0', $html);
        $this->assertStringContainsString('admin-save-bar sticky top-0', $html);
        $this->assertStringContainsString('data-admin-confirm-trigger', $html);
        $this->assertStringContainsString('data-confirm-form="hero-form"', $html);
        $this->assertStringContainsString('data-confirm-title="メインビジュアル保存の確認"', $html);
        $this->assertStringContainsString('data-confirm-submit-label="保存する"', $html);
        $this->assertStringContainsString('画像・表示順・公開状態・altテキストなど、現在入力されている内容が反映されます。', $html);
        $this->assertStringContainsString('トップページのメインビジュアルを登録します。推奨：横長画像', $html);
        $this->assertStringContainsString('id="hero-image-error"', $html);
        $this->assertStringContainsString('showImageError', $html);
        $this->assertStringNotContainsString('return confirm(', $html);
        $this->assertStringNotContainsString('alert(', $html);
        $this->assertSame(1, substr_count($html, 'data-confirm-form="hero-form"'));
    }

    public function test_top_page_save_uses_confirm_modal(): void
    {
        SalonSetting::current();

        $html = $this->actingAs($this->admin())
            ->get(route('admin.home.top'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('sticky top-0', $html);
        $this->assertStringContainsString('data-admin-confirm-trigger', $html);
        $this->assertStringContainsString('data-confirm-form="top-page-form"', $html);
        $this->assertStringContainsString('data-confirm-title="トップページ設定保存の確認"', $html);
        $this->assertStringContainsString('data-confirm-submit-label="保存する"', $html);
        $this->assertStringContainsString('ヒーロー、コンセプト、表示件数、トップページの表示順など、現在入力されている内容が反映されます。', $html);
        $this->assertStringNotContainsString('return confirm(', $html);
        $this->assertSame(1, substr_count($html, 'data-confirm-form="top-page-form"'));
    }

    public function test_sns_save_uses_confirm_modal(): void
    {
        SalonSetting::current();

        $html = $this->actingAs($this->admin())
            ->get(route('admin.store.sns'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('sticky top-0', $html);
        $this->assertStringContainsString('data-confirm-form="sns-form"', $html);
        $this->assertStringContainsString('data-confirm-title="SNS設定保存の確認"', $html);
        $this->assertStringContainsString('data-confirm-submit-label="保存する"', $html);
        $this->assertSame(1, substr_count($html, 'data-confirm-form="sns-form"'));
    }

    public function test_reservations_save_uses_confirm_modal(): void
    {
        SalonSetting::current();

        $html = $this->actingAs($this->admin())
            ->get(route('admin.store.reservations'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('sticky top-0', $html);
        $this->assertStringContainsString('data-confirm-form="reservations-form"', $html);
        $this->assertStringContainsString('data-confirm-title="予約設定保存の確認"', $html);
        $this->assertStringContainsString('data-confirm-submit-label="保存する"', $html);
        $this->assertSame(1, substr_count($html, 'data-confirm-form="reservations-form"'));
    }

    public function test_seo_save_uses_confirm_modal(): void
    {
        SalonSetting::current();

        $html = $this->actingAs($this->admin())
            ->get(route('admin.system.seo'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('sticky top-0', $html);
        $this->assertStringContainsString('data-confirm-form="seo-form"', $html);
        $this->assertStringContainsString('data-confirm-title="SEO設定保存の確認"', $html);
        $this->assertStringContainsString('data-confirm-submit-label="保存する"', $html);
        $this->assertSame(1, substr_count($html, 'data-confirm-form="seo-form"'));
    }

    public function test_design_save_uses_confirm_modal(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('admin.system.design'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('sticky top-0', $html);
        $this->assertStringContainsString('data-confirm-form="design-form"', $html);
        $this->assertStringContainsString('data-confirm-title="デザイン設定保存の確認"', $html);
        $this->assertStringContainsString('data-confirm-submit-label="保存する"', $html);
        $this->assertStringContainsString('data-confirm-callback="design-settings-reset"', $html);
        $this->assertSame(1, substr_count($html, 'data-confirm-form="design-form"'));
    }

    public function test_galleries_bulk_save_uses_confirm_modal_without_browser_confirm(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('admin.galleries.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('sticky top-0', $html);
        $this->assertStringContainsString('admin-save-bar sticky top-0', $html);
        $this->assertStringContainsString('data-admin-confirm-trigger', $html);
        $this->assertStringContainsString('data-confirm-form="galleries-bulk-form"', $html);
        $this->assertStringContainsString('data-confirm-title="ギャラリー保存の確認"', $html);
        $this->assertStringContainsString('data-confirm-submit-label="保存する"', $html);
        $this->assertStringContainsString('画像、タイトル、詳細、担当スタッフ、表示順、公開状態、削除など', $html);
        $this->assertStringContainsString('公開サイトに表示するギャラリーを登録・編集します。', $html);
        $this->assertStringNotContainsString('return confirm(', $html);
        $this->assertSame(1, substr_count($html, 'data-confirm-form="galleries-bulk-form"'));
    }

    public function test_banners_bulk_save_uses_confirm_modal_without_browser_confirm(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('admin.home.banners'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('sticky top-0', $html);
        $this->assertStringContainsString('admin-save-bar sticky top-0', $html);
        $this->assertStringContainsString('data-admin-confirm-trigger', $html);
        $this->assertStringContainsString('data-confirm-form="banners-bulk-form"', $html);
        $this->assertStringContainsString('data-confirm-title="バナー保存の確認"', $html);
        $this->assertStringContainsString('data-confirm-submit-label="保存する"', $html);
        $this->assertStringContainsString('画像、タイトル、リンク、表示場所、公開期間、公開状態、表示順、削除など', $html);
        $this->assertStringNotContainsString('return confirm(', $html);
        $this->assertSame(1, substr_count($html, 'data-confirm-form="banners-bulk-form"'));
    }

    public function test_news_bulk_save_uses_confirm_modal_without_browser_confirm(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('admin.news.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('sticky top-0', $html);
        $this->assertStringContainsString('admin-save-bar sticky top-0', $html);
        $this->assertStringContainsString('data-admin-confirm-trigger', $html);
        $this->assertStringContainsString('data-confirm-form="news-bulk-form"', $html);
        $this->assertStringContainsString('data-confirm-title="お知らせ保存の確認"', $html);
        $this->assertStringContainsString('data-confirm-submit-label="保存する"', $html);
        $this->assertStringContainsString('タイトル、本文、公開日時、公開状態、表示順、削除など', $html);
        $this->assertStringContainsString('公開サイトに表示するお知らせを登録・編集します。', $html);
        $this->assertStringNotContainsString('return confirm(', $html);
        $this->assertSame(1, substr_count($html, 'data-confirm-form="news-bulk-form"'));
    }

    public function test_staff_bulk_save_uses_confirm_modal_without_browser_confirm(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('admin.staff.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('sticky top-0', $html);
        $this->assertStringContainsString('admin-save-bar sticky top-0', $html);
        $this->assertStringContainsString('data-admin-confirm-trigger', $html);
        $this->assertStringContainsString('data-confirm-form="staff-bulk-form"', $html);
        $this->assertStringContainsString('data-confirm-title="スタッフ保存の確認"', $html);
        $this->assertStringContainsString('data-confirm-submit-label="保存する"', $html);
        $this->assertStringContainsString('写真、名前、役職、プロフィール、表示順、公開状態、削除など', $html);
        $this->assertStringContainsString('公開サイトに表示するスタッフ情報を登録・編集します。', $html);
        $this->assertStringNotContainsString('return confirm(', $html);
        $this->assertSame(1, substr_count($html, 'data-confirm-form="staff-bulk-form"'));
    }
}
