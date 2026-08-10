<?php

namespace Tests\Feature;

use App\Models\Gallery;
use App\Models\GalleryImage;
use App\Models\News;
use App\Models\SalonSetting;
use App\Models\TopPageSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HomeNavigationAndNewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_header_nav_uses_section_anchors_including_news(): void
    {
        SalonSetting::current();
        TopPageSection::ensureDefaults();

        $html = $this->get(route('home'))->assertOk()->getContent();

        foreach (['concept', 'news', 'menu', 'staff', 'access'] as $id) {
            $this->assertStringContainsString('/#'.$id, $html);
        }
        $this->assertStringContainsString('/#gallery', $html);

        $this->assertMatchesRegularExpression(
            '/aria-label="メインメニュー"[^>]*>\s*'
            .'<a[^>]*>Concept<\/a>\s*'
            .'<a[^>]*>News<\/a>\s*'
            .'<a[^>]*>Gallery<\/a>\s*'
            .'<a[^>]*>Menu<\/a>\s*'
            .'<a[^>]*>Staff<\/a>\s*'
            .'<a[^>]*>Access<\/a>/s',
            $html
        );
    }

    public function test_header_gallery_nav_shows_when_section_on_even_without_published_galleries(): void
    {
        SalonSetting::current();
        TopPageSection::ensureDefaults();
        TopPageSection::query()->where('section_key', TopPageSection::KEY_GALLERY)->update([
            'is_visible' => true,
        ]);

        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringNotContainsString('id="gallery"', $html);
        $this->assertStringContainsString('>Gallery</a>', $html);
        $this->assertStringContainsString('<span>Gallery</span>', $html);
    }

    public function test_header_and_mobile_nav_hide_items_when_top_sections_are_off(): void
    {
        Storage::fake('public');
        SalonSetting::current();
        TopPageSection::ensureDefaults();

        $gallery = Gallery::query()->create([
            'title' => '公開ギャラリー',
            'caption' => null,
            'sort_order' => 1,
            'is_published' => true,
        ]);
        GalleryImage::query()->create([
            'gallery_id' => $gallery->id,
            'image_path' => UploadedFile::fake()->image('g.jpg')->store('galleries', 'public'),
            'display_order' => 1,
        ]);

        TopPageSection::query()->whereIn('section_key', [
            TopPageSection::KEY_NEWS,
            TopPageSection::KEY_BLOG,
            TopPageSection::KEY_GALLERY,
            TopPageSection::KEY_MENU,
            TopPageSection::KEY_STAFF,
            TopPageSection::KEY_ACCESS,
        ])->update(['is_visible' => false]);

        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('>Concept</a>', $html);
        $this->assertStringNotContainsString('>News</a>', $html);
        $this->assertStringNotContainsString('>Gallery</a>', $html);
        $this->assertStringNotContainsString('>Menu</a>', $html);
        $this->assertStringNotContainsString('>Staff</a>', $html);
        $this->assertStringNotContainsString('>Access</a>', $html);
        $this->assertStringNotContainsString('<span>News</span>', $html);
        $this->assertStringNotContainsString('<span>Gallery</span>', $html);
        $this->assertStringNotContainsString('<span>Menu</span>', $html);
        $this->assertStringNotContainsString('<span>Staff</span>', $html);
        $this->assertStringNotContainsString('<span>Access</span>', $html);
        $this->assertStringNotContainsString('id="gallery"', $html);
        $this->assertStringNotContainsString('id="menu"', $html);
        $this->assertStringNotContainsString('id="staff"', $html);
        $this->assertStringNotContainsString('id="access"', $html);
    }

    public function test_header_news_nav_stays_when_only_blog_section_is_on(): void
    {
        SalonSetting::current();
        TopPageSection::ensureDefaults();
        TopPageSection::query()->where('section_key', TopPageSection::KEY_NEWS)->update(['is_visible' => false]);
        TopPageSection::query()->where('section_key', TopPageSection::KEY_BLOG)->update(['is_visible' => true]);

        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('>News</a>', $html);
        $this->assertStringContainsString('/#blog', $html);
        $this->assertStringNotContainsString('href="'.url('/#news').'"', $html);
    }

    public function test_home_shows_up_to_configured_published_news_newest_first(): void
    {
        SalonSetting::current();

        News::query()->create([
            'title' => '古いお知らせ',
            'slug' => 'old-news',
            'body' => 'body',
            'is_published' => true,
            'published_at' => now()->subDays(10),
            'display_order' => 2,
        ]);
        News::query()->create([
            'title' => '新しいお知らせ',
            'slug' => 'new-news',
            'body' => 'body',
            'is_published' => true,
            'published_at' => now()->subDay(),
            'display_order' => 1,
        ]);
        News::query()->create([
            'title' => '非公開',
            'slug' => 'draft',
            'body' => 'body',
            'is_published' => false,
            'published_at' => now(),
            'display_order' => 99,
        ]);

        for ($i = 1; $i <= 5; $i++) {
            News::query()->create([
                'title' => "追加{$i}",
                'slug' => "extra-{$i}",
                'body' => 'body',
                'is_published' => true,
                'published_at' => now()->subDays($i + 1),
                'display_order' => $i + 2,
            ]);
        }

        $response = $this->get(route('home'));
        $response->assertOk();
        $response->assertSee('id="news"', false);
        $response->assertSee('新しいお知らせ', false);
        $response->assertDontSee('>非公開<', false);
        $response->assertSee('すべて見る →', false);
        $response->assertSee('btn-outline', false);
        $response->assertSee(route('news.show', 'new-news'), false);
        $response->assertSee(route('news.index'), false);

        $html = $response->getContent();
        $this->assertLessThan(strpos($html, '追加1'), strpos($html, '新しいお知らせ'));
        // Default top-page news display_count is 3.
        $this->assertSame(3, substr_count($html, 'tracking-widest text-salon-accent">NEWS</'));
    }

    public function test_home_news_section_hides_empty_list_when_no_published_news(): void
    {
        SalonSetting::current();

        $html = $this->get(route('home'))->assertOk()->getContent();
        $this->assertStringNotContainsString('tracking-widest text-salon-accent">NEWS</', $html);
        $this->assertStringNotContainsString(route('news.index'), $html);
        $this->assertStringNotContainsString('お知らせはありません。', $html);
        $this->assertStringNotContainsString('<ul class="divide-y', $html);
    }

    public function test_home_shows_news_row_when_published_in_japan_timezone(): void
    {
        SalonSetting::current();
        $this->assertSame('Asia/Tokyo', config('app.timezone'));

        News::query()->create([
            'title' => '定休日のお知らせ',
            'slug' => 'closed-day',
            'body' => '本文',
            'is_published' => true,
            'published_at' => now('Asia/Tokyo')->subMinute(),
            'display_order' => 1,
        ]);

        $html = $this->get(route('home'))->assertOk()->getContent();
        $this->assertStringContainsString('定休日のお知らせ', $html);
        $this->assertStringContainsString('tracking-widest text-salon-accent">NEWS</', $html);
        $this->assertStringContainsString(route('news.index'), $html);
        $this->assertStringContainsString('すべて見る →', $html);
    }
}
