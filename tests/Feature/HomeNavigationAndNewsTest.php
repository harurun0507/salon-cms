<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\SalonSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeNavigationAndNewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_header_nav_uses_anchors_except_news_goes_to_index(): void
    {
        SalonSetting::current();

        $html = $this->get(route('home'))->assertOk()->getContent();

        foreach (['concept', 'menu', 'gallery', 'staff', 'access'] as $id) {
            $this->assertStringContainsString('/#'.$id, $html);
        }

        $this->assertStringNotContainsString('/#news"', $html);
        $this->assertStringContainsString(route('news.index'), $html);
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
        $response->assertSee('一覧を見る →', false);
        $response->assertSee('btn-outline', false);
        $response->assertSee(route('news.show', 'new-news'), false);

        $html = $response->getContent();
        $this->assertLessThan(strpos($html, '追加1'), strpos($html, '新しいお知らせ'));
        // Default top-page news display_count is 3.
        $this->assertSame(3, substr_count($html, 'font-medium leading-relaxed'));
    }

    public function test_home_news_section_hides_empty_list_when_no_published_news(): void
    {
        SalonSetting::current();

        $html = $this->get(route('home'))->assertOk()->getContent();
        $this->assertStringContainsString('id="news"', $html);
        $this->assertStringContainsString('一覧を見る →', $html);
        $this->assertStringContainsString('btn-outline', $html);
        $this->assertStringNotContainsString('お知らせはありません。', $html);
        $this->assertStringNotContainsString('<ul class="divide-y', $html);
    }
}
