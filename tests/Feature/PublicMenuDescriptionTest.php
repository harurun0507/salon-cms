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
        $this->assertStringContainsString('id="cut"', $html);
        $this->assertStringContainsString('href="#cut"', $html);
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
            strpos($html, 'href="#color"'),
            strpos($html, 'href="#cut"')
        );
        $this->assertLessThan(
            strpos($html, 'id="color"'),
            strpos($html, 'id="cut"')
        );
        $this->assertStringContainsString('>カット</a>', $html);
        $this->assertStringContainsString('>カラー</a>', $html);
        $this->assertStringNotContainsString('カット (2)', $html);
        $this->assertStringNotContainsString('カット（2）', $html);
        $this->assertStringContainsString('[data-menu-category-section]', $html);
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

    public function test_home_menu_section_hides_descriptions_and_shows_excerpt(): void
    {
        SalonSetting::current();

        $cut = MenuCategory::query()->create(['name' => 'カット', 'sort_order' => 1]);
        $empty = MenuCategory::query()->create(['name' => 'カラー', 'sort_order' => 2]);

        foreach ([1, 2, 3, 4] as $i) {
            $menu = Menu::query()->create([
                'name' => "カット{$i}",
                'price' => '¥'.(5000 + $i),
                'description' => "説明{$i}",
                'sort_order' => $i,
                'is_published' => true,
            ]);
            $menu->categories()->attach($cut->id, ['sort_order' => $i]);
        }

        $unpublished = Menu::query()->create([
            'name' => '非公開カット',
            'price' => '¥9,999',
            'description' => '非公開説明',
            'sort_order' => 5,
            'is_published' => false,
        ]);
        $unpublished->categories()->attach($cut->id, ['sort_order' => 5]);

        $html = $this->get(route('home'))->assertOk()->getContent();

        preg_match('/id="menu"[\s\S]*?<\/section>/', $html, $menuSection);
        $this->assertNotEmpty($menuSection);
        $section = $menuSection[0];

        $this->assertStringContainsString('home-menu-categories', $section);
        $this->assertStringContainsString('home-menu-category', $section);
        $this->assertStringContainsString('home-menu-category-en', $section);
        $this->assertStringContainsString('CUT', $section);
        $this->assertStringContainsString('カット', $section);
        $this->assertStringContainsString('カット1', $section);
        $this->assertStringContainsString('カット2', $section);
        $this->assertStringContainsString('カット3', $section);
        $this->assertStringNotContainsString('カット4', $section);
        $this->assertStringNotContainsString('非公開カット', $section);
        $this->assertStringNotContainsString('カラー', $section);
        $this->assertStringNotContainsString('説明1', $section);
        $this->assertStringNotContainsString('whitespace-pre-line', $section);
        $this->assertStringNotContainsString('menu-price-desc', $section);
        $this->assertStringContainsString('すべて見る', $section);
        $this->assertStringContainsString(route('menu', absolute: false), $section);
        $this->assertStringContainsString('home-menu-category-more', $section);
        $this->assertStringContainsString('home-menu-category-hit', $section);
        $this->assertStringContainsString('data-home-menu-href', $section);
        $this->assertStringContainsString(route('menu', absolute: false).'#cut', $section);
        $this->assertSame(0, $empty->publishedMenus()->count());
    }

    public function test_home_combination_excerpt_shows_tags_without_description(): void
    {
        SalonSetting::current();

        $combination = MenuCategory::query()->create([
            'name' => MenuCategory::NAME_COMBINATION,
            'sort_order' => 1,
            'allow_multiple_selection' => true,
        ]);
        $cut = MenuCategory::query()->create(['name' => 'カット', 'sort_order' => 2]);
        $color = MenuCategory::query()->create(['name' => 'カラー', 'sort_order' => 3]);

        $setMenu = Menu::query()->create([
            'name' => '[ ヘルシーな艶髪へ ]セット',
            'price' => '¥12,980',
            'description' => 'セット説明はトップ非表示',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $setMenu->categories()->attach([
            $combination->id => ['sort_order' => 1],
            $cut->id => ['sort_order' => 1],
            $color->id => ['sort_order' => 1],
        ]);

        $html = $this->get(route('home'))->assertOk()->getContent();

        preg_match('/id="menu"[\s\S]*?<\/section>/', $html, $menuSection);
        $this->assertNotEmpty($menuSection);
        $section = $menuSection[0];

        $this->assertStringContainsString('組み合わせ', $section);
        $this->assertStringContainsString('menu-price-tags', $section);
        $this->assertStringContainsString('>カット</span>', $section);
        $this->assertStringContainsString('>カラー</span>', $section);
        $this->assertStringContainsString('[ ヘルシーな艶髪へ ]セット', $section);
        $this->assertStringContainsString('¥12,980', $section);
        $this->assertStringNotContainsString('セット説明はトップ非表示', $section);
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
            '/id="cut"[\s\S]*?<\/section>/',
            $html,
            $cutSection
        );
        preg_match(
            '/id="head-spa"[\s\S]*?<\/section>/',
            $html,
            $spaSection
        );

        $this->assertNotEmpty($cutSection);
        $this->assertNotEmpty($spaSection);
        $this->assertStringContainsString('カット＆リラックスヘッドスパ', $cutSection[0]);
        $this->assertStringContainsString('カット＆リラックスヘッドスパ', $spaSection[0]);
        $this->assertSame(2, substr_count($html, 'カット＆リラックスヘッドスパ'));
    }

    public function test_combination_menu_shows_tags_only_under_combination_category(): void
    {
        SalonSetting::current();

        $combination = MenuCategory::query()->create([
            'name' => MenuCategory::NAME_COMBINATION,
            'sort_order' => 1,
            'allow_multiple_selection' => true,
        ]);
        $cut = MenuCategory::query()->create(['name' => 'カット', 'sort_order' => 2]);
        $color = MenuCategory::query()->create(['name' => 'カラー', 'sort_order' => 3]);
        $treatment = MenuCategory::query()->create(['name' => 'トリートメント', 'sort_order' => 4]);

        $setMenu = Menu::query()->create([
            'name' => '[ ヘルシーな艶髪へ ]カット＆カラー＆link高保湿トリートメント',
            'price' => '¥12,980',
            'description' => 'セット説明',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $setMenu->categories()->attach([
            $combination->id => ['sort_order' => 1],
            $cut->id => ['sort_order' => 1],
            $color->id => ['sort_order' => 1],
            $treatment->id => ['sort_order' => 1],
        ]);

        $standalone = Menu::query()->create([
            'name' => 'カット単品',
            'price' => '¥5,940',
            'sort_order' => 2,
            'is_published' => true,
        ]);
        $standalone->categories()->attach($cut->id, ['sort_order' => 2]);

        $html = $this->get(route('menu'))->assertOk()->getContent();

        preg_match(
            '/id="set"[\s\S]*?<\/section>/',
            $html,
            $comboSection
        );
        preg_match(
            '/id="cut"[\s\S]*?<\/section>/',
            $html,
            $cutSection
        );

        $this->assertNotEmpty($comboSection);
        $this->assertNotEmpty($cutSection);
        $this->assertStringContainsString('menu-price-tags', $comboSection[0]);
        $this->assertStringContainsString('menu-price-tag', $comboSection[0]);
        $this->assertStringContainsString('menu-price-tag-icon', $comboSection[0]);
        $this->assertStringContainsString('menu-price-tag-label', $comboSection[0]);
        $this->assertStringContainsString('>カット</span>', $comboSection[0]);
        $this->assertStringContainsString('>カラー</span>', $comboSection[0]);
        $this->assertStringContainsString('>トリートメント</span>', $comboSection[0]);
        $this->assertStringContainsString('[ ヘルシーな艶髪へ ]カット＆カラー＆link高保湿トリートメント', $comboSection[0]);
        $this->assertSame(1, substr_count($html, '[ ヘルシーな艶髪へ ]カット＆カラー＆link高保湿トリートメント'));
        $this->assertStringNotContainsString('[ ヘルシーな艶髪へ ]', $cutSection[0]);
        $this->assertStringContainsString('カット単品', $cutSection[0]);
        $this->assertStringNotContainsString('menu-price-tags', $cutSection[0]);
    }

    public function test_renamed_allow_multiple_category_keeps_set_menu_listing_rules(): void
    {
        SalonSetting::current();

        $setCategory = MenuCategory::query()->create([
            'name' => 'セットメニュー',
            'sort_order' => 1,
            'allow_multiple_selection' => true,
        ]);
        $cut = MenuCategory::query()->create([
            'name' => 'カット',
            'sort_order' => 2,
            'allow_multiple_selection' => false,
        ]);
        $color = MenuCategory::query()->create([
            'name' => 'カラー',
            'sort_order' => 3,
            'allow_multiple_selection' => false,
        ]);

        $setMenu = Menu::query()->create([
            'name' => 'カット＆カラーセット',
            'price' => '¥12,980',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $setMenu->categories()->attach([
            $setCategory->id => ['sort_order' => 1],
            $cut->id => ['sort_order' => 1],
            $color->id => ['sort_order' => 1],
        ]);

        $standalone = Menu::query()->create([
            'name' => 'カット単品',
            'price' => '¥5,940',
            'sort_order' => 2,
            'is_published' => true,
        ]);
        $standalone->categories()->attach($cut->id, ['sort_order' => 2]);

        $html = $this->get(route('menu'))->assertOk()->getContent();

        preg_match('/id="set"[\s\S]*?<\/section>/', $html, $setSection);
        preg_match('/id="cut"[\s\S]*?<\/section>/', $html, $cutSection);
        preg_match('/id="color"[\s\S]*?<\/section>/', $html, $colorSection);

        $this->assertNotEmpty($setSection);
        $this->assertNotEmpty($cutSection);
        $this->assertStringContainsString('セットメニュー', $setSection[0]);
        $this->assertStringContainsString('カット＆カラーセット', $setSection[0]);
        $this->assertStringContainsString('>カット</span>', $setSection[0]);
        $this->assertStringContainsString('>カラー</span>', $setSection[0]);
        $this->assertSame(1, substr_count($html, 'カット＆カラーセット'));
        $this->assertStringNotContainsString('カット＆カラーセット', $cutSection[0]);
        $this->assertStringContainsString('カット単品', $cutSection[0]);
        if ($colorSection !== []) {
            $this->assertStringNotContainsString('カット＆カラーセット', $colorSection[0]);
        }
    }
}
