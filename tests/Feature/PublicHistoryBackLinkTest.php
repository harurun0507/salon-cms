<?php

namespace Tests\Feature;

use App\Models\SalonSetting;
use App\Models\TopPageSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicHistoryBackLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_pages_link_back_to_home_section_hashes(): void
    {
        SalonSetting::current();
        TopPageSection::ensureDefaults();

        $cases = [
            route('gallery') => ['/#gallery', 'Gallery'],
            route('blog.index') => ['/#news', 'Blog'],
            route('news.index') => ['/#news', 'News'],
            route('staff') => ['/#staff', 'Staff'],
            route('menu') => ['/#menu', 'Menu'],
        ];

        foreach ($cases as $url => [$hashPath, $eyebrow]) {
            $html = $this->get($url)->assertOk()->getContent();
            $expectedHref = url($hashPath);

            $this->assertStringContainsString('site-list-page-header', $html, $url);
            $this->assertStringContainsString('btn-outline', $html, $url);
            $this->assertStringContainsString('site-back-link--history', $html, $url);
            $this->assertStringContainsString('>戻る</span>', $html, $url);
            $this->assertStringContainsString('href="'.$expectedHref.'"', $html, $url);
            $this->assertStringContainsString($eyebrow, $html, $url);
            $this->assertStringNotContainsString('data-history-back', $html, $url);
            $this->assertStringNotContainsString('history.back', $html, $url);
            $this->assertStringNotContainsString('前に戻る', $html, $url);
            $this->assertStringNotContainsString('一覧へ戻る', $html, $url);
        }

        $this->assertSame(url('/#gallery'), TopPageSection::listPageBackHref('gallery'));
        $this->assertSame(url('/#menu'), TopPageSection::listPageBackHref('menu'));
        $this->assertSame(url('/#staff'), TopPageSection::listPageBackHref('staff'));
        $this->assertSame(url('/#news'), TopPageSection::listPageBackHref('news'));
        $this->assertSame(url('/#news'), TopPageSection::listPageBackHref('blog'));
    }

    public function test_blog_back_uses_blog_hash_when_news_section_is_hidden(): void
    {
        SalonSetting::current();
        TopPageSection::ensureDefaults();
        TopPageSection::query()->where('section_key', TopPageSection::KEY_NEWS)->update([
            'is_visible' => false,
        ]);
        TopPageSection::query()->where('section_key', TopPageSection::KEY_BLOG)->update([
            'is_visible' => true,
        ]);

        $this->assertSame(url('/#blog'), TopPageSection::listPageBackHref('blog'));
        $this->assertSame(url('/#blog'), TopPageSection::listPageBackHref('news'));

        $html = $this->get(route('blog.index'))->assertOk()->getContent();
        $this->assertStringContainsString('href="'.url('/#blog').'"', $html);
    }
}
