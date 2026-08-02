<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Gallery;
use App\Models\HeroImage;
use App\Models\Menu;
use App\Models\MenuCategory;
use App\Models\News;
use App\Models\SalonSetting;
use App\Models\StaffMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $overrides = []): User
    {
        return User::factory()->admin()->create($overrides);
    }

    private function editor(array $overrides = []): User
    {
        return User::factory()->editor()->create($overrides);
    }

    public function test_dashboard_shows_site_status_count_cards_and_quick_actions(): void
    {
        $setting = SalonSetting::current();
        $setting->update([
            'noindex' => false,
            'ga_measurement_id' => 'G-TEST123',
            'hot_pepper_url' => 'https://beauty.hotpepper.jp/example',
            'instagram_url' => 'https://instagram.com/example',
        ]);

        News::query()->create([
            'title' => '公開お知らせ',
            'slug' => 'published-news',
            'body' => '本文',
            'is_published' => true,
            'published_at' => now()->subDay(),
            'display_order' => 1,
        ]);
        News::query()->create([
            'title' => '非公開お知らせ',
            'slug' => 'draft-news',
            'body' => '本文',
            'is_published' => false,
            'published_at' => null,
            'display_order' => 2,
        ]);

        Gallery::query()->create([
            'image_path' => 'galleries/a.jpg',
            'caption' => '公開ギャラリー',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        Gallery::query()->create([
            'image_path' => 'galleries/b.jpg',
            'caption' => '非公開ギャラリー',
            'sort_order' => 2,
            'is_published' => false,
        ]);

        $category = MenuCategory::query()->create(['name' => 'カット', 'sort_order' => 1]);
        Menu::query()->create([
            'menu_category_id' => $category->id,
            'name' => 'カットA',
            'price' => '¥5,000',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        Menu::query()->create([
            'menu_category_id' => $category->id,
            'name' => 'カットB',
            'price' => '¥6,000',
            'sort_order' => 2,
            'is_published' => false,
        ]);

        StaffMember::query()->create([
            'name' => '公開スタッフ',
            'photo_path' => 'staff/a.jpg',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        StaffMember::query()->create([
            'name' => '非公開スタッフ',
            'photo_path' => 'staff/b.jpg',
            'sort_order' => 2,
            'is_published' => false,
        ]);

        HeroImage::query()->create([
            'salon_setting_id' => $setting->id,
            'image_path' => 'hero/a.jpg',
            'alt_text' => 'ヒーロー',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('サイトの状態', $html);
        $this->assertStringContainsString(route('home'), $html);
        $this->assertStringContainsString('表示可能', $html);
        $this->assertStringContainsString('インデックスする', $html);
        $this->assertStringContainsString('公開サイトを見る', $html);
        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringContainsString('dashboard-status-chip is-set is-link', $html);
        $this->assertStringContainsString('href="'.route('admin.system.seo').'"', $html);
        $this->assertStringContainsString('href="'.route('admin.system.analytics').'"', $html);
        $this->assertStringContainsString('href="'.route('admin.store.reservations').'"', $html);
        $this->assertStringContainsString('href="'.route('admin.store.sns').'"', $html);

        $this->assertMatchesRegularExpression('/アクセス解析[\s\S]*設定済み/u', $html);
        $this->assertMatchesRegularExpression('/予約設定[\s\S]*設定済み/u', $html);
        $this->assertMatchesRegularExpression('/SNS[\s\S]*設定済み/u', $html);

        $this->assertStringContainsString('公開 1', $html);
        $this->assertStringContainsString('非公開 1', $html);
        $this->assertStringContainsString('dashboard-count-card', $html);
        $this->assertStringContainsString(route('admin.news.index'), $html);
        $this->assertStringContainsString(route('admin.galleries.index'), $html);
        $this->assertStringContainsString(route('admin.menus.index'), $html);
        $this->assertStringContainsString(route('admin.staff.index'), $html);

        $this->assertStringContainsString('最近の更新', $html);
        $this->assertStringContainsString('クイックアクション', $html);
        $this->assertStringContainsString('お知らせ追加', $html);
        $this->assertStringContainsString('ギャラリー追加', $html);
        $this->assertStringContainsString('バナー追加', $html);
        $this->assertStringContainsString('スタッフ追加', $html);
        $this->assertStringContainsString(route('admin.galleries.create'), $html);
        $this->assertStringContainsString(route('admin.home.banners'), $html);
    }

    public function test_dashboard_shows_attention_items_and_clears_when_resolved(): void
    {
        $setting = SalonSetting::current();
        $setting->update([
            'ga_measurement_id' => null,
            'og_image' => null,
            'favicon_path' => null,
            'hot_pepper_url' => null,
            'instagram_url' => null,
        ]);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('確認が必要な項目', $html);
        $this->assertStringContainsString('アクセス解析（GA4）の測定IDが未設定です', $html);
        $this->assertStringContainsString('OGP画像が未設定です', $html);
        $this->assertStringContainsString('ファビコンが未設定です', $html);
        $this->assertStringContainsString('予約URLが未設定です', $html);
        $this->assertStringContainsString('SNSリンクが未設定です', $html);
        $this->assertStringContainsString('公開中のメインビジュアルがありません', $html);
        $this->assertStringContainsString('公開中のスタッフがいません', $html);
        $this->assertStringContainsString('公開中のメニューがありません', $html);
        $this->assertStringContainsString(route('admin.system.analytics'), $html);
        $this->assertStringContainsString(route('admin.system.seo'), $html);
        $this->assertStringContainsString(route('admin.store.reservations'), $html);
        $this->assertStringContainsString(route('admin.store.sns'), $html);
        $this->assertStringContainsString(route('admin.home.hero'), $html);
        $this->assertStringContainsString('dashboard-attention-item', $html);
        $this->assertStringNotContainsString('設定する', $html);
        $this->assertStringNotContainsString('現在、確認が必要な項目はありません。', $html);

        $setting->update([
            'ga_measurement_id' => 'G-DONE',
            'og_image' => 'settings/og/done.jpg',
            'favicon_path' => 'settings/favicon/done.png',
            'hot_pepper_url' => 'https://beauty.hotpepper.jp/done',
            'instagram_url' => 'https://instagram.com/done',
        ]);

        HeroImage::query()->create([
            'salon_setting_id' => $setting->id,
            'image_path' => 'hero/done.jpg',
            'alt_text' => '完了ヒーロー',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        StaffMember::query()->create([
            'name' => '完了スタッフ',
            'photo_path' => 'staff/done.jpg',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $category = MenuCategory::query()->create(['name' => '完了カテゴリ', 'sort_order' => 1]);
        Menu::query()->create([
            'menu_category_id' => $category->id,
            'name' => '完了メニュー',
            'price' => '¥3,000',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $resolved = $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('現在、確認が必要な項目はありません。', $resolved);
        $this->assertStringNotContainsString('アクセス解析（GA4）の測定IDが未設定です', $resolved);
        $this->assertStringNotContainsString('OGP画像が未設定です', $resolved);
        $this->assertStringNotContainsString('ファビコンが未設定です', $resolved);
        $this->assertStringNotContainsString('予約URLが未設定です', $resolved);
        $this->assertStringNotContainsString('SNSリンクが未設定です', $resolved);
        $this->assertStringNotContainsString('公開中のメインビジュアルがありません', $resolved);
        $this->assertStringNotContainsString('公開中のスタッフがいません', $resolved);
        $this->assertStringNotContainsString('公開中のメニューがありません', $resolved);
    }

    public function test_editor_cannot_see_admin_only_attention_links(): void
    {
        SalonSetting::current()->update([
            'ga_measurement_id' => null,
            'og_image' => null,
            'favicon_path' => null,
            'hot_pepper_url' => null,
            'instagram_url' => null,
        ]);

        $html = $this->actingAs($this->editor())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('アクセス解析（GA4）の測定IDが未設定です', $html);
        $this->assertStringNotContainsString('OGP画像が未設定です', $html);
        $this->assertStringNotContainsString('ファビコンが未設定です', $html);
        $this->assertStringNotContainsString(route('admin.system.analytics'), $html);
        $this->assertStringNotContainsString(route('admin.system.seo'), $html);
        $this->assertStringNotContainsString(route('admin.system.users'), $html);
        $this->assertStringNotContainsString(route('admin.system.design'), $html);
        $this->assertStringNotContainsString('href="'.route('admin.system.analytics').'"', $html);
        $this->assertStringNotContainsString('href="'.route('admin.system.seo').'"', $html);

        $this->assertStringContainsString('検索エンジン', $html);
        $this->assertStringContainsString('アクセス解析', $html);
        $this->assertStringContainsString('予約URLが未設定です', $html);
        $this->assertStringContainsString('SNSリンクが未設定です', $html);
        $this->assertStringContainsString(route('admin.store.reservations'), $html);
        $this->assertStringContainsString(route('admin.store.sns'), $html);
        $this->assertStringContainsString('dashboard-attention-item', $html);
        $this->assertStringNotContainsString('設定する', $html);
        $this->assertStringContainsString('公開中のメインビジュアルがありません', $html);
        $this->assertStringContainsString('公開中のスタッフがいません', $html);
        $this->assertStringContainsString('公開中のメニューがありません', $html);
    }

    public function test_recent_updates_merge_sources_and_sort_by_updated_at(): void
    {
        $setting = SalonSetting::current();
        $setting->update([
            'ga_measurement_id' => 'G-OK',
            'og_image' => 'settings/og/ok.jpg',
            'favicon_path' => 'settings/favicon/ok.png',
            'hot_pepper_url' => 'https://example.com/reserve',
            'instagram_url' => 'https://instagram.com/ok',
        ]);

        $t1 = now()->subDays(5);
        $t2 = now()->subDays(4);
        $t3 = now()->subDays(3);
        $t4 = now()->subDays(2);
        $t5 = now()->subDay();
        $t6 = now()->subHour();

        $oldNews = News::query()->create([
            'title' => '古いお知らせ',
            'slug' => 'old-news',
            'body' => '本文',
            'is_published' => true,
            'published_at' => $t1,
            'display_order' => 1,
        ]);
        News::query()->whereKey($oldNews->id)->update(['updated_at' => $t1]);

        $gallery = Gallery::query()->create([
            'image_path' => 'galleries/recent.jpg',
            'caption' => '最新ギャラリー',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        Gallery::query()->whereKey($gallery->id)->update(['updated_at' => $t6]);

        $category = MenuCategory::query()->create(['name' => 'カラー', 'sort_order' => 1]);
        $menu = Menu::query()->create([
            'menu_category_id' => $category->id,
            'name' => '中間メニュー',
            'price' => '¥8,000',
            'sort_order' => 1,
            'is_published' => false,
        ]);
        Menu::query()->whereKey($menu->id)->update(['updated_at' => $t5]);

        $staff = StaffMember::query()->create([
            'name' => 'スタッフ更新',
            'photo_path' => 'staff/r.jpg',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        StaffMember::query()->whereKey($staff->id)->update(['updated_at' => $t4]);

        $banner = Banner::query()->create([
            'title' => 'バナー更新',
            'image_path' => 'banners/r.jpg',
            'display_location' => Banner::LOCATION_TOP,
            'display_order' => 1,
            'is_published' => true,
        ]);
        Banner::query()->whereKey($banner->id)->update(['updated_at' => $t3]);

        $hero = HeroImage::query()->create([
            'salon_setting_id' => $setting->id,
            'image_path' => 'hero/r.jpg',
            'alt_text' => 'ヒーロー更新',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        HeroImage::query()->whereKey($hero->id)->update(['updated_at' => $t2]);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('最新ギャラリー', $html);
        $this->assertStringContainsString('中間メニュー', $html);
        $this->assertStringContainsString('スタッフ更新', $html);
        $this->assertStringContainsString('バナー更新', $html);
        $this->assertStringContainsString('ヒーロー更新', $html);
        $this->assertStringContainsString(route('admin.galleries.edit', $gallery), $html);
        $this->assertStringNotContainsString('古いお知らせ', $html);

        $galleryPos = strpos($html, '最新ギャラリー');
        $menuPos = strpos($html, '中間メニュー');
        $this->assertNotFalse($galleryPos);
        $this->assertNotFalse($menuPos);
        $this->assertLessThan($menuPos, $galleryPos);
    }

    public function test_news_future_published_at_counts_as_unpublished(): void
    {
        SalonSetting::current()->update([
            'ga_measurement_id' => 'G-OK',
            'og_image' => 'settings/og/ok.jpg',
            'favicon_path' => 'settings/favicon/ok.png',
            'hot_pepper_url' => 'https://example.com/reserve',
            'instagram_url' => 'https://instagram.com/ok',
        ]);

        News::query()->create([
            'title' => '予約公開お知らせ',
            'slug' => 'scheduled-news',
            'body' => '本文',
            'is_published' => true,
            'published_at' => now()->addDay(),
            'display_order' => 1,
        ]);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/お知らせ[\s\S]*?dashboard-count-number">1</u', $html);
        $this->assertMatchesRegularExpression('/お知らせ[\s\S]*?dashboard-count-unit">件</u', $html);
        $this->assertStringContainsString('公開 0', $html);
        $this->assertStringContainsString('非公開 1', $html);
        $this->assertStringContainsString('非公開', $html);
    }

    public function test_indexing_disabled_shows_caution_badge(): void
    {
        SalonSetting::current()->update(['noindex' => true]);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('インデックスしない', $html);
        $this->assertStringContainsString('dashboard-status-badge is-caution', $html);
    }

    public function test_recent_updates_show_distinguishing_titles_or_numbered_fallbacks(): void
    {
        $setting = SalonSetting::current();
        $setting->update([
            'ga_measurement_id' => 'G-OK',
            'og_image' => 'settings/og/ok.jpg',
            'favicon_path' => 'settings/favicon/ok.png',
            'hot_pepper_url' => 'https://example.com/reserve',
            'instagram_url' => 'https://instagram.com/ok',
        ]);

        $t1 = now()->subMinutes(1);
        $t2 = now()->subMinutes(2);
        $t3 = now()->subMinutes(3);
        $t4 = now()->subMinutes(4);
        $t5 = now()->subMinutes(5);

        $heroLater = HeroImage::query()->create([
            'salon_setting_id' => $setting->id,
            'image_path' => 'hero/later.jpg',
            'alt_text' => null,
            'sort_order' => 2,
            'is_published' => true,
        ]);
        HeroImage::query()->whereKey($heroLater->id)->update(['updated_at' => $t1]);

        $heroEarlier = HeroImage::query()->create([
            'salon_setting_id' => $setting->id,
            'image_path' => 'hero/earlier.jpg',
            'alt_text' => '',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        HeroImage::query()->whereKey($heroEarlier->id)->update(['updated_at' => $t2]);

        $bannerEmpty = Banner::query()->create([
            'title' => '  ',
            'image_path' => 'banners/empty.jpg',
            'display_location' => Banner::LOCATION_TOP,
            'display_order' => 1,
            'is_published' => true,
        ]);
        Banner::query()->whereKey($bannerEmpty->id)->update(['updated_at' => $t3]);

        $galleryEmpty = Gallery::query()->create([
            'image_path' => 'galleries/empty.jpg',
            'caption' => null,
            'sort_order' => 1,
            'is_published' => true,
        ]);
        Gallery::query()->whereKey($galleryEmpty->id)->update(['updated_at' => $t4]);

        $bannerNamed = Banner::query()->create([
            'title' => '春キャンペーン',
            'image_path' => 'banners/named.jpg',
            'display_location' => Banner::LOCATION_TOP,
            'display_order' => 2,
            'is_published' => true,
        ]);
        Banner::query()->whereKey($bannerNamed->id)->update(['updated_at' => $t5]);

        // Extra hero keeps sort positions stable but stays out of the recent top-5.
        $heroNamed = HeroImage::query()->create([
            'salon_setting_id' => $setting->id,
            'image_path' => 'hero/named.jpg',
            'alt_text' => '正面エントランス',
            'sort_order' => 3,
            'is_published' => true,
        ]);
        HeroImage::query()->whereKey($heroNamed->id)->update(['updated_at' => now()->subDay()]);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->getContent();

        // Numbered fallbacks use admin display order (sort_order / display_order), not update order.
        $this->assertStringContainsString('メインビジュアル2', $html); // sort_order 2, updated most recently
        $this->assertStringContainsString('メインビジュアル1', $html); // sort_order 1
        $this->assertStringContainsString('バナー1', $html);
        $this->assertStringContainsString('ギャラリー画像1', $html);
        $this->assertStringContainsString('春キャンペーン', $html);

        $this->assertMatchesRegularExpression(
            '/dashboard-recent-title[^>]*>メインビジュアル2</u',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/dashboard-recent-title[^>]*>バナー1</u',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/dashboard-recent-title[^>]*>ギャラリー画像1</u',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/dashboard-recent-title[^>]*>春キャンペーン</u',
            $html
        );
    }
}
