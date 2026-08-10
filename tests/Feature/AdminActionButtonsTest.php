<?php

namespace Tests\Feature;

use App\Models\Gallery;
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

    public function test_news_cards_use_add_card_and_delete_icon_without_edit_button(): void
    {
        News::query()->create([
            'title' => 'Test',
            'slug' => 'test',
            'body' => 'body',
            'is_published' => true,
            'published_at' => now(),
            'display_order' => 1,
        ]);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.news.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="news-add-card"', $html);
        $this->assertStringContainsString('data-news-add', $html);
        $this->assertStringContainsString('data-news-add-top', $html);
        $this->assertStringContainsString('btn-admin-create', $html);
        $this->assertStringContainsString('admin-icon-btn-delete', $html);
        $this->assertStringContainsString('data-news-remove', $html);
        $this->assertStringContainsString('aria-label="削除"', $html);
        $this->assertStringContainsString('&times;', $html);
        $this->assertStringNotContainsString('admin-icon-btn-edit', $html);
        $this->assertStringNotContainsString('admin-action-group', $html);
        $this->assertStringNotContainsString('btn-admin-edit', $html);
    }

    public function test_staff_inline_cards_use_add_card_and_delete_icon_without_edit_button(): void
    {
        StaffMember::query()->create([
            'name' => 'Staff',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.staff.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="staff-add-card"', $html);
        $this->assertStringContainsString('data-staff-add', $html);
        $this->assertStringContainsString('btn-admin-create', $html);
        $this->assertStringContainsString('admin-icon-btn-delete', $html);
        $this->assertStringContainsString('data-staff-remove', $html);
        $this->assertStringContainsString('aria-label="削除"', $html);
        $this->assertStringContainsString('&times;', $html);
        $this->assertStringNotContainsString('admin-icon-btn-edit', $html);
        $this->assertStringNotContainsString('admin-action-group', $html);
        $this->assertStringNotContainsString('btn-admin-edit', $html);
    }

    public function test_gallery_inline_cards_use_add_card_and_delete_icon_without_edit_button(): void
    {
        Gallery::query()->create([
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.galleries.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="gallery-add-card"', $html);
        $this->assertStringContainsString('data-gallery-add', $html);
        $this->assertStringContainsString('btn-admin-create', $html);
        $this->assertStringContainsString('admin-icon-btn-delete', $html);
        $this->assertStringContainsString('data-gallery-remove', $html);
        $this->assertStringContainsString('aria-label="削除"', $html);
        $this->assertStringContainsString('&times;', $html);
        $this->assertStringNotContainsString('admin-icon-btn-edit', $html);
        $this->assertStringNotContainsString('admin-action-group', $html);
        $this->assertStringNotContainsString('btn-admin-edit', $html);
    }

    public function test_menus_and_settings_use_shared_delete_action_styles(): void
    {
        $user = $this->admin();
        $setting = SalonSetting::current();

        $category = MenuCategory::query()->create(['name' => 'カット', 'sort_order' => 1]);
        $menu = Menu::query()->create([
            'name' => 'カット',
            'price' => '¥5,000',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $menu->categories()->attach($category->id, ['sort_order' => 1]);
        $setting->update(['logo_image' => 'settings/logos/logo.png']);

        $menusHtml = $this->actingAs($user)->get(route('admin.menus.index'))->assertOk()->getContent();
        $this->assertStringContainsString('btn-admin-create', $menusHtml);
        $this->assertStringContainsString('data-admin-delete-trigger', $menusHtml);
        $this->assertStringContainsString('data-delete-form="delete-menu-', $menusHtml);
        $this->assertStringContainsString('category-delete-x', $menusHtml);
        $this->assertStringNotContainsString('data-open-detail', $menusHtml);
        $this->assertStringNotContainsString('id="menu-detail-modal"', $menusHtml);

        $settingsHtml = $this->actingAs($user)->get(route('admin.settings.edit'))->assertOk()->getContent();
        $this->assertStringContainsString('admin-icon-btn-delete', $settingsHtml);
        $this->assertStringContainsString('logo-delete-form', $settingsHtml);
    }
}
