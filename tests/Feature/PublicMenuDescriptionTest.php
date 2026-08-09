<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\MenuCategory;
use App\Models\SalonSetting;
use App\Models\DesignSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicMenuDescriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_page_preserves_newlines_with_whitespace_pre_line(): void
    {
        SalonSetting::current();

        $category = MenuCategory::query()->create(['name' => 'カット', 'sort_order' => 1]);
        $menu = Menu::query()->create([
            'name' => 'カットベーシック',
            'price' => '¥5,000',
            'description' => "1行目\n2行目",
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $menu->categories()->attach($category->id, ['sort_order' => 1]);

        $html = $this->get(route('menu'))->assertOk()->getContent();

        $this->assertStringContainsString('menu-price-desc', $html);
        $this->assertStringContainsString("1行目\n2行目", $html);
        $this->assertStringContainsString('¥5,000', $html);
        $this->assertStringNotContainsString('<br', $html);
        $this->assertStringContainsString('id="menu-category-'.$category->id.'"', $html);
        $this->assertStringContainsString('href="#menu-category-'.$category->id.'"', $html);
        $this->assertStringContainsString('menu-category-nav', $html);
        $this->assertStringContainsString('menu-category-nav-bar', $html);
        $this->assertStringContainsString('menu-category-nav-link', $html);
        $this->assertStringContainsString('data-menu-category-nav', $html);
        $this->assertStringContainsString('menu-price-row', $html);
        $this->assertStringContainsString('menu-price-leader', $html);
        $this->assertStringContainsString('menu-price-value', $html);
        $this->assertStringContainsString('menu-category-heading-en', $html);
        $this->assertStringContainsString('CUT', $html);
        $this->assertStringContainsString('menu-price-desc', $html);
    }

    public function test_menu_page_renders_inquiry_price_as_label(): void
    {
        SalonSetting::current();

        $category = MenuCategory::query()->create(['name' => 'その他', 'sort_order' => 1]);
        $menu = Menu::query()->create([
            'name' => 'ウィッグ相談',
            'price' => '要問い合わせ',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $menu->categories()->attach($category->id, ['sort_order' => 1]);

        $html = $this->get(route('menu'))->assertOk()->getContent();

        $this->assertStringContainsString('menu-price-inquiry', $html);
        $this->assertStringContainsString('要問い合わせ', $html);
        $this->assertStringContainsString('OTHER', $html);
        $this->assertStringContainsString('menu-price-leader', $html);
    }

    public function test_menu_page_category_nav_follows_sort_order_and_skips_counts(): void
    {
        SalonSetting::current();

        $second = MenuCategory::query()->create(['name' => 'カラー', 'sort_order' => 2]);
        $first = MenuCategory::query()->create(['name' => 'カット', 'sort_order' => 1]);
        $menu = Menu::query()->create([
            'name' => 'カットA',
            'price' => '¥4,000',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $menu->categories()->attach($first->id, ['sort_order' => 1]);
        $menu = Menu::query()->create([
            'name' => 'カットB',
            'price' => '¥5,000',
            'sort_order' => 2,
            'is_published' => true,
        ]);
        $menu->categories()->attach($first->id, ['sort_order' => 2]);
        $menu = Menu::query()->create([
            'name' => 'カラーA',
            'price' => '¥8,000',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $menu->categories()->attach($second->id, ['sort_order' => 1]);

        $html = $this->get(route('menu'))->assertOk()->getContent();

        $this->assertLessThan(
            strpos($html, 'href="#menu-category-'.$second->id.'"'),
            strpos($html, 'href="#menu-category-'.$first->id.'"')
        );
        $this->assertLessThan(
            strpos($html, 'id="menu-category-'.$second->id.'"'),
            strpos($html, 'id="menu-category-'.$first->id.'"')
        );
        $this->assertStringContainsString('>カット</a>', $html);
        $this->assertStringContainsString('>カラー</a>', $html);
        $this->assertStringNotContainsString('カット (2)', $html);
        $this->assertStringNotContainsString('カット（2）', $html);
        $this->assertStringContainsString('[id^="menu-category-"]', $html);
        $this->assertStringContainsString('data-menu-category-nav', $html);
        $this->assertStringContainsString('data-menu-category-link', $html);
        $this->assertStringContainsString('menu-category-nav-bar', $html);
        $this->assertStringContainsString('--menu-category-scroll-margin', $html);
        $this->assertStringContainsString('--site-header-offset', file_get_contents(resource_path('css/app.css')));
        $this->assertStringNotContainsString('IntersectionObserver', $html);
        $this->assertStringNotContainsString('section.hidden = !active', $html);
        $this->assertStringNotContainsString('event.preventDefault()', $html);
        $this->assertStringContainsString('setActive(link.getAttribute(\'href\')', $html);
    }

    public function test_menu_category_nav_bar_is_sticky_below_header(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));
        preg_match('/\.menu-category-nav-bar\s*\{([^}]+)\}/s', $css, $bar);

        $this->assertNotEmpty($bar[1] ?? null);
        $this->assertStringContainsString('position: sticky', $bar[1]);
        $this->assertStringContainsString('top: var(--site-header-offset', $bar[1]);
        $this->assertStringContainsString('background-color: var(--site-background', $bar[1]);
        $this->assertStringContainsString('z-index: 40', $bar[1]);
    }

    public function test_menu_category_nav_styles_use_design_accent_color_variable(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));
        preg_match_all('/\.menu-category-nav-link\s*\{([^}]+)\}/s', $css, $bases);
        $base = collect($bases[1] ?? [])->first(fn (string $block) => str_contains($block, 'background-color: #fff'));
        preg_match('/\.menu-category-nav-link\.is-active,\s*\n\s*\.menu-category-nav-link\[aria-current="true"\]\s*\{([^}]+)\}/s', $css, $active);
        preg_match('/\.menu-category-nav-link:hover\s*\{([^}]+)\}/s', $css, $hover);

        $this->assertNotEmpty($base);
        $this->assertNotEmpty($active[1] ?? null);
        $this->assertNotEmpty($hover[1] ?? null);
        $this->assertStringContainsString('var(--site-secondary', $base);
        $this->assertStringContainsString('background-color: #fff', $base);
        $this->assertStringNotContainsString('--site-primary', $base);
        $this->assertStringContainsString('var(--site-secondary', $active[1]);
        $this->assertStringContainsString('color: #fff', $active[1]);
        $this->assertStringContainsString('var(--site-secondary-soft', $hover[1]);
        $this->assertStringContainsString('var(--site-secondary', $hover[1]);
        $this->assertStringNotContainsString('color-mix(', $hover[1]);
        $this->assertSame('#d1d6cb', DesignSetting::secondarySoftFromAccent('#7c8a6a'));
    }

    public function test_home_menu_section_preserves_newlines_with_whitespace_pre_line(): void
    {
        SalonSetting::current();

        $category = MenuCategory::query()->create(['name' => 'カット', 'sort_order' => 1]);
        $menu = Menu::query()->create([
            'name' => 'カットベーシック',
            'price' => '¥5,000',
            'description' => "説明A\n説明B",
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $menu->categories()->attach($category->id, ['sort_order' => 1]);

        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('whitespace-pre-line', $html);
        $this->assertStringContainsString("説明A\n説明B", $html);
        $this->assertStringContainsString('¥5,000', $html);
    }

    public function test_menu_appears_in_each_assigned_category_on_public_page(): void
    {
        SalonSetting::current();

        $cut = MenuCategory::query()->create(['name' => 'カット', 'sort_order' => 1]);
        $spa = MenuCategory::query()->create(['name' => 'ヘッドスパ', 'sort_order' => 2]);
        $menu = Menu::query()->create([
            'name' => 'カット＆リラックスヘッドスパ',
            'price' => '¥8,800',
            'description' => 'セットメニュー',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $menu->categories()->attach([
            $cut->id => ['sort_order' => 1],
            $spa->id => ['sort_order' => 1],
        ]);

        $html = $this->get(route('menu'))->assertOk()->getContent();

        preg_match(
            '/id="menu-category-'.$cut->id.'"[\s\S]*?<\/section>/',
            $html,
            $cutSection
        );
        preg_match(
            '/id="menu-category-'.$spa->id.'"[\s\S]*?<\/section>/',
            $html,
            $spaSection
        );

        $this->assertNotEmpty($cutSection);
        $this->assertNotEmpty($spaSection);
        $this->assertStringContainsString('カット＆リラックスヘッドスパ', $cutSection[0]);
        $this->assertStringContainsString('カット＆リラックスヘッドスパ', $spaSection[0]);
        $this->assertSame(2, substr_count($html, 'カット＆リラックスヘッドスパ'));
    }
}
