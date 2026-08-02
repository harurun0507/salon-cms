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
    }

    public function test_menus_bulk_save_uses_confirm_modal_without_browser_confirm(): void
    {
        $category = MenuCategory::query()->create(['name' => 'カット', 'sort_order' => 1]);
        Menu::query()->create([
            'menu_category_id' => $category->id,
            'name' => 'カットベーシック',
            'price' => 5000,
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

        $this->assertStringContainsString('sticky top-[4.5rem]', $html);
        $this->assertStringContainsString('-mx-4 -mt-4 mb-6', $html);
        $this->assertStringContainsString('data-admin-confirm-trigger', $html);
        $this->assertStringContainsString('data-confirm-form="settings-form"', $html);
        $this->assertStringContainsString('data-confirm-title="店舗情報保存の確認"', $html);
        $this->assertStringContainsString('data-confirm-submit-label="保存する"', $html);
        $this->assertStringContainsString('店名、ロゴ、店舗情報など、現在入力されている内容が反映されます。', $html);
        $this->assertStringContainsString('各項目を編集し、「保存する」でまとめて反映できます', $html);
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

        $this->assertStringContainsString('sticky top-[4.5rem]', $html);
        $this->assertStringContainsString('-mx-4 -mt-4 mb-6', $html);
        $this->assertStringContainsString('data-admin-confirm-trigger', $html);
        $this->assertStringContainsString('data-confirm-form="hero-form"', $html);
        $this->assertStringContainsString('data-confirm-title="メインビジュアル保存の確認"', $html);
        $this->assertStringContainsString('data-confirm-submit-label="保存する"', $html);
        $this->assertStringContainsString('画像・表示順・公開状態・altテキストなど、現在入力されている内容が反映されます。', $html);
        $this->assertStringContainsString('各項目を編集し、「保存する」でまとめて反映できます', $html);
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

        $this->assertStringContainsString('sticky top-[4.5rem]', $html);
        $this->assertStringContainsString('data-admin-confirm-trigger', $html);
        $this->assertStringContainsString('data-confirm-form="top-page-form"', $html);
        $this->assertStringContainsString('data-confirm-title="トップページ設定保存の確認"', $html);
        $this->assertStringContainsString('data-confirm-submit-label="保存する"', $html);
        $this->assertStringContainsString('ヒーロー、コンセプト、表示件数、セクション表示・表示順など、現在入力されている内容が反映されます。', $html);
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

        $this->assertStringContainsString('sticky top-[4.5rem]', $html);
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

        $this->assertStringContainsString('sticky top-[4.5rem]', $html);
        $this->assertStringContainsString('data-confirm-form="reservations-form"', $html);
        $this->assertStringContainsString('data-confirm-title="予約設定保存の確認"', $html);
        $this->assertStringContainsString('data-confirm-submit-label="保存する"', $html);
        $this->assertSame(1, substr_count($html, 'data-confirm-form="reservations-form"'));
    }

    public function test_galleries_bulk_save_uses_confirm_modal_without_browser_confirm(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('admin.galleries.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('sticky top-[4.5rem]', $html);
        $this->assertStringContainsString('-mx-4 -mt-4 mb-6', $html);
        $this->assertStringContainsString('data-admin-confirm-trigger', $html);
        $this->assertStringContainsString('data-confirm-form="galleries-bulk-form"', $html);
        $this->assertStringContainsString('data-confirm-title="一括保存の確認"', $html);
        $this->assertStringContainsString('data-confirm-submit-label="一括保存する"', $html);
        $this->assertStringContainsString('画像、キャプション、表示順、公開状態、削除など', $html);
        $this->assertStringContainsString('カードで編集し、「一括保存」で反映できます', $html);
        $this->assertStringNotContainsString('return confirm(', $html);
        $this->assertSame(1, substr_count($html, 'data-confirm-form="galleries-bulk-form"'));
    }

    public function test_banners_bulk_save_uses_confirm_modal_without_browser_confirm(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('admin.home.banners'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('sticky top-[4.5rem]', $html);
        $this->assertStringContainsString('-mx-4 -mt-4 mb-6', $html);
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

        $this->assertStringContainsString('sticky top-[4.5rem]', $html);
        $this->assertStringContainsString('-mx-4 -mt-4 mb-6', $html);
        $this->assertStringContainsString('data-admin-confirm-trigger', $html);
        $this->assertStringContainsString('data-confirm-form="news-bulk-form"', $html);
        $this->assertStringContainsString('data-confirm-title="お知らせ保存の確認"', $html);
        $this->assertStringContainsString('data-confirm-submit-label="保存する"', $html);
        $this->assertStringContainsString('タイトル、本文、公開日時、公開状態、表示順、削除など', $html);
        $this->assertStringContainsString('カードで編集し、「保存する」でまとめて反映できます', $html);
        $this->assertStringNotContainsString('return confirm(', $html);
        $this->assertSame(1, substr_count($html, 'data-confirm-form="news-bulk-form"'));
    }
}
