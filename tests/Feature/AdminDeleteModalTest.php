<?php

namespace Tests\Feature;

use App\Models\Gallery;
use App\Models\HeroImage;
use App\Models\Menu;
use App\Models\MenuCategory;
use App\Models\News;
use App\Models\SalonSetting;
use App\Models\StaffMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDeleteModalTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    public function test_admin_layout_includes_shared_delete_modal(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="admin-delete-modal"', $html);
        $this->assertStringContainsString('削除の確認', $html);
        $this->assertStringContainsString('role="dialog"', $html);
        $this->assertStringContainsString('aria-modal="true"', $html);
        $this->assertStringContainsString('aria-labelledby="admin-delete-modal-title"', $html);
    }

    public function test_news_delete_uses_modal_trigger_with_title_and_no_confirm(): void
    {
        News::query()->create([
            'title' => '公開お知らせ',
            'slug' => 'published-news',
            'body' => 'body',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.news.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-admin-delete-trigger', $html);
        $this->assertStringContainsString('data-delete-message="「公開お知らせ」を削除しますか？"', $html);
        $this->assertStringContainsString('data-admin-delete-form', $html);
        $this->assertStringNotContainsString('return confirm(', $html);
        $this->assertStringContainsString('id="admin-delete-modal"', $html);
    }

    public function test_staff_delete_messages(): void
    {
        StaffMember::query()->create([
            'name' => '山田 花子',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $staffHtml = $this->actingAs($this->admin())
            ->get(route('admin.staff.index'))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('data-delete-message="「山田 花子」を削除しますか？"', $staffHtml);
        $this->assertStringNotContainsString('return confirm(', $staffHtml);
    }

    public function test_gallery_defers_delete_to_bulk_save_without_delete_modal_trigger(): void
    {
        Gallery::query()->create([
            'image_path' => 'galleries/a.jpg',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $galleryHtml = $this->actingAs($this->admin())
            ->get(route('admin.galleries.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-gallery-remove', $galleryHtml);
        $this->assertStringContainsString('admin-icon-btn-delete', $galleryHtml);
        $this->assertStringContainsString('id="gallery-deleted-ids"', $galleryHtml);
        $this->assertStringContainsString("hidden.name = 'deleted_ids[]'", $galleryHtml);
        $this->assertStringNotContainsString('data-delete-message="ギャラリー画像を削除しますか？"', $galleryHtml);
        $this->assertStringNotContainsString('return confirm(', $galleryHtml);
    }

    public function test_menus_delete_uses_external_form_triggers(): void
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

        $this->assertStringContainsString(
            'data-delete-message="「カット」カテゴリと配下のメニューを削除しますか？"',
            $html
        );
        $this->assertStringContainsString(
            'data-delete-message="「カットベーシック」を削除しますか？"',
            $html
        );
        $this->assertStringContainsString('data-delete-form="delete-category-'.$category->id.'"', $html);
        $this->assertStringNotContainsString("confirm('変更内容を一括保存します。よろしいですか？')", $html);
    }

    public function test_settings_image_delete_messages_without_save_confirm(): void
    {
        $setting = SalonSetting::current();
        HeroImage::query()->create([
            'salon_setting_id' => $setting->id,
            'image_path' => 'settings/a.jpg',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $setting->update(['logo_image' => 'settings/logos/logo.png']);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-delete-message="メインビジュアル画像を削除しますか？"', $html);
        $this->assertStringContainsString('data-delete-message="ロゴ画像を削除しますか？"', $html);
        $this->assertStringContainsString('data-delete-form="logo-delete-form"', $html);
        $this->assertStringNotContainsString(
            "return confirm('店舗情報を保存します。公開サイトに反映されます。よろしいですか？')",
            $html
        );
    }

    public function test_news_delete_endpoint_still_works(): void
    {
        $news = News::query()->create([
            'title' => '削除対象',
            'slug' => 'to-delete',
            'body' => 'body',
            'is_published' => false,
            'published_at' => null,
        ]);

        $this->actingAs($this->admin())
            ->delete(route('admin.news.destroy', $news))
            ->assertRedirect(route('admin.news.index'));

        $this->assertDatabaseMissing('news', ['id' => $news->id]);
    }
}
