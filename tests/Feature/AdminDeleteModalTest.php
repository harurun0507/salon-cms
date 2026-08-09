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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_news_defers_delete_to_bulk_save_without_delete_modal_trigger(): void
    {
        News::query()->create([
            'title' => '公開お知らせ',
            'slug' => 'published-news',
            'body' => 'body',
            'is_published' => true,
            'published_at' => now(),
            'display_order' => 1,
        ]);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.news.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-news-remove', $html);
        $this->assertStringContainsString('admin-icon-btn-delete', $html);
        $this->assertStringContainsString('id="news-deleted-ids"', $html);
        $this->assertStringContainsString("hidden.name = 'deleted_ids[]'", $html);
        $this->assertStringNotContainsString('data-delete-message="「公開お知らせ」を削除しますか？"', $html);
        $this->assertStringNotContainsString('return confirm(', $html);
        $this->assertStringContainsString('id="admin-delete-modal"', $html);
    }

    public function test_staff_defers_delete_to_bulk_save_without_delete_modal_trigger(): void
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

        $this->assertStringContainsString('data-staff-remove', $staffHtml);
        $this->assertStringContainsString('admin-icon-btn-delete', $staffHtml);
        $this->assertStringContainsString('id="staff-deleted-ids"', $staffHtml);
        $this->assertStringContainsString("hidden.name = 'deleted_ids[]'", $staffHtml);
        $this->assertStringNotContainsString('data-delete-message="「山田 花子」を削除しますか？"', $staffHtml);
        $this->assertStringNotContainsString('return confirm(', $staffHtml);
        $this->assertStringContainsString('id="admin-delete-modal"', $staffHtml);
    }

    public function test_staff_bulk_delete_via_save_works(): void
    {
        $staff = StaffMember::query()->create([
            'name' => '削除対象',
            'sort_order' => 1,
            'is_published' => false,
        ]);

        $this->actingAs($this->admin())
            ->put(route('admin.staff.bulk-update'), [
                'deleted_ids' => [$staff->id],
            ])
            ->assertRedirect(route('admin.staff.index'));

        $this->assertDatabaseMissing('staff_members', ['id' => $staff->id]);
    }

    public function test_gallery_defers_delete_to_bulk_save_without_delete_modal_trigger(): void
    {
        Gallery::query()->create([
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
        $menu = Menu::query()->create([
            'name' => 'カットベーシック',
            'price' => '¥5,000',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $menu->categories()->attach($category->id, ['sort_order' => 1]);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.menus.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(
            'data-delete-message="「カット」カテゴリを削除しますか？',
            $html
        );
        $this->assertStringContainsString(
            'このカテゴリのみに属するメニューがある場合は削除できません。"',
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
        $setting->update(['logo_image' => 'settings/logos/logo.png']);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('data-delete-message="メインビジュアル画像を削除しますか？"', $html);
        $this->assertStringContainsString('data-delete-message="ロゴ画像を削除しますか？"', $html);
        $this->assertStringContainsString('data-delete-form="logo-delete-form"', $html);
        $this->assertStringNotContainsString(
            "return confirm('店舗情報を保存します。公開サイトに反映されます。よろしいですか？')",
            $html
        );
    }

    public function test_hero_defers_delete_to_bulk_save_without_delete_modal_trigger(): void
    {
        $setting = SalonSetting::current();
        HeroImage::query()->create([
            'salon_setting_id' => $setting->id,
            'image_path' => 'settings/a.jpg',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.home.hero'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-hero-remove', $html);
        $this->assertStringContainsString('admin-icon-btn-delete', $html);
        $this->assertStringContainsString('id="hero-deleted-ids"', $html);
        $this->assertStringContainsString("hidden.name = 'deleted_ids[]'", $html);
        $this->assertStringNotContainsString('data-delete-message="メインビジュアル画像を削除しますか？"', $html);
        $this->assertStringNotContainsString('name="_method" value="DELETE"', $html);
        $this->assertStringNotContainsString('return confirm(', $html);
    }

    public function test_hero_bulk_delete_via_save_works(): void
    {
        Storage::fake('public');
        $setting = SalonSetting::current();
        $path = UploadedFile::fake()->image('hero-delete.jpg')->store('settings', 'public');
        $image = HeroImage::query()->create([
            'salon_setting_id' => $setting->id,
            'image_path' => $path,
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $this->actingAs($this->admin())
            ->put(route('admin.home.hero.update'), [
                'deleted_ids' => [$image->id],
            ])
            ->assertRedirect(route('admin.home.hero'));

        $this->assertDatabaseMissing('hero_images', ['id' => $image->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_news_bulk_delete_via_save_works(): void
    {
        $news = News::query()->create([
            'title' => '削除対象',
            'slug' => 'to-delete',
            'body' => 'body',
            'is_published' => false,
            'published_at' => null,
            'display_order' => 1,
        ]);

        $this->actingAs($this->admin())
            ->put(route('admin.news.update'), [
                'deleted_ids' => [$news->id],
            ])
            ->assertRedirect(route('admin.news.index'));

        $this->assertDatabaseMissing('news', ['id' => $news->id]);
    }
}
