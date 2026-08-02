<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsEditModalTest extends TestCase
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
        ], $overrides));
    }

    public function test_news_index_embeds_edit_modal_and_json_payload_without_edit_link(): void
    {
        $user = $this->admin();
        $news = $this->createNews();

        $html = $this->actingAs($user)
            ->get(route('admin.news.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="news-edit-modal"', $html);
        $this->assertStringContainsString('id="news-edit-data"', $html);
        $this->assertStringContainsString('data-open-news-edit', $html);
        $this->assertStringContainsString('role="dialog"', $html);
        $this->assertStringContainsString('aria-modal="true"', $html);
        $this->assertStringContainsString('お知らせ編集', $html);
        $this->assertStringContainsString('元のタイトル', $html);
        $this->assertStringContainsString('複数行もあります。', $html);
        $this->assertStringContainsString('menu-published-label is-published', $html);
        $this->assertStringContainsString('menu-published-dot', $html);
        $this->assertStringContainsString('data-published-text>公開</span>', $html);
        $this->assertStringContainsString('menu-published-control', $html);
        $this->assertStringContainsString('menu-published-checkbox', $html);
        $this->assertStringContainsString('syncPublishedLabel', $html);
        $this->assertStringContainsString('setPublishedStatusCell', $html);
        $this->assertStringNotContainsString('公開する', $html);
        $this->assertStringNotContainsString('news-status rounded-full', $html);
        $this->assertStringNotContainsString(
            'href="'.route('admin.news.edit', $news).'"',
            $html
        );
    }

    public function test_news_index_shows_unpublished_status_label(): void
    {
        $this->createNews([
            'title' => '非公開お知らせ',
            'slug' => 'unpublished-news',
            'is_published' => false,
        ]);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.news.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('menu-published-label is-unpublished', $html);
        $this->assertStringContainsString('data-published-text>非公開</span>', $html);
        $this->assertStringNotContainsString('公開する', $html);
        $this->assertStringNotContainsString('news-status rounded-full', $html);
    }

    public function test_edit_page_still_available_as_fallback(): void
    {
        $user = $this->admin();
        $news = $this->createNews();

        $this->actingAs($user)
            ->get(route('admin.news.edit', $news))
            ->assertOk()
            ->assertSee('お知らせ編集')
            ->assertSee($news->title);
    }

    public function test_ajax_update_returns_json_and_persists_changes(): void
    {
        $user = $this->admin();
        $news = $this->createNews();

        $response = $this->actingAs($user)
            ->putJson(route('admin.news.update', $news), [
                'title' => '更新後タイトル',
                'body' => '更新後本文',
                'published_at' => '2026-08-01T10:00',
                'is_published' => false,
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'お知らせを更新しました。')
            ->assertJsonPath('news.title', '更新後タイトル')
            ->assertJsonPath('news.body', '更新後本文')
            ->assertJsonPath('news.is_published', false)
            ->assertJsonPath('news.published_at_display', '2026/08/01');

        $this->assertDatabaseHas('news', [
            'id' => $news->id,
            'title' => '更新後タイトル',
            'body' => '更新後本文',
            'is_published' => false,
        ]);
    }

    public function test_normal_update_still_redirects_to_index(): void
    {
        $user = $this->admin();
        $news = $this->createNews();

        $this->actingAs($user)
            ->put(route('admin.news.update', $news), [
                'title' => '通常更新タイトル',
                'body' => '通常更新本文',
                'published_at' => '2026-08-01T12:00',
                'is_published' => '1',
            ])
            ->assertRedirect(route('admin.news.index'))
            ->assertSessionHas('success', 'お知らせを更新しました。');

        $this->assertDatabaseHas('news', [
            'id' => $news->id,
            'title' => '通常更新タイトル',
            'body' => '通常更新本文',
            'is_published' => true,
        ]);
    }

    public function test_ajax_update_validation_errors_return_json_422(): void
    {
        $user = $this->admin();
        $news = $this->createNews();

        $this->actingAs($user)
            ->putJson(route('admin.news.update', $news), [
                'title' => '',
                'body' => '',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'body']);

        $this->assertDatabaseHas('news', [
            'id' => $news->id,
            'title' => '元のタイトル',
        ]);
    }
}
