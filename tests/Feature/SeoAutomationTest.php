<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\SalonSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SeoAutomationTest extends TestCase
{
    use RefreshDatabase;

    public function test_robots_txt_disallows_all_outside_production(): void
    {
        SalonSetting::current()->update(['noindex' => false]);

        $response = $this->get('/robots.txt');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertSame("User-agent: *\nDisallow: /\n", $response->getContent());
        $this->assertStringNotContainsString('Sitemap:', $response->getContent());
    }

    public function test_robots_txt_disallows_all_in_production_when_noindex(): void
    {
        $this->app['env'] = 'production';
        SalonSetting::current()->update(['noindex' => true]);

        $response = $this->get('/robots.txt');

        $response->assertOk();
        $this->assertSame("User-agent: *\nDisallow: /\n", $response->getContent());
        $this->assertStringNotContainsString('Sitemap:', $response->getContent());
    }

    public function test_robots_txt_allows_and_includes_sitemap_in_production(): void
    {
        $this->app['env'] = 'production';
        SalonSetting::current()->update(['noindex' => false]);

        $response = $this->get('/robots.txt');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $body = $response->getContent();
        $this->assertStringContainsString("User-agent: *\nAllow: /\n", $body);
        $this->assertStringContainsString('Sitemap: '.url('/sitemap.xml'), $body);
    }

    public function test_sitemap_includes_home_and_published_news_excludes_unpublished(): void
    {
        SalonSetting::current();

        News::query()->create([
            'title' => '公開お知らせ',
            'slug' => 'published-news',
            'body' => 'body',
            'is_published' => true,
            'published_at' => now()->subDay(),
            'display_order' => 1,
        ]);
        News::query()->create([
            'title' => '下書き',
            'slug' => 'draft-news',
            'body' => 'body',
            'is_published' => false,
            'published_at' => now()->subDay(),
            'display_order' => 2,
        ]);
        News::query()->create([
            'title' => '予約公開',
            'slug' => 'future-news',
            'body' => 'body',
            'is_published' => true,
            'published_at' => now()->addDays(3),
            'display_order' => 3,
        ]);

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $xml = $response->getContent();
        $this->assertStringContainsString('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', $xml);
        $this->assertStringContainsString('<loc>'.e(route('home')).'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.e(route('news.index')).'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.e(route('menu')).'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.e(route('gallery')).'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.e(route('staff')).'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.e(route('access')).'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.e(route('privacy')).'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.e(route('news.show', ['slug' => 'published-news'])).'</loc>', $xml);
        $this->assertStringNotContainsString('draft-news', $xml);
        $this->assertStringNotContainsString('future-news', $xml);
        $this->assertStringNotContainsString('/admin', $xml);
        $this->assertStringNotContainsString('/login', $xml);
    }

    public function test_sitemap_uses_database_slugs_not_invented_item_id_patterns(): void
    {
        SalonSetting::current();

        // Japanese titles yield uniqueSlug fallbacks: item, item-1, item-2
        News::query()->create([
            'title' => '一件目',
            'slug' => 'item',
            'body' => 'body',
            'is_published' => true,
            'published_at' => now()->subDays(2),
            'display_order' => 1,
        ]);
        News::query()->create([
            'title' => '非公開の二件目',
            'slug' => 'item-1',
            'body' => 'body',
            'is_published' => false,
            'published_at' => now()->subDay(),
            'display_order' => 2,
        ]);
        News::query()->create([
            'title' => '三件目',
            'slug' => 'item-2',
            'body' => 'body',
            'is_published' => true,
            'published_at' => now()->subHour(),
            'display_order' => 3,
        ]);

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertStringContainsString('<loc>'.e(route('news.show', ['slug' => 'item'])).'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.e(route('news.show', ['slug' => 'item-2'])).'</loc>', $xml);
        $this->assertStringNotContainsString('/news/item-1', $xml);
    }

    public function test_public_head_includes_canonical_and_default_favicon(): void
    {
        SalonSetting::current()->update([
            'favicon_path' => null,
            'ga_measurement_id' => null,
        ]);

        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('<link rel="canonical" href="'.e(url()->to(route('home'))).'">', $html);
        $this->assertStringContainsString('<link rel="icon" href="'.e(asset('favicon.ico')).'">', $html);
        $this->assertStringNotContainsString('googletagmanager.com/gtag/js', $html);
        $this->assertStringNotContainsString('gtag(', $html);

        $menuHtml = $this->get(route('menu'))->assertOk()->getContent();
        $this->assertStringContainsString('<link rel="canonical" href="'.e(url()->to(route('menu'))).'">', $menuHtml);
    }

    public function test_public_head_outputs_custom_favicon_and_ga4_when_set(): void
    {
        Storage::fake('public');
        $faviconPath = UploadedFile::fake()->image('icon.png', 32, 32)->store('settings/favicon', 'public');

        SalonSetting::current()->update([
            'favicon_path' => $faviconPath,
            'ga_measurement_id' => 'G-TEST12345',
        ]);

        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('<link rel="icon" href="'.e(asset('storage/'.$faviconPath)).'">', $html);
        $this->assertStringContainsString('https://www.googletagmanager.com/gtag/js?id=G-TEST12345', $html);
        $this->assertStringContainsString("gtag('config', \"G-TEST12345\")", $html);
        $this->assertStringContainsString('<link rel="canonical" href="', $html);
    }

    public function test_public_head_falls_back_logo_for_og_image_and_keeps_title_section(): void
    {
        Storage::fake('public');
        $logoPath = UploadedFile::fake()->image('logo.png', 200, 80)->store('settings/logo', 'public');

        SalonSetting::current()->update([
            'shop_name' => 'Logo Shop',
            'site_title' => 'SEO Title',
            'meta_description' => 'Desc',
            'og_image' => null,
            'logo_image' => $logoPath,
            'noindex' => false,
        ]);

        $homeHtml = $this->get(route('home'))->assertOk()->getContent();
        $this->assertStringContainsString('<title>Logo Shop | SEO Title</title>', $homeHtml);
        $this->assertStringContainsString('storage/'.$logoPath, $homeHtml);
        $this->assertStringContainsString('<meta property="og:image" content="', $homeHtml);

        $menuHtml = $this->get(route('menu'))->assertOk()->getContent();
        $this->assertStringContainsString('<title>メニュー・料金 | SEO Title</title>', $menuHtml);
    }
}
