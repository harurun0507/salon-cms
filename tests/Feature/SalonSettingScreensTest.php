<?php

namespace Tests\Feature;

use App\Models\SalonSetting;
use App\Models\SocialLink;
use App\Models\TopPageSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
            ->assertSee('トップページの表示順')
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
            ->assertSee('トップページの表示順');

        $html = $response->getContent();

        $this->assertStringContainsString('ヒーロー設定', $html);
        $this->assertStringContainsString('コンセプト設定', $html);
        $this->assertStringContainsString('トップページ表示件数', $html);
        $this->assertStringContainsString('トップページの表示順', $html);
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

    public function test_sns_page_shows_and_saves_social_links(): void
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

        $this->assertStringContainsString('SNS・公式アカウント', $html);
        $this->assertStringContainsString('公開サイトに表示するSNSや公式アカウントのリンクを設定します。', $html);
        $this->assertStringContainsString('name="links[instagram][url]"', $html);
        $this->assertStringContainsString('admin-service-heading', $html);
        $this->assertStringContainsString('admin-service-icon', $html);
        $this->assertStringContainsString('admin-service-name', $html);
        $this->assertStringContainsString('>Instagram</span>', $html);
        $this->assertStringContainsString('プロフィールURL', $html);
        $this->assertStringContainsString('表示する', $html);
        $this->assertStringContainsString('表示しない', $html);
        $this->assertStringContainsString('data-sns-grid', $html);
        $this->assertStringNotContainsString('name="instagram_url"', $html);
        $this->assertStringNotContainsString('name="hot_pepper_url"', $html);
        $this->assertStringNotContainsString('name="hero_label"', $html);

        $payload = $this->snsPayload([
            'instagram' => [
                'url' => 'https://instagram.com/new-salon',
                'is_visible' => '1',
                'display_order' => 1,
            ],
        ]);

        $this->actingAs($this->admin())->put(route('admin.store.sns.update'), $payload)
            ->assertRedirect(route('admin.store.sns'));

        $instagram = SocialLink::query()->where('service_key', 'instagram')->first();
        $this->assertNotNull($instagram);
        $this->assertSame('https://instagram.com/new-salon', $instagram->url);
        $this->assertTrue($instagram->is_visible);

        $fresh = SalonSetting::current()->fresh();
        $this->assertSame('https://instagram.com/new-salon', $fresh->instagram_url);
        $this->assertSame('https://beauty.hotpepper.jp/keep', $fresh->hot_pepper_url);
        $this->assertSame('Keep Label', $fresh->hero_label);
    }

    private function snsPayload(array $overrides = []): array
    {
        $links = [];
        foreach (config('social_links.services') as $key => $meta) {
            $links[$key] = array_merge([
                'url' => null,
                'is_visible' => '0',
                'display_order' => (int) $meta['default_order'],
            ], $overrides[$key] ?? []);
        }

        return ['links' => $links];
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

    public function test_seo_page_shows_and_saves_fields(): void
    {
        SalonSetting::current()->update([
            'site_title' => 'Old Title',
            'meta_description' => 'Old description',
            'meta_keywords' => 'old,keywords',
            'og_title' => 'Old OG',
            'og_description' => 'Old OG desc',
            'twitter_card' => SalonSetting::TWITTER_CARD_SUMMARY,
            'noindex' => true,
            'instagram_url' => 'https://instagram.com/keep',
            'shop_name' => 'Keep Shop',
        ]);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.system.seo'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('サイト基本SEO', $html);
        $this->assertStringContainsString('ファビコン', $html);
        $this->assertStringContainsString('SNS（OGP）', $html);
        $this->assertStringContainsString('検索エンジン', $html);
        $this->assertStringContainsString('Google検索プレビュー', $html);
        $this->assertStringContainsString('SNSシェアプレビュー', $html);
        $this->assertStringContainsString('Google検索結果イメージ', $html);
        $this->assertStringContainsString('Facebook・LINE・X共有イメージ', $html);
        $this->assertStringContainsString('LINEやFacebookなどで共有された際の表示イメージです。', $html);
        $this->assertStringContainsString('data-seo-previews', $html);
        $this->assertStringContainsString('lg:grid-cols-2', $html);
        $this->assertStringContainsString('xl:grid-cols-2', $html);
        $this->assertLessThan(
            strpos($html, 'SNSシェアプレビュー'),
            strpos($html, 'Google検索プレビュー'),
            'Google preview should appear before SNS preview in the markup (stacked order on narrow screens).'
        );
        $this->assertStringContainsString('data-char-count="site_title"', $html);
        $this->assertStringContainsString('data-char-count="meta_description"', $html);
        $this->assertStringContainsString('data-char-count="og_title"', $html);
        $this->assertStringContainsString('data-char-count="og_description"', $html);
        $this->assertStringContainsString('data-google-title', $html);
        $this->assertStringContainsString('data-sns-title', $html);
        $this->assertStringContainsString('ここにOGP画像をドロップしてください', $html);
        $this->assertStringContainsString('ここにファビコンをドロップしてください', $html);
        $this->assertStringContainsString('name="site_title"', $html);
        $this->assertStringContainsString('name="meta_description"', $html);
        $this->assertStringContainsString('name="meta_keywords"', $html);
        $this->assertStringContainsString('name="favicon"', $html);
        $this->assertStringContainsString('name="og_title"', $html);
        $this->assertStringContainsString('name="og_description"', $html);
        $this->assertStringContainsString('name="og_image"', $html);
        $this->assertStringContainsString('name="twitter_card"', $html);
        $this->assertStringContainsString('value="summary_large_image"', $html);
        $this->assertStringContainsString('value="summary"', $html);
        $this->assertStringContainsString('大きい画像', $html);
        $this->assertStringContainsString('小さい画像', $html);
        $this->assertStringContainsString('admin-segmented', $html);
        $this->assertStringContainsString('X（Twitter）で共有された際のカード表示形式です。通常は「大きい画像」をおすすめします。', $html);
        $this->assertStringNotContainsString('<select name="twitter_card"', $html);
        $this->assertMatchesRegularExpression(
            '/value="summary"[^>]*checked|checked[^>]*value="summary"/',
            $html
        );
        $this->assertStringContainsString('seo-og-placeholder-main', $html);
        $this->assertStringContainsString('OGP画像未設定', $html);
        $this->assertStringContainsString('name="noindex"', $html);
        $this->assertStringContainsString('インデックスする', $html);
        $this->assertStringContainsString('インデックスしない', $html);
        $this->assertStringContainsString('この設定では、公開サイトがGoogleなどの検索結果に表示されない可能性があります。', $html);
        $this->assertStringContainsString('banner-dropzone', $html);
        $this->assertStringContainsString(url('/sitemap.xml'), $html);
        $this->assertStringContainsString(url('/robots.txt'), $html);
        $this->assertStringContainsString('公開後、このsitemap.xml URLをGoogle Search Consoleへ登録してください。', $html);
        $this->assertStringContainsString('data-copy-target="sitemap-url"', $html);
        $this->assertStringContainsString('data-copy-target="robots-txt-url"', $html);
        $this->assertStringContainsString('開く', $html);
        $this->assertStringContainsString('コピー', $html);
        $this->assertMatchesRegularExpression('/href="[^"]*sitemap\\.xml"[^>]*target="_blank"/', $html);
        $this->assertMatchesRegularExpression('/href="[^"]*robots\\.txt"[^>]*target="_blank"/', $html);
        $this->assertStringNotContainsString('name="instagram_url"', $html);
        $this->assertStringNotContainsString('name="shop_name"', $html);
        $this->assertStringNotContainsString('この機能は現在準備中です。', $html);

        $this->actingAs($this->admin())->put(route('admin.system.seo.update'), [
            'site_title' => 'Sun & Me SEO',
            'meta_description' => '新しい説明文です。',
            'meta_keywords' => '美容室,ヘアサロン',
            'og_title' => 'OG Title',
            'og_description' => 'OG Description',
            'twitter_card' => SalonSetting::TWITTER_CARD_SUMMARY_LARGE_IMAGE,
            'noindex' => '0',
        ])->assertRedirect(route('admin.system.seo'));

        $fresh = SalonSetting::current()->fresh();
        $this->assertSame('Sun & Me SEO', $fresh->site_title);
        $this->assertSame('新しい説明文です。', $fresh->meta_description);
        $this->assertSame('美容室,ヘアサロン', $fresh->meta_keywords);
        $this->assertSame('OG Title', $fresh->og_title);
        $this->assertSame('OG Description', $fresh->og_description);
        $this->assertSame(SalonSetting::TWITTER_CARD_SUMMARY_LARGE_IMAGE, $fresh->twitter_card);
        $this->assertFalse($fresh->noindex);
        $this->assertSame('https://instagram.com/keep', $fresh->instagram_url);
        $this->assertSame('Keep Shop', $fresh->shop_name);
    }

    public function test_seo_page_uploads_og_image(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())->put(route('admin.system.seo.update'), [
            'site_title' => 'With Image',
            'meta_description' => null,
            'meta_keywords' => null,
            'og_title' => null,
            'og_description' => null,
            'twitter_card' => SalonSetting::TWITTER_CARD_SUMMARY_LARGE_IMAGE,
            'noindex' => '0',
            'og_image' => UploadedFile::fake()->image('og.jpg', 1200, 630),
        ])->assertRedirect(route('admin.system.seo'));

        $setting = SalonSetting::current()->fresh();
        $this->assertNotNull($setting->og_image);
        $this->assertStringStartsWith('settings/og/', $setting->og_image);
        Storage::disk('public')->assertExists($setting->og_image);
    }

    public function test_seo_page_uploads_favicon(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())->put(route('admin.system.seo.update'), [
            'site_title' => 'With Favicon',
            'meta_description' => null,
            'meta_keywords' => null,
            'og_title' => null,
            'og_description' => null,
            'twitter_card' => SalonSetting::TWITTER_CARD_SUMMARY_LARGE_IMAGE,
            'noindex' => '0',
            'favicon' => UploadedFile::fake()->image('favicon.png', 64, 64),
        ])->assertRedirect(route('admin.system.seo'));

        $setting = SalonSetting::current()->fresh();
        $this->assertNotNull($setting->favicon_path);
        $this->assertStringStartsWith('settings/favicon/', $setting->favicon_path);
        Storage::disk('public')->assertExists($setting->favicon_path);
    }

    public function test_analytics_page_shows_and_saves_measurement_id(): void
    {
        SalonSetting::current()->update([
            'ga_measurement_id' => null,
            'shop_name' => 'Keep Shop',
        ]);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.system.analytics'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Google Analytics 4', $html);
        $this->assertStringContainsString('Google Analyticsを利用すると、公開サイトの閲覧数やアクセス状況をGoogle Analyticsの管理画面で確認できます。', $html);
        $this->assertStringContainsString('Google Analyticsで発行された「測定ID（G-から始まるID）」を入力してください。', $html);
        $this->assertStringContainsString('Google Analytics 測定ID', $html);
        $this->assertStringContainsString('placeholder="G-XXXXXXXXXX"', $html);
        $this->assertStringContainsString('状態', $html);
        $this->assertStringContainsString('未設定', $html);
        $this->assertStringContainsString('アクセス解析は現在無効です。', $html);
        $this->assertStringContainsString('測定IDを設定すると、公開サイトのアクセス解析を開始できます。', $html);
        $this->assertStringContainsString('測定IDは、Google Analyticsの「管理」→「データストリーム」→対象のWebサイトから確認できます。', $html);
        $this->assertStringContainsString('Google Analyticsを開く', $html);
        $this->assertStringContainsString('https://analytics.google.com/', $html);
        $this->assertStringContainsString('name="ga_measurement_id"', $html);
        $this->assertStringContainsString('analytics-form', $html);
        $this->assertSame(1, substr_count($html, 'Google Analyticsを開く'));
        $this->assertTrue(
            strpos($html, 'Google Analytics 測定ID') < strpos($html, 'Google Analyticsを開く')
            && strpos($html, 'Google Analyticsを開く') < strpos($html, 'name="ga_measurement_id"')
        );
        $this->assertStringNotContainsString('測定ID（Measurement ID）', $html);
        $this->assertStringNotContainsString('admin-service-heading', $html);
        $this->assertStringNotContainsString('測定IDが未入力の場合、アクセス解析は行われません。', $html);
        $this->assertStringNotContainsString('測定IDは「G-」から始まる文字列です。', $html);
        $this->assertStringNotContainsString('この機能は現在準備中です。', $html);
        $this->assertStringNotContainsString('公開サイトでアクセス解析が有効になります。', $html);

        $this->actingAs($this->admin())->put(route('admin.system.analytics.update'), [
            'ga_measurement_id' => 'g-abc123xyz',
        ])->assertRedirect(route('admin.system.analytics'));

        $fresh = SalonSetting::current()->fresh();
        $this->assertSame('G-ABC123XYZ', $fresh->ga_measurement_id);
        $this->assertSame('Keep Shop', $fresh->shop_name);

        $configuredHtml = $this->actingAs($this->admin())
            ->get(route('admin.system.analytics'))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('設定済み', $configuredHtml);
        $this->assertStringContainsString('公開サイトでアクセス解析が有効です。', $configuredHtml);
        $this->assertStringContainsString('data-configured="1"', $configuredHtml);

        $this->actingAs($this->admin())->put(route('admin.system.analytics.update'), [
            'ga_measurement_id' => '  G-abc-123  ',
        ])->assertRedirect(route('admin.system.analytics'));
        $this->assertSame('G-ABC-123', SalonSetting::current()->fresh()->ga_measurement_id);

        $this->actingAs($this->admin())->put(route('admin.system.analytics.update'), [
            'ga_measurement_id' => '',
        ])->assertRedirect(route('admin.system.analytics'));

        $this->assertNull(SalonSetting::current()->fresh()->ga_measurement_id);
    }

    public function test_analytics_rejects_invalid_measurement_id(): void
    {
        $this->actingAs($this->admin())
            ->from(route('admin.system.analytics'))
            ->put(route('admin.system.analytics.update'), [
                'ga_measurement_id' => 'UA-123456-1',
            ])
            ->assertRedirect(route('admin.system.analytics'))
            ->assertSessionHasErrors([
                'ga_measurement_id' => '「G-」から始まる測定IDを入力してください。',
            ]);

        $this->actingAs($this->admin())
            ->from(route('admin.system.analytics'))
            ->put(route('admin.system.analytics.update'), [
                'ga_measurement_id' => 'G-ABC_DEF',
            ])
            ->assertRedirect(route('admin.system.analytics'))
            ->assertSessionHasErrors('ga_measurement_id');
    }

    public function test_seo_noindex_outputs_robots_meta_on_public_pages(): void
    {
        SalonSetting::current()->update([
            'site_title' => 'Indexable Salon',
            'noindex' => false,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<meta name="robots" content="index, follow">', false);

        SalonSetting::current()->update(['noindex' => true]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_public_head_outputs_seo_tags_and_og_fallbacks(): void
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->image('share.jpg', 1200, 630)->store('settings/og', 'public');

        SalonSetting::current()->update([
            'shop_name' => 'Fallback Shop',
            'site_title' => 'SEO Site Title',
            'meta_description' => 'Meta description body',
            'meta_keywords' => 'cut,color',
            'og_title' => null,
            'og_description' => null,
            'og_image' => $path,
            'twitter_card' => SalonSetting::TWITTER_CARD_SUMMARY,
            'noindex' => false,
        ]);

        $html = $this->get(route('home'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('<title>Fallback Shop | SEO Site Title</title>', $html);
        $this->assertStringContainsString('<meta name="description" content="Meta description body">', $html);
        $this->assertStringContainsString('<meta name="keywords" content="cut,color">', $html);
        $this->assertStringContainsString('<meta property="og:title" content="SEO Site Title">', $html);
        $this->assertStringContainsString('<meta property="og:description" content="Meta description body">', $html);
        $this->assertStringContainsString('storage/'.$path, $html);
        $this->assertStringContainsString('<meta name="twitter:card" content="summary">', $html);
        $this->assertStringContainsString('<meta name="twitter:title" content="SEO Site Title">', $html);
        $this->assertStringContainsString('<meta name="twitter:description" content="Meta description body">', $html);

        $menuHtml = $this->get(route('menu'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('<title>メニュー・料金 | SEO Site Title</title>', $menuHtml);
    }
}
