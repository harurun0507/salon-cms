<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\DesignSetting;
use App\Models\Gallery;
use App\Models\GalleryImage;
use App\Models\Menu;
use App\Models\MenuCategory;
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

    public function test_public_section_flow_matches_header_and_places_gallery_before_menu(): void
    {
        $this->assertSame(
            ['banner', 'news', 'blog', 'gallery', 'menu', 'staff', 'access'],
            TopPageSection::publicConfigurableKeysInOrder()
        );

        $this->assertSame(
            ['hero-slider', 'concept', 'banners', 'news', 'blog', 'gallery', 'menu', 'staff', 'access'],
            array_column(TopPageSection::publicScrollSectionMeta(), 'id')
        );

        $this->assertSame(
            ['Concept', 'News', 'Gallery', 'Menu', 'Staff', 'Access'],
            array_column(TopPageSection::publicHeaderNavItems([
                TopPageSection::KEY_NEWS => true,
                TopPageSection::KEY_BLOG => true,
                TopPageSection::KEY_GALLERY => true,
                TopPageSection::KEY_MENU => true,
                TopPageSection::KEY_STAFF => true,
                TopPageSection::KEY_ACCESS => true,
            ]), 'label')
        );
    }

    public function test_vertical_indicator_home_uses_public_nav_order_and_shared_section_meta(): void
    {
        Storage::fake('public');
        SalonSetting::current();
        TopPageSection::ensureDefaults();

        // Admin order intentionally reversed vs header (menu before gallery).
        TopPageSection::query()->where('section_key', TopPageSection::KEY_MENU)->update(['display_order' => 3]);
        TopPageSection::query()->where('section_key', TopPageSection::KEY_GALLERY)->update(['display_order' => 4]);

        DesignSetting::current()->update([
            'scroll_display_type' => DesignSetting::SCROLL_VERTICAL_INDICATOR,
        ]);

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

        $html = $this->get(route('home'))->assertOk()->getContent();

        $galleryPos = strpos($html, 'id="gallery"');
        $menuPos = strpos($html, 'id="menu"');
        $this->assertNotFalse($galleryPos);
        $this->assertNotFalse($menuPos);
        $this->assertLessThan($menuPos, $galleryPos);

        $this->assertMatchesRegularExpression(
            '/const SECTION_META = .+?"id"\s*:\s*"gallery".+?"id"\s*:\s*"menu"/s',
            $html
        );

        $this->assertStringContainsString('home-vi-gallery__footer', $html);
        $this->assertMatchesRegularExpression(
            '/home-vi-gallery__footer[\s\S]*?class="btn-outline"[^>]*>\s*すべて見る →/u',
            $html
        );
        $this->assertStringNotContainsString('home-vi-more-link--on-dark', $html);
    }

    public function test_vertical_indicator_news_blog_uses_asymmetric_split_layout(): void
    {
        SalonSetting::current();
        TopPageSection::ensureDefaults();
        DesignSetting::current()->update([
            'scroll_display_type' => DesignSetting::SCROLL_VERTICAL_INDICATOR,
        ]);

        News::query()->create([
            'title' => 'VIお知らせ',
            'slug' => 'vi-news',
            'body' => '本文',
            'is_published' => true,
            'published_at' => now()->subHour(),
            'display_order' => 1,
        ]);

        Blog::query()->create([
            'title' => 'VIブログ',
            'slug' => 'vi-blog',
            'body' => '本文',
            'is_published' => true,
            'published_at' => now()->subHour(),
            'display_order' => 1,
        ]);
        Blog::query()->create([
            'title' => 'VIブログ2件目は非表示',
            'slug' => 'vi-blog-2',
            'body' => '本文',
            'is_published' => true,
            'published_at' => now()->subDays(2),
            'display_order' => 2,
        ]);

        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('home-vi-news--split', $html);
        $this->assertStringContainsString('home-vi-news__news', $html);
        $this->assertStringContainsString('home-vi-news__blog', $html);
        $this->assertStringContainsString('home-vi-news__feature', $html);
        $this->assertStringContainsString('home-vi-news__visual', $html);
        $this->assertStringContainsString('home-vi-news__more-link', $html);
        $this->assertStringContainsString('VIお知らせ', $html);
        $this->assertStringContainsString('VIブログ', $html);
        $this->assertStringNotContainsString('VIブログ2件目は非表示', $html);
        $this->assertStringContainsString(route('news.show', 'vi-news', absolute: false), $html);
        $this->assertStringContainsString(route('blog.show', 'vi-blog', absolute: false), $html);
        // Card chrome from the list-style eyecatch should not appear in VI feature.
        $this->assertStringNotContainsString('blog-eyecatch-tab', $html);
        $this->assertStringNotContainsString('home-blog-card-more', $html);
        // Colored-scrollbar stacked layout classes should not appear in VI mode.
        $this->assertStringNotContainsString('home-blog-after-news', $html);
    }

    public function test_vertical_indicator_menu_uses_modal_triggers_instead_of_page_only(): void
    {
        SalonSetting::current();
        TopPageSection::ensureDefaults();
        DesignSetting::current()->update([
            'scroll_display_type' => DesignSetting::SCROLL_VERTICAL_INDICATOR,
        ]);

        $set = MenuCategory::query()->create([
            'name' => '組み合わせ',
            'sort_order' => 1,
            'allow_multiple_selection' => true,
        ]);
        $cut = MenuCategory::query()->create([
            'name' => 'カット',
            'sort_order' => 2,
            'allow_multiple_selection' => false,
        ]);

        $setMenu = Menu::query()->create([
            'name' => 'カット＋カラー',
            'price' => '¥12,000',
            'description' => 'セット説明',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $setMenu->categories()->attach([
            $set->id => ['sort_order' => 1],
            $cut->id => ['sort_order' => 2],
        ]);

        $cutMenu = Menu::query()->create([
            'name' => 'カット単品',
            'price' => '¥5,000',
            'description' => 'カット説明',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $cutMenu->categories()->attach($cut->id, ['sort_order' => 1]);

        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('home-vi-menu', $html);
        $this->assertStringContainsString('data-menu-modal', $html);
        $this->assertStringContainsString('data-menu-modal-trigger', $html);
        $this->assertStringContainsString('data-menu-category-id="'.$set->id.'"', $html);
        $this->assertStringContainsString('data-menu-category-id="'.$cut->id.'"', $html);
        $this->assertStringNotContainsString('data-menu-view="all"', $html);
        $this->assertMatchesRegularExpression(
            '/href="'.preg_quote(route('menu'), '/').'"[^>]*>\s*すべて見る →/u',
            $html
        );
        $this->assertStringContainsString('カット＋カラー', $html);
        $this->assertStringContainsString('セット説明', $html);
        $this->assertStringContainsString('カット説明', $html);
        // Set block itself is the clickable trigger (not only the arrow).
        $this->assertMatchesRegularExpression(
            '/class="home-vi-menu__set"[^>]*data-menu-modal-trigger/s',
            $html
        );
        // Category tiles keep modal triggers; "すべて見る" navigates to /menu.
        $this->assertDoesNotMatchRegularExpression(
            '/data-menu-modal-trigger[^>]*>\s*すべて見る/u',
            $html
        );
    }

    public function test_vertical_indicator_news_modal_aggregates_business_calendar_by_month(): void
    {
        \Illuminate\Support\Carbon::setTestNow('2026-08-25 12:00:00');

        SalonSetting::current();
        TopPageSection::ensureDefaults();
        DesignSetting::current()->update([
            'scroll_display_type' => DesignSetting::SCROLL_VERTICAL_INDICATOR,
            'news_detail_display' => DesignSetting::DETAIL_DISPLAY_MODAL,
        ]);
        TopPageSection::query()->where('section_key', TopPageSection::KEY_NEWS)->update([
            'display_count' => 10,
        ]);

        $salon = SalonSetting::current();
        $salon->closedWeekdays()->delete();
        $salon->closedNthWeekdays()->delete();
        $salon->closedWeekdays()->create(['weekday' => 1]); // Monday

        News::query()->create([
            'title' => '8月の定休日',
            'slug' => 'aug-holiday',
            'body' => '',
            'category' => News::CATEGORY_HOLIDAY,
            'is_published' => true,
            'published_at' => '2026-08-01 10:00:00',
            'display_order' => 1,
        ]);

        $temporary = News::query()->create([
            'title' => '臨時休業のお知らせ',
            'slug' => 'aug-temporary',
            'body' => '',
            'category' => News::CATEGORY_TEMPORARY_CLOSURE,
            'is_published' => true,
            'published_at' => '2026-08-10 10:00:00',
            'display_order' => 2,
        ]);
        $temporary->closedDates()->create(['closed_date' => '2026-08-15']);

        News::query()->create([
            'title' => '営業時間変更のお知らせ',
            'slug' => 'aug-hours',
            'body' => '',
            'category' => News::CATEGORY_HOURS,
            'hours_change_date' => '2026-08-20',
            'hours_start_time' => '10:00:00',
            'hours_end_time' => '17:00:00',
            'is_published' => true,
            'published_at' => '2026-08-10 10:00:00',
            'display_order' => 3,
        ]);

        News::query()->create([
            'title' => '新メニューのお知らせ',
            'slug' => 'aug-new-menu',
            'body' => '新メニューを追加しました。',
            'category' => News::CATEGORY_NEW_MENU,
            'is_published' => true,
            'published_at' => '2026-08-05 10:00:00',
            'display_order' => 4,
        ]);

        $payload = News::buildBusinessCalendarPayload(2026, 8);
        $this->assertSame('2026年8月 営業カレンダー', $payload['title']);
        $this->assertContains('holiday', $payload['days']['2026-08-03'] ?? []);
        $this->assertContains('temporary', $payload['days']['2026-08-15'] ?? []);
        $this->assertContains('hours', $payload['days']['2026-08-20'] ?? []);
        $this->assertTrue(collect($payload['notes'])->contains(
            fn (array $note) => ($note['label'] ?? '') === '8月15日'
                && ($note['detail'] ?? '') === '臨時休業'
                && (int) ($note['newsId'] ?? 0) === (int) $temporary->id
        ));
        $this->assertTrue(collect($payload['notes'])->contains(
            fn (array $note) => ($note['label'] ?? '') === '8月20日'
                && ($note['detail'] ?? '') === '営業時間変更　10:00〜17:00'
                && ($note['newsId'] ?? null) !== null
        ));
        $this->assertTrue(collect($payload['notes'])->contains(
            fn (array $note) => ($note['categoryKey'] ?? '') === News::CATEGORY_HOLIDAY
                && array_key_exists('newsId', $note)
                && $note['newsId'] === null
        ));

        $html = $this->get(route('home'))->assertOk()->getContent();
        $this->assertStringContainsString('data-news-modal-calendar-legend', $html);
        $this->assertStringContainsString('data-news-modal-calendar-notes', $html);
        $this->assertStringContainsString('2026年8月 営業カレンダー', $html);
        $this->assertStringContainsString('resolveCalendarFocus', $html);
        $this->assertStringContainsString('"hoursChangeDate":"2026-08-20"', $html);
        $this->assertStringContainsString('is-selected', $html);

        $this->assertMatchesRegularExpression(
            '/data-news-modal-data[^>]*>\s*\{/s',
            $html
        );
        $this->assertStringContainsString('"aggregateBusinessCalendar":true', $html);
        $this->assertStringContainsString('"businessCalendarKey":"2026-08"', $html);
        $this->assertStringContainsString('"slug":"aug-new-menu"', $html);
        $this->assertStringContainsString('"slug":"aug-new-menu","url":', $html);
        $this->assertDoesNotMatchRegularExpression(
            '/"slug":"aug-new-menu"[^}]*"businessCalendarKey":"2026-08"/s',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/"slug":"aug-new-menu"[^}]*"businessCalendarKey":null/s',
            $html
        );

        \Illuminate\Support\Carbon::setTestNow();
    }

    public function test_colored_scrollbar_home_keeps_classic_menu_links_without_modal(): void
    {
        SalonSetting::current();
        TopPageSection::ensureDefaults();
        DesignSetting::current()->update([
            'scroll_display_type' => DesignSetting::SCROLL_COLORED_SCROLLBAR,
        ]);

        $cut = MenuCategory::query()->create([
            'name' => 'カット',
            'sort_order' => 1,
            'allow_multiple_selection' => false,
        ]);
        $cutMenu = Menu::query()->create([
            'name' => 'カット単品',
            'price' => '¥5,000',
            'description' => null,
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $cutMenu->categories()->attach($cut->id, ['sort_order' => 1]);

        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringNotContainsString('data-menu-modal', $html);
        $this->assertStringNotContainsString('data-menu-modal-trigger', $html);
        $this->assertStringContainsString(route('menu', absolute: false), $html);
    }

    public function test_colored_scrollbar_home_keeps_admin_display_order(): void
    {
        Storage::fake('public');
        SalonSetting::current();
        TopPageSection::ensureDefaults();

        TopPageSection::query()->where('section_key', TopPageSection::KEY_MENU)->update(['display_order' => 3]);
        TopPageSection::query()->where('section_key', TopPageSection::KEY_GALLERY)->update(['display_order' => 4]);

        DesignSetting::current()->update([
            'scroll_display_type' => DesignSetting::SCROLL_COLORED_SCROLLBAR,
        ]);

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

        $html = $this->get(route('home'))->assertOk()->getContent();

        $galleryPos = strpos($html, 'id="gallery"');
        $menuPos = strpos($html, 'id="menu"');
        $this->assertNotFalse($galleryPos);
        $this->assertNotFalse($menuPos);
        $this->assertLessThan($galleryPos, $menuPos);
    }
}
