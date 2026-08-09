<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\SalonSetting;
use App\Models\TopPageSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BlogAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    private function createBlog(array $overrides = []): Blog
    {
        return Blog::query()->create(array_merge([
            'title' => '元のブログ',
            'slug' => 'original-blog',
            'body' => "本文です。\n複数行もあります。",
            'body_format' => Blog::BODY_FORMAT_PLAIN,
            'is_published' => true,
            'published_at' => now()->startOfMinute(),
            'display_order' => 1,
        ], $overrides));
    }

    public function test_blog_index_shows_card_ui_and_sticky_save(): void
    {
        $blog = $this->createBlog();

        $html = $this->actingAs($this->admin())
            ->get(route('admin.blog.index'))
            ->assertOk()
            ->assertDontSee('この機能は現在準備中です。', false)
            ->getContent();

        $this->assertStringContainsString('id="blog-bulk-form"', $html);
        $this->assertStringContainsString('enctype="multipart/form-data"', $html);
        $this->assertStringContainsString('id="blog-add-card"', $html);
        $this->assertStringContainsString('ブログを追加', $html);
        $this->assertStringContainsString('アイキャッチ画像', $html);
        $this->assertStringContainsString('投稿日', $html);
        $this->assertStringContainsString('name="blogs['.$blog->id.'][title]"', $html);
        $this->assertStringContainsString('name="blogs['.$blog->id.'][body]"', $html);
        $this->assertStringContainsString('name="blogs['.$blog->id.'][published_at]"', $html);
        $this->assertStringContainsString('name="blogs['.$blog->id.'][eye_catch]"', $html);
        $this->assertStringContainsString('data-confirm-form="blog-bulk-form"', $html);
    }

    public function test_bulk_update_creates_updates_and_deletes_blogs(): void
    {
        Storage::fake('public');

        $keep = $this->createBlog([
            'title' => '残す',
            'slug' => 'keep',
            'display_order' => 1,
            'is_published' => true,
        ]);
        $remove = $this->createBlog([
            'title' => '消す',
            'slug' => 'remove',
            'display_order' => 2,
            'eye_catch_image_path' => UploadedFile::fake()->image('old.jpg')->store('blogs', 'public'),
        ]);

        $this->actingAs($this->admin())
            ->put(route('admin.blog.update'), [
                'blogs' => [
                    $keep->id => [
                        'title' => '更新後',
                        'body' => "更新本文\n2行目",
                        'published_at' => '2026-08-01T10:00',
                        'is_published' => '0',
                        'display_order' => 2,
                        'remove_eye_catch' => '0',
                    ],
                ],
                'new_blogs' => [
                    'new_1' => [
                        'title' => '新規ブログ',
                        'body' => "新規本文です。\n複数行。",
                        'published_at' => '2026-08-02T12:00',
                        'is_published' => '1',
                        'display_order' => 1,
                        'eye_catch' => UploadedFile::fake()->image('new.jpg'),
                    ],
                ],
                'deleted_ids' => [$remove->id],
            ])
            ->assertRedirect(route('admin.blog.index'))
            ->assertSessionHas('success', 'ブログを保存しました。');

        $this->assertDatabaseMissing('blogs', ['id' => $remove->id]);

        $keep->refresh();
        $this->assertSame('更新後', $keep->title);
        $this->assertSame("更新本文\n2行目", $keep->body);
        $this->assertFalse($keep->is_published);
        $this->assertSame(2, $keep->display_order);

        $created = Blog::query()->where('title', '新規ブログ')->first();
        $this->assertNotNull($created);
        $this->assertSame(1, $created->display_order);
        $this->assertTrue($created->is_published);
        $this->assertNotNull($created->eye_catch_image_path);
        $this->assertTrue(Storage::disk('public')->exists($created->eye_catch_image_path));
    }

    public function test_bulk_update_can_remove_eye_catch_image(): void
    {
        Storage::fake('public');

        $path = UploadedFile::fake()->image('cover.jpg')->store('blogs', 'public');
        $blog = $this->createBlog([
            'eye_catch_image_path' => $path,
        ]);

        $this->actingAs($this->admin())
            ->put(route('admin.blog.update'), [
                'blogs' => [
                    $blog->id => [
                        'title' => $blog->title,
                        'body' => $blog->body,
                        'published_at' => $blog->published_at->format('Y-m-d\TH:i'),
                        'is_published' => '1',
                        'display_order' => 1,
                        'remove_eye_catch' => '1',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.blog.index'));

        $blog->refresh();
        $this->assertNull($blog->eye_catch_image_path);
        $this->assertFalse(Storage::disk('public')->exists($path));
    }

    public function test_bulk_update_requires_published_at(): void
    {
        $this->actingAs($this->admin())
            ->from(route('admin.blog.index'))
            ->put(route('admin.blog.update'), [
                'new_blogs' => [
                    'new_1' => [
                        'title' => 'タイトル',
                        'body' => '本文',
                        'published_at' => '',
                        'is_published' => '1',
                        'display_order' => 1,
                    ],
                ],
            ])
            ->assertRedirect(route('admin.blog.index'))
            ->assertSessionHasErrors(['new_blogs.new_1.published_at']);
    }

    public function test_guest_cannot_access_blog_admin(): void
    {
        $this->get(route('admin.blog.index'))->assertRedirect();
    }

    public function test_public_blog_honors_published_rules_and_home_section(): void
    {
        SalonSetting::current();
        TopPageSection::ensureDefaults();
        TopPageSection::query()->where('section_key', TopPageSection::KEY_BLOG)->update([
            'is_visible' => true,
            'display_count' => 3,
        ]);

        Blog::query()->create([
            'title' => '公開ブログ',
            'slug' => 'public-blog',
            'body' => "段落1\n\n段落2",
            'body_format' => Blog::BODY_FORMAT_PLAIN,
            'is_published' => true,
            'published_at' => now()->subDay(),
            'display_order' => 1,
        ]);
        Blog::query()->create([
            'title' => '下書き',
            'slug' => 'draft-blog',
            'body' => '下書き本文',
            'body_format' => Blog::BODY_FORMAT_PLAIN,
            'is_published' => false,
            'published_at' => now()->subDay(),
            'display_order' => 2,
        ]);
        Blog::query()->create([
            'title' => '予約投稿',
            'slug' => 'future-blog',
            'body' => '未来本文',
            'body_format' => Blog::BODY_FORMAT_PLAIN,
            'is_published' => true,
            'published_at' => now()->addDay(),
            'display_order' => 3,
        ]);

        $home = $this->get(route('home'))->assertOk()->getContent();
        $this->assertStringContainsString('id="blog"', $home);
        $this->assertStringContainsString('NEWS & BLOG', $home);
        $this->assertStringContainsString('ニュース・ブログ', $home);
        $this->assertStringContainsString('公開ブログ', $home);
        $this->assertStringContainsString('すべて見る →', $home);
        $this->assertStringContainsString(route('blog.index'), $home);
        $this->assertStringNotContainsString('下書き', $home);
        $this->assertStringNotContainsString('予約投稿', $home);

        $this->get(route('blog.index'))
            ->assertOk()
            ->assertSee('公開ブログ', false)
            ->assertDontSee('下書き', false)
            ->assertDontSee('予約投稿', false);

        $this->get(route('blog.show', 'public-blog'))
            ->assertOk()
            ->assertSee('公開ブログ', false)
            ->assertSee('段落1', false)
            ->assertSee('段落2', false);

        $this->get(route('blog.show', 'draft-blog'))->assertNotFound();
        $this->get(route('blog.show', 'future-blog'))->assertNotFound();
    }
}
