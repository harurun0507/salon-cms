<?php

namespace Tests\Feature;

use App\Models\SalonSetting;
use App\Models\TopPageSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalonSettingScreensTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    private function topPagePayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'hero_label' => 'Personal Hair Salon',
            'hero_title' => "あなたらしさに、\n少しだけ今っぽさを。",
            'concept_title' => 'ナチュラルに、自分らしく。',
            'concept' => '丁寧なカウンセリングを大切にしています。',
            'section_order' => TopPageSection::KEYS,
            'sections' => [
                'banner' => ['is_visible' => '1'],
                'news' => ['display_count' => 3, 'is_visible' => '1'],
                'menu' => ['display_count' => 6, 'is_visible' => '1'],
                'gallery' => ['display_count' => 6, 'is_visible' => '1'],
                'staff' => ['display_count' => 4, 'is_visible' => '1'],
                'access' => ['is_visible' => '1'],
            ],
        ], $overrides);
    }

    public function test_top_page_shows_section_setting_card_titles(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.home.top'))
            ->assertOk()
            ->assertSee('トップページ表示件数')
            ->assertSee('セクション表示・表示順')
            ->assertSee('ヒーロー設定')
            ->assertSee('コンセプト設定');
    }

    public function test_top_page_shows_and_saves_copy_fields(): void
    {
        SalonSetting::current()->update([
            'hero_label' => 'Old Label',
            'hero_title' => 'Old Title',
            'concept_title' => 'Old Concept',
            'concept' => 'Old body',
            'instagram_url' => 'https://instagram.com/untouched',
            'shop_name' => 'Untouched Shop',
        ]);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.home.top'))
            ->assertOk()
            ->assertSee('トップページ表示件数')
            ->assertSee('セクション表示・表示順');

        $html = $response->getContent();

        $this->assertStringContainsString('ヒーロー設定', $html);
        $this->assertStringContainsString('コンセプト設定', $html);
        $this->assertStringContainsString('トップページ表示件数', $html);
        $this->assertStringContainsString('セクション表示・表示順', $html);
        $this->assertStringContainsString('name="hero_label"', $html);
        $this->assertStringContainsString('name="hero_title"', $html);
        $this->assertStringContainsString('name="concept_title"', $html);
        $this->assertStringContainsString('name="concept"', $html);
        $this->assertStringContainsString('name="sections[news][display_count]"', $html);
        $this->assertStringContainsString('name="sections[menu][display_count]"', $html);
        $this->assertStringContainsString('name="sections[gallery][display_count]"', $html);
        $this->assertStringContainsString('name="sections[staff][display_count]"', $html);
        $this->assertStringNotContainsString('name="sections[access][display_count]"', $html);
        $this->assertStringNotContainsString('name="sections[banner][display_count]"', $html);
        $this->assertStringContainsString('name="section_order[]"', $html);
        $this->assertStringContainsString('バナー', $html);
        $this->assertStringContainsString('data-top-section-drag-handle', $html);
        $this->assertStringContainsString('admin-switch', $html);
        $this->assertStringNotContainsString('name="shop_name"', $html);
        $this->assertStringNotContainsString('name="instagram_url"', $html);
        $this->assertStringNotContainsString('name="hot_pepper_url"', $html);

        $this->actingAs($this->admin())
            ->put(route('admin.home.top.update'), $this->topPagePayload())
            ->assertRedirect(route('admin.home.top'));

        $fresh = SalonSetting::current()->fresh();
        $this->assertSame('Personal Hair Salon', $fresh->hero_label);
        $this->assertSame("あなたらしさに、\n少しだけ今っぽさを。", $fresh->hero_title);
        $this->assertSame('ナチュラルに、自分らしく。', $fresh->concept_title);
        $this->assertSame('丁寧なカウンセリングを大切にしています。', $fresh->concept);
        $this->assertSame('https://instagram.com/untouched', $fresh->instagram_url);
        $this->assertSame('Untouched Shop', $fresh->shop_name);
    }

    public function test_top_page_saves_section_counts_visibility_and_order(): void
    {
        TopPageSection::ensureDefaults();

        $this->actingAs($this->admin())
            ->put(route('admin.home.top.update'), $this->topPagePayload([
                'section_order' => ['access', 'staff', 'gallery', 'menu', 'news', 'banner'],
                'sections' => [
                    'banner' => ['is_visible' => '0'],
                    'news' => ['display_count' => 5, 'is_visible' => '0'],
                    'menu' => ['display_count' => 8, 'is_visible' => '1'],
                    'gallery' => ['display_count' => 2, 'is_visible' => '0'],
                    'staff' => ['display_count' => 1, 'is_visible' => '1'],
                    'access' => ['is_visible' => '1'],
                ],
            ]))
            ->assertRedirect(route('admin.home.top'));

        $byKey = TopPageSection::query()->get()->keyBy('section_key');

        $this->assertSame(1, $byKey['access']->display_order);
        $this->assertSame(2, $byKey['staff']->display_order);
        $this->assertSame(3, $byKey['gallery']->display_order);
        $this->assertSame(4, $byKey['menu']->display_order);
        $this->assertSame(5, $byKey['news']->display_order);
        $this->assertSame(6, $byKey['banner']->display_order);

        $this->assertFalse($byKey['banner']->is_visible);
        $this->assertFalse($byKey['news']->is_visible);
        $this->assertTrue($byKey['menu']->is_visible);
        $this->assertFalse($byKey['gallery']->is_visible);
        $this->assertTrue($byKey['staff']->is_visible);
        $this->assertTrue($byKey['access']->is_visible);

        $this->assertNull($byKey['banner']->display_count);
        $this->assertSame(5, $byKey['news']->display_count);
        $this->assertSame(8, $byKey['menu']->display_count);
        $this->assertSame(2, $byKey['gallery']->display_count);
        $this->assertSame(1, $byKey['staff']->display_count);
        $this->assertNull($byKey['access']->display_count);

        $home = $this->get(route('home'))->assertOk()->getContent();
        $this->assertStringNotContainsString('id="news"', $home);
        $this->assertStringNotContainsString('id="gallery"', $home);
        $this->assertStringNotContainsString('id="banners"', $home);
        $this->assertStringContainsString('id="menu"', $home);
        $this->assertStringContainsString('id="staff"', $home);
        $this->assertStringContainsString('id="access"', $home);
        $this->assertLessThan(strpos($home, 'id="staff"'), strpos($home, 'id="access"'));
        $this->assertLessThan(strpos($home, 'id="menu"'), strpos($home, 'id="staff"'));
    }

    public function test_sns_page_shows_and_saves_instagram_url(): void
    {
        SalonSetting::current()->update([
            'instagram_url' => 'https://instagram.com/old',
            'hot_pepper_url' => 'https://beauty.hotpepper.jp/keep',
            'hero_label' => 'Keep Label',
        ]);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.store.sns'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('name="instagram_url"', $html);
        $this->assertStringContainsString('SNSアカウント', $html);
        $this->assertStringContainsString('admin-service-heading', $html);
        $this->assertStringContainsString('admin-service-icon', $html);
        $this->assertStringContainsString('admin-service-name', $html);
        $this->assertStringContainsString('>Instagram</span>', $html);
        $this->assertStringContainsString('プロフィールURL', $html);
        $this->assertStringContainsString('公開サイトのInstagramアイコンから遷移するURLです。', $html);
        $this->assertStringNotContainsString('Instagram プロフィールURL', $html);
        $this->assertStringNotContainsString('name="hot_pepper_url"', $html);
        $this->assertStringNotContainsString('name="hero_label"', $html);

        $this->actingAs($this->admin())->put(route('admin.store.sns.update'), [
            'instagram_url' => 'https://instagram.com/new-salon',
        ])->assertRedirect(route('admin.store.sns'));

        $fresh = SalonSetting::current()->fresh();
        $this->assertSame('https://instagram.com/new-salon', $fresh->instagram_url);
        $this->assertSame('https://beauty.hotpepper.jp/keep', $fresh->hot_pepper_url);
        $this->assertSame('Keep Label', $fresh->hero_label);
    }

    public function test_reservations_page_shows_and_saves_hot_pepper_url(): void
    {
        SalonSetting::current()->update([
            'hot_pepper_url' => 'https://beauty.hotpepper.jp/old',
            'instagram_url' => 'https://instagram.com/keep',
            'hero_label' => 'Keep Label',
        ]);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.store.reservations'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('name="hot_pepper_url"', $html);
        $this->assertStringContainsString('予約サービス', $html);
        $this->assertStringContainsString('admin-service-heading', $html);
        $this->assertStringContainsString('admin-service-icon', $html);
        $this->assertStringContainsString('admin-service-name', $html);
        $this->assertStringContainsString('>Hot Pepper Beauty</span>', $html);
        $this->assertStringContainsString('予約URL', $html);
        $this->assertStringContainsString('公開サイトの「予約する」ボタンから遷移するURLです。', $html);
        $this->assertStringNotContainsString('Hot Pepper 予約URL', $html);
        $this->assertStringNotContainsString('name="instagram_url"', $html);
        $this->assertStringNotContainsString('name="hero_label"', $html);

        $this->actingAs($this->admin())->put(route('admin.store.reservations.update'), [
            'hot_pepper_url' => 'https://beauty.hotpepper.jp/new',
        ])->assertRedirect(route('admin.store.reservations'));

        $fresh = SalonSetting::current()->fresh();
        $this->assertSame('https://beauty.hotpepper.jp/new', $fresh->hot_pepper_url);
        $this->assertSame('https://instagram.com/keep', $fresh->instagram_url);
        $this->assertSame('Keep Label', $fresh->hero_label);
    }
}
