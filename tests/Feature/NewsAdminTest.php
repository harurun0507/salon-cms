<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\SalonSetting;
use App\Models\TopPageSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    private function createNews(array $overrides = []): News
    {
        return News::query()->create(array_merge([
            'title' => '元のタイトル',
            'slug' => 'original-title',
            'body' => "本文です。\n複数行もあります。",
            'is_published' => true,
            'published_at' => now()->startOfMinute(),
            'display_order' => 1,
        ], $overrides));
    }

    public function test_news_index_shows_card_ui_and_sticky_save(): void
    {
        $news = $this->createNews();

        $html = $this->actingAs($this->admin())
            ->get(route('admin.news.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="news-bulk-form"', $html);
        $this->assertStringContainsString('grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3', $html);
        $this->assertStringContainsString('id="news-add-card"', $html);
        $this->assertStringContainsString('お知らせを追加', $html);
        $this->assertStringContainsString('カードを追加し、保存で登録できます。', $html);
        $this->assertStringContainsString('data-news-drag-handle', $html);
        $this->assertStringContainsString('data-news-card-title', $html);
        $this->assertStringContainsString('元のタイトル', $html);
        $this->assertStringContainsString('admin-required-badge', $html);
        $this->assertStringContainsString('必須', $html);
        $this->assertStringContainsString('name="news['.$news->id.'][title]"', $html);
        $this->assertStringContainsString('name="news['.$news->id.'][body]"', $html);
        $this->assertStringContainsString('name="news['.$news->id.'][published_at]"', $html);
        $this->assertStringContainsString('aria-label="公開状態"', $html);
        $this->assertStringContainsString('name="news['.$news->id.'][is_published]"', $html);
        $this->assertStringContainsString('admin-segmented-input', $html);
        $this->assertStringContainsString('>公開</span>', $html);
        $this->assertStringContainsString('>非公開</span>', $html);
        $this->assertStringContainsString('sticky top-[4.5rem]', $html);
        $this->assertStringContainsString('data-admin-confirm-trigger', $html);
        $this->assertStringContainsString('data-confirm-form="news-bulk-form"', $html);
        $this->assertStringContainsString('data-confirm-submit-label="保存する"', $html);
        $this->assertStringContainsString('data-news-title-input', $html);
        $this->assertStringContainsString('syncCardHeading', $html);
        $this->assertStringContainsString("emptyHeading = '新規お知らせ'", $html);
        $this->assertStringNotContainsString('id="news-create-modal"', $html);
        $this->assertStringNotContainsString('id="news-edit-modal"', $html);
        $this->assertStringNotContainsString('data-open-news-create', $html);
        $this->assertStringNotContainsString('data-open-news-edit', $html);
        $this->assertStringNotContainsString('ありません', $html);
    }

    public function test_news_index_shows_add_card_when_empty(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('admin.news.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="news-add-card"', $html);
        $this->assertStringContainsString('お知らせを追加', $html);
        $this->assertStringNotContainsString('ありません', $html);
    }

    public function test_bulk_update_creates_updates_and_deletes_news(): void
    {
        $keep = $this->createNews([
            'title' => '残す',
            'slug' => 'keep',
            'display_order' => 1,
            'is_published' => true,
        ]);
        $remove = $this->createNews([
            'title' => '消す',
            'slug' => 'remove',
            'display_order' => 2,
        ]);

        $this->actingAs($this->admin())
            ->put(route('admin.news.update'), [
                'news' => [
                    $keep->id => [
                        'title' => '更新後',
                        'body' => '更新本文',
                        'published_at' => '2026-08-01T10:00',
                        'is_published' => '0',
                        'display_order' => 2,
                    ],
                ],
                'new_news' => [
                    'new_1' => [
                        'title' => '新規お知らせ',
                        'body' => "新規本文です。\n複数行。",
                        'published_at' => '2026-08-02T12:00',
                        'is_published' => '1',
                        'display_order' => 1,
                    ],
                ],
                'deleted_ids' => [$remove->id],
            ])
            ->assertRedirect(route('admin.news.index'))
            ->assertSessionHas('success', 'お知らせを保存しました。');

        $this->assertDatabaseMissing('news', ['id' => $remove->id]);

        $keep->refresh();
        $this->assertSame('更新後', $keep->title);
        $this->assertSame('更新本文', $keep->body);
        $this->assertFalse($keep->is_published);
        $this->assertSame(2, $keep->display_order);
        $this->assertSame('2026-08-01 10:00:00', $keep->published_at?->format('Y-m-d H:i:s'));

        $created = News::query()->where('title', '新規お知らせ')->first();
        $this->assertNotNull($created);
        $this->assertSame(1, $created->display_order);
        $this->assertTrue($created->is_published);
        $this->assertNotSame('', (string) $created->slug);
    }

    public function test_bulk_update_requires_title_and_body_for_new_news(): void
    {
        $this->actingAs($this->admin())
            ->from(route('admin.news.index'))
            ->put(route('admin.news.update'), [
                'new_news' => [
                    'new_1' => [
                        'title' => '',
                        'body' => '',
                        'display_order' => 1,
                        'is_published' => '1',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.news.index'))
            ->assertSessionHasErrors(['new_news.new_1.title', 'new_news.new_1.body']);
    }

    public function test_bulk_update_preserves_input_on_validation_errors(): void
    {
        $news = $this->createNews(['title' => '既存', 'slug' => 'existing']);

        $this->actingAs($this->admin())
            ->from(route('admin.news.index'))
            ->put(route('admin.news.update'), [
                'news' => [
                    $news->id => [
                        'title' => '',
                        'body' => '残す本文',
                        'published_at' => '2026-08-01T09:00',
                        'is_published' => '1',
                        'display_order' => 1,
                    ],
                ],
                'new_news' => [
                    'new_1' => [
                        'title' => '新規入力途中',
                        'body' => '',
                        'published_at' => '2026-08-03T11:00',
                        'is_published' => '0',
                        'display_order' => 2,
                    ],
                ],
            ])
            ->assertRedirect(route('admin.news.index'))
            ->assertSessionHasErrors(['news.'.$news->id.'.title', 'new_news.new_1.body']);

        $this->assertSame('残す本文', session()->getOldInput('news.'.$news->id.'.body'));
        $this->assertSame('新規入力途中', session()->getOldInput('new_news.new_1.title'));
        $this->assertSame('2026-08-03T11:00', session()->getOldInput('new_news.new_1.published_at'));

        $html = $this->actingAs($this->admin())
            ->get(route('admin.news.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('残す本文', $html);
        $this->assertStringContainsString('新規入力途中', $html);
        $this->assertStringContainsString('name="new_news[new_1][title]"', $html);
        $this->assertStringContainsString('2026-08-03T11:00', $html);
    }

    public function test_bulk_update_normalizes_display_order(): void
    {
        $a = $this->createNews(['title' => 'A', 'slug' => 'a', 'display_order' => 10]);
        $b = $this->createNews(['title' => 'B', 'slug' => 'b', 'display_order' => 20]);

        $this->actingAs($this->admin())
            ->put(route('admin.news.update'), [
                'news' => [
                    $b->id => [
                        'title' => 'B',
                        'body' => 'body-b',
                        'display_order' => 1,
                        'is_published' => '1',
                    ],
                    $a->id => [
                        'title' => 'A',
                        'body' => 'body-a',
                        'display_order' => 2,
                        'is_published' => '1',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.news.index'));

        $this->assertSame(1, $b->fresh()->display_order);
        $this->assertSame(2, $a->fresh()->display_order);
    }

    public function test_guest_cannot_access_news_admin(): void
    {
        $this->get(route('admin.news.index'))->assertRedirect();
        $this->put(route('admin.news.update'), [])->assertRedirect();
    }

    public function test_public_news_honors_display_order_and_published_rules(): void
    {
        SalonSetting::current();
        TopPageSection::ensureDefaults();

        $this->createNews([
            'title' => '後で表示',
            'slug' => 'second',
            'display_order' => 2,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);
        $this->createNews([
            'title' => '先に表示',
            'slug' => 'first',
            'display_order' => 1,
            'is_published' => true,
            'published_at' => now()->subDays(5),
        ]);
        $this->createNews([
            'title' => '非公開',
            'slug' => 'draft',
            'display_order' => 0,
            'is_published' => false,
            'published_at' => now(),
        ]);
        $this->createNews([
            'title' => '未来公開',
            'slug' => 'future',
            'display_order' => 0,
            'is_published' => true,
            'published_at' => now()->addDay(),
        ]);

        $homeHtml = $this->get(route('home'))->assertOk()->getContent();
        $this->assertStringContainsString('先に表示', $homeHtml);
        $this->assertStringContainsString('後で表示', $homeHtml);
        $this->assertStringNotContainsString('非公開', $homeHtml);
        $this->assertStringNotContainsString('未来公開', $homeHtml);
        $this->assertLessThan(strpos($homeHtml, '後で表示'), strpos($homeHtml, '先に表示'));

        $listHtml = $this->get(route('news.index'))->assertOk()->getContent();
        $this->assertStringContainsString('先に表示', $listHtml);
        $this->assertLessThan(strpos($listHtml, '後で表示'), strpos($listHtml, '先に表示'));

        $this->get(route('news.show', 'first'))->assertOk()->assertSee('先に表示');
        $this->get(route('news.show', 'draft'))->assertNotFound();
        $this->get(route('news.show', 'future'))->assertNotFound();
    }
}
