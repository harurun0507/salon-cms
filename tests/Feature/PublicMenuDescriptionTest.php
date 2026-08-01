<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\MenuCategory;
use App\Models\SalonSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicMenuDescriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_page_preserves_newlines_with_whitespace_pre_line(): void
    {
        SalonSetting::current();

        $category = MenuCategory::query()->create(['name' => 'カット', 'sort_order' => 1]);
        Menu::query()->create([
            'menu_category_id' => $category->id,
            'name' => 'カットベーシック',
            'price' => 5000,
            'description' => "1行目\n2行目",
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $html = $this->get(route('menu'))->assertOk()->getContent();

        $this->assertStringContainsString('whitespace-pre-line', $html);
        $this->assertStringContainsString("1行目\n2行目", $html);
        $this->assertStringNotContainsString('<br', $html);
    }

    public function test_home_menu_section_preserves_newlines_with_whitespace_pre_line(): void
    {
        SalonSetting::current();

        $category = MenuCategory::query()->create(['name' => 'カット', 'sort_order' => 1]);
        Menu::query()->create([
            'menu_category_id' => $category->id,
            'name' => 'カットベーシック',
            'price' => 5000,
            'description' => "説明A\n説明B",
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('whitespace-pre-line', $html);
        $this->assertStringContainsString("説明A\n説明B", $html);
    }
}
