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

        $this->assertStringContainsString('data-admin-confirm-trigger', $html);
        $this->assertStringContainsString('data-confirm-form="settings-form"', $html);
        $this->assertStringContainsString('data-confirm-title="店舗情報保存の確認"', $html);
        $this->assertStringContainsString('data-confirm-submit-label="保存する"', $html);
        $this->assertStringContainsString('店名、画像、店舗情報など、現在入力されている内容が反映されます。', $html);
        $this->assertStringContainsString('id="hero-image-error"', $html);
        $this->assertStringContainsString('id="logo-image-error"', $html);
        $this->assertStringContainsString('showImageError', $html);
        $this->assertStringNotContainsString('return confirm(', $html);
        $this->assertStringNotContainsString('alert(', $html);
    }
}
