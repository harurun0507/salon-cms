<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminSidebarNavTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    public function test_sidebar_shows_hierarchical_labels_and_not_old_menu_label(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('ダッシュボード', $html);
        $this->assertStringContainsString('ホームページ', $html);
        $this->assertStringContainsString('メインビジュアル', $html);
        $this->assertStringContainsString('トップページ設定', $html);
        $this->assertStringContainsString('バナー', $html);
        $this->assertStringContainsString('コンテンツ', $html);
        $this->assertStringContainsString('お知らせ', $html);
        $this->assertStringContainsString('ギャラリー', $html);
        $this->assertStringContainsString('>メニュー<', $html);
        $this->assertStringContainsString('スタッフ', $html);
        $this->assertStringContainsString('店舗情報', $html);
        $this->assertStringContainsString('基本情報', $html);
        $this->assertStringContainsString('SNS', $html);
        $this->assertStringContainsString('予約設定', $html);
        $this->assertStringContainsString('システム', $html);
        $this->assertStringContainsString('SEO', $html);
        $this->assertStringContainsString('管理ユーザー', $html);
        $this->assertStringContainsString('デザイン設定', $html);
        $this->assertStringContainsString('公開サイトを見る', $html);
        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringNotContainsString('メニュー・料金', $html);
        $this->assertStringContainsString('admin-nav-group', $html);
    }

    public function test_content_parent_is_open_when_news_is_active(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('admin.news.index'))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/<details[^>]*class="[^"]*admin-nav-group[^"]*"[^>]*data-nav-key="content"[^>]*data-nav-current="1"[^>]*\sopen(?:\s|>)/u',
            $html
        );
        $this->assertStringContainsString('admin-nav-link-active', $html);
        $this->assertStringContainsString(route('admin.news.index'), $html);
    }

    public function test_nav_groups_are_independent_not_exclusive_accordion(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertDoesNotMatchRegularExpression(
            '/<details[^>]*\sname=/u',
            $html
        );
        $this->assertStringContainsString('data-nav-key="home"', $html);
        $this->assertStringContainsString('data-nav-key="content"', $html);
        $this->assertStringContainsString('data-nav-key="store"', $html);
        $this->assertStringContainsString('data-nav-key="system"', $html);
        $this->assertStringContainsString('admin-sidebar-open-parents', $html);
    }

    public function test_dashboard_keeps_nav_groups_closed_by_default(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertDoesNotMatchRegularExpression(
            '/<details[^>]*class="[^"]*admin-nav-group[^"]*"[^>]*\sopen(?:\s|>)/u',
            $html
        );
        $this->assertStringNotContainsString('data-nav-current="1"', $html);
    }

    public function test_basic_info_uses_existing_settings_url(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(route('admin.settings.edit'), $html);
        $this->assertStringContainsString('基本情報', $html);
        $this->assertStringContainsString('admin-nav-link-active', $html);
    }

    public function test_hero_page_is_real_screen_not_placeholder(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('admin.home.hero'))
            ->assertOk()
            ->assertSee('メインビジュアル', false)
            ->assertDontSee('この機能は現在準備中です。', false)
            ->getContent();

        $this->assertStringContainsString('id="hero-form"', $html);
        $this->assertStringContainsString('admin-nav-link-active', $html);
        $this->assertMatchesRegularExpression(
            '/<details[^>]*class="[^"]*admin-nav-group[^"]*"[^>]*data-nav-key="home"[^>]*data-nav-current="1"[^>]*\sopen(?:\s|>)/u',
            $html
        );
    }

    public function test_hero_page_requires_authentication(): void
    {
        $this->get(route('admin.home.hero'))->assertRedirect();
    }

    #[DataProvider('realSettingScreensProvider')]
    public function test_setting_screens_are_real_not_placeholder(string $routeName, string $title, string $formId, string $navKey): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route($routeName))
            ->assertOk()
            ->assertSee($title, false)
            ->assertDontSee('この機能は現在準備中です。', false)
            ->getContent();

        $this->assertStringContainsString('id="'.$formId.'"', $html);
        $this->assertStringContainsString('admin-nav-link-active', $html);
        $this->assertMatchesRegularExpression(
            '/<details[^>]*class="[^"]*admin-nav-group[^"]*"[^>]*data-nav-key="'.$navKey.'"[^>]*data-nav-current="1"[^>]*\sopen(?:\s|>)/u',
            $html
        );
    }

    public function test_setting_screens_require_authentication(): void
    {
        $this->get(route('admin.home.top'))->assertRedirect();
        $this->get(route('admin.store.sns'))->assertRedirect();
        $this->get(route('admin.store.reservations'))->assertRedirect();
    }

    #[DataProvider('placeholderRoutesProvider')]
    public function test_placeholder_pages_render_coming_soon(string $routeName, string $title): void
    {
        $this->actingAs($this->admin())
            ->get(route($routeName))
            ->assertOk()
            ->assertSee($title, false)
            ->assertSee('この機能は現在準備中です。', false);
    }

    public function test_placeholder_pages_require_authentication(): void
    {
        $this->get(route('admin.system.seo'))->assertRedirect();
    }

    public static function realSettingScreensProvider(): array
    {
        return [
            ['admin.home.top', 'トップページ設定', 'top-page-form', 'home'],
            ['admin.store.sns', 'SNS', 'sns-form', 'store'],
            ['admin.store.reservations', '予約設定', 'reservations-form', 'store'],
        ];
    }

    public static function placeholderRoutesProvider(): array
    {
        return [
            ['admin.system.seo', 'SEO'],
            ['admin.system.users', '管理ユーザー'],
            ['admin.system.design', 'デザイン設定'],
        ];
    }
}
