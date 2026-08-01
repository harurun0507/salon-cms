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

class AdminActionButtonsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    public function test_news_staff_and_gallery_lists_use_shared_action_button_classes(): void
    {
        $user = $this->admin();

        News::query()->create([
            'title' => 'Test',
            'slug' => 'test',
            'body' => 'body',
            'is_published' => true,
            'published_at' => now(),
        ]);
        StaffMember::query()->create([
            'name' => 'Staff',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        Gallery::query()->create([
            'image_path' => 'galleries/a.jpg',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        foreach ([
            route('admin.news.index'),
            route('admin.staff.index'),
            route('admin.galleries.index'),
        ] as $url) {
            $html = $this->actingAs($user)->get($url)->assertOk()->getContent();
            $this->assertStringContainsString('btn-admin-create', $html);
            $this->assertStringContainsString('btn-admin-edit', $html);
            $this->assertStringContainsString('btn-admin-delete', $html);
            $this->assertStringContainsString('admin-action-group', $html);
        }
    }

    public function test_menus_and_settings_use_shared_delete_action_styles(): void
    {
        $user = $this->admin();
        $setting = SalonSetting::current();

        $category = MenuCategory::query()->create(['name' => 'カット', 'sort_order' => 1]);
        Menu::query()->create([
            'menu_category_id' => $category->id,
            'name' => 'カット',
            'price' => 5000,
            'sort_order' => 1,
            'is_published' => true,
        ]);
        HeroImage::query()->create([
            'salon_setting_id' => $setting->id,
            'image_path' => 'settings/a.jpg',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $setting->update(['logo_image' => 'settings/logos/logo.png']);

        $menusHtml = $this->actingAs($user)->get(route('admin.menus.index'))->assertOk()->getContent();
        $this->assertStringContainsString('btn-admin-create', $menusHtml);
        $this->assertStringContainsString('btn-admin-edit', $menusHtml);
        $this->assertStringContainsString('btn-admin-delete', $menusHtml);
        $this->assertStringContainsString('data-open-detail', $menusHtml);
        $this->assertStringContainsString('btn-admin-edit', $menusHtml);
        $this->assertStringContainsString('>編集</span>', $menusHtml);
        $this->assertStringNotContainsString('>詳細編集</span>', $menusHtml);

        $settingsHtml = $this->actingAs($user)->get(route('admin.settings.edit'))->assertOk()->getContent();
        $this->assertStringContainsString('btn-admin-delete', $settingsHtml);
        $this->assertStringContainsString('logo-delete-form', $settingsHtml);
    }
}
