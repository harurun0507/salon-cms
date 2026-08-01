<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsCreateModalTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    public function test_news_index_embeds_create_modal_without_create_link_on_button(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('admin.news.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="news-create-modal"', $html);
        $this->assertStringContainsString('data-open-news-create', $html);
        $this->assertStringContainsString('id="news-row-template"', $html);
        $this->assertStringContainsString('role="dialog"', $html);
        $this->assertStringContainsString('お知らせ登録', $html);

        $this->assertDoesNotMatchRegularExpression(
            '/data-open-news-create[^>]*(?:href\s*=\s*["\'][^"\']*news\/create|href\s*=\s*["\'][^"\']*'.preg_quote(parse_url(route('admin.news.create'), PHP_URL_PATH), '/').')/',
            $html
        );
        $this->assertStringNotContainsString(
            'href="'.route('admin.news.create').'"',
            $html
        );
    }

    public function test_create_page_still_available_as_fallback(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.news.create'))
            ->assertOk()
            ->assertSee('お知らせ登録');
    }

    public function test_ajax_store_returns_json_and_persists_news(): void
    {
        $response = $this->actingAs($this->admin())
            ->postJson(route('admin.news.store'), [
                'title' => '新規お知らせ',
                'body' => "本文です。\n複数行もあります。",
                'published_at' => '2026-08-01T10:00',
                'is_published' => true,
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'お知らせを登録しました。')
            ->assertJsonPath('news.title', '新規お知らせ')
            ->assertJsonPath('news.body', "本文です。\n複数行もあります。")
            ->assertJsonPath('news.published_at', '2026-08-01T10:00')
            ->assertJsonPath('news.published_at_display', '2026/08/01')
            ->assertJsonPath('news.is_published', true);

        $this->assertNotNull($response->json('news.id'));
        $this->assertNotNull($response->json('news.update_url'));
        $this->assertNotNull($response->json('news.destroy_url'));

        $this->assertDatabaseHas('news', [
            'title' => '新規お知らせ',
            'body' => "本文です。\n複数行もあります。",
            'is_published' => true,
        ]);
    }

    public function test_normal_store_still_redirects_with_flash(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.news.store'), [
                'title' => '通常登録',
                'body' => '通常登録本文',
                'published_at' => '2026-08-01T12:00',
                'is_published' => '1',
            ])
            ->assertRedirect(route('admin.news.index'))
            ->assertSessionHas('success', 'お知らせを登録しました。');

        $this->assertDatabaseHas('news', [
            'title' => '通常登録',
            'body' => '通常登録本文',
            'is_published' => true,
        ]);
    }

    public function test_ajax_store_validation_errors_return_json_422(): void
    {
        $this->actingAs($this->admin())
            ->postJson(route('admin.news.store'), [
                'title' => '',
                'body' => '',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'body']);

        $this->assertDatabaseMissing('news', [
            'title' => '',
        ]);
    }

    public function test_index_orders_null_published_at_last_on_desc(): void
    {
        $user = $this->admin();

        $nullOlder = News::query()->create([
            'title' => 'Null Older',
            'slug' => 'null-older',
            'body' => 'body',
            'is_published' => true,
            'published_at' => null,
        ]);
        $dated = News::query()->create([
            'title' => 'Dated',
            'slug' => 'dated',
            'body' => 'body',
            'is_published' => true,
            'published_at' => '2026-01-01 10:00:00',
        ]);
        $nullNewer = News::query()->create([
            'title' => 'Null Newer',
            'slug' => 'null-newer',
            'body' => 'body',
            'is_published' => true,
            'published_at' => null,
        ]);

        $orderedIds = News::query()
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->pluck('id')
            ->all();

        $this->assertSame(
            [$dated->id, $nullNewer->id, $nullOlder->id],
            $orderedIds
        );

        $html = $this->actingAs($user)
            ->get(route('admin.news.index'))
            ->assertOk()
            ->getContent();

        $datedPos = strpos($html, 'data-news-row="'.$dated->id.'"');
        $nullNewerPos = strpos($html, 'data-news-row="'.$nullNewer->id.'"');
        $nullOlderPos = strpos($html, 'data-news-row="'.$nullOlder->id.'"');

        $this->assertNotFalse($datedPos);
        $this->assertNotFalse($nullNewerPos);
        $this->assertNotFalse($nullOlderPos);
        $this->assertTrue($datedPos < $nullNewerPos);
        $this->assertTrue($nullNewerPos < $nullOlderPos);
        $this->assertStringContainsString('data-published-at=""', $html);
        $this->assertStringContainsString('data-published-at="2026-01-01T10:00"', $html);
    }
}
