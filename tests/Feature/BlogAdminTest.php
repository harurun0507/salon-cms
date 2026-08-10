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
        $this->assertStringContainsString('投稿日時', $html);
        $this->assertStringNotContainsString('>投稿日<', $html);
        $this->assertStringContainsString('name="blogs['.$blog->id.'][title]"', $html);
        $this->assertStringContainsString('name="blogs['.$blog->id.'][body]"', $html);
        $this->assertStringContainsString('name="blogs['.$blog->id.'][published_at]"', $html);
        $this->assertStringContainsString('name="blogs['.$blog->id.'][eye_catch]"', $html);
        $this->assertStringContainsString('data-blog-published-at', $html);
        $this->assertStringContainsString('data-blog-is-published', $html);
        $this->assertStringContainsString('fillPublishedAtIfEmpty', $html);
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

    public function test_validation_error_keeps_uploaded_new_blog_image_as_pending_preview(): void
    {
        Storage::fake('public');

        $html = $this->actingAs($this->admin())
            ->followingRedirects()
            ->from(route('admin.blog.index'))
            ->put(route('admin.blog.update'), [
                'new_blogs' => [
                    'new_1' => [
                        'title' => '画像ありタイトル',
                        'body' => '本文',
                        'published_at' => '',
                        'is_published' => '1',
                        'display_order' => 1,
                        'eye_catch' => UploadedFile::fake()->image('cover.jpg', 800, 500),
                    ],
                ],
            ])
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('投稿日時は必須です。', $html);
        $this->assertStringContainsString('name="new_blogs[new_1][pending_image_path]"', $html);
        $this->assertStringContainsString('選択中の画像を保持しています', $html);
        $this->assertMatchesRegularExpression(
            '#name="new_blogs\[new_1\]\[pending_image_path\]"\s+value="blogs/tmp/[^"]+"#',
            $html
        );
        $this->assertStringContainsString('storage/blogs/tmp/', $html);
        $this->assertSame(0, Blog::query()->count());

        $pendingFiles = Storage::disk('public')->allFiles('blogs/tmp');
        $this->assertNotEmpty($pendingFiles);
    }

    public function test_new_blog_can_be_saved_using_pending_image_after_validation_error(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->from(route('admin.blog.index'))
            ->put(route('admin.blog.update'), [
                'new_blogs' => [
                    'new_1' => [
                        'title' => '再保存ブログ',
                        'body' => '本文',
                        'published_at' => '',
                        'is_published' => '1',
                        'display_order' => 1,
                        'eye_catch' => UploadedFile::fake()->image('retry.jpg', 800, 500),
                    ],
                ],
            ])
            ->assertRedirect(route('admin.blog.index'))
            ->assertSessionHasErrors(['new_blogs.new_1.published_at']);

        $pendingPath = session()->getOldInput('new_blogs.new_1.pending_image_path');
        $this->assertIsString($pendingPath);
        Storage::disk('public')->assertExists($pendingPath);

        $this->actingAs($this->admin())
            ->put(route('admin.blog.update'), [
                'new_blogs' => [
                    'new_1' => [
                        'title' => '再保存ブログ',
                        'body' => '本文',
                        'published_at' => '2026-08-01T10:00',
                        'is_published' => '1',
                        'display_order' => 1,
                        'pending_image_path' => $pendingPath,
                    ],
                ],
            ])
            ->assertRedirect(route('admin.blog.index'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $created = Blog::query()->where('title', '再保存ブログ')->first();
        $this->assertNotNull($created);
        $this->assertStringStartsWith('blogs/', $created->eye_catch_image_path);
        $this->assertStringNotContainsString('/tmp/', $created->eye_catch_image_path);
        Storage::disk('public')->assertExists($created->eye_catch_image_path);
        Storage::disk('public')->assertMissing($pendingPath);
    }

    public function test_validation_error_keeps_existing_blog_image_preview(): void
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->image('keep.jpg')->store('blogs', 'public');
        $blog = $this->createBlog([
            'eye_catch_image_path' => $path,
            'title' => '既存ブログ',
        ]);

        $html = $this->actingAs($this->admin())
            ->followingRedirects()
            ->from(route('admin.blog.index'))
            ->put(route('admin.blog.update'), [
                'blogs' => [
                    $blog->id => [
                        'title' => '',
                        'body' => $blog->body,
                        'published_at' => $blog->published_at->format('Y-m-d\TH:i'),
                        'is_published' => '1',
                        'display_order' => 1,
                        'remove_eye_catch' => '0',
                    ],
                ],
            ])
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('storage/'.$path, $html);
        $this->assertStringContainsString('data-blog-image', $html);
        $this->assertStringContainsString('タイトルは必須です。', $html);
        Storage::disk('public')->assertExists($path);
        $this->assertSame($path, $blog->fresh()->eye_catch_image_path);
    }

    public function test_validation_error_with_new_upload_keeps_pending_without_replacing_saved_image(): void
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->image('saved.jpg')->store('blogs', 'public');
        $blog = $this->createBlog([
            'eye_catch_image_path' => $path,
        ]);

        $html = $this->actingAs($this->admin())
            ->followingRedirects()
            ->from(route('admin.blog.index'))
            ->put(route('admin.blog.update'), [
                'blogs' => [
                    $blog->id => [
                        'title' => '',
                        'body' => $blog->body,
                        'published_at' => $blog->published_at->format('Y-m-d\TH:i'),
                        'is_published' => '1',
                        'display_order' => 1,
                        'remove_eye_catch' => '0',
                        'eye_catch' => UploadedFile::fake()->image('next.jpg', 800, 500),
                    ],
                ],
            ])
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('選択中の画像を保持しています', $html);
        $this->assertStringContainsString('storage/blogs/tmp/', $html);
        Storage::disk('public')->assertExists($path);
        $this->assertSame($path, $blog->fresh()->eye_catch_image_path);
        $this->assertNotEmpty(Storage::disk('public')->allFiles('blogs/tmp'));
    }

    public function test_validation_error_with_remove_flag_does_not_delete_stored_image(): void
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->image('keep.jpg')->store('blogs', 'public');
        $blog = $this->createBlog([
            'eye_catch_image_path' => $path,
        ]);

        $html = $this->actingAs($this->admin())
            ->followingRedirects()
            ->from(route('admin.blog.index'))
            ->put(route('admin.blog.update'), [
                'blogs' => [
                    $blog->id => [
                        'title' => '',
                        'body' => $blog->body,
                        'published_at' => $blog->published_at->format('Y-m-d\TH:i'),
                        'is_published' => '1',
                        'display_order' => 1,
                        'remove_eye_catch' => '1',
                    ],
                ],
            ])
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('タイトルは必須です。', $html);
        $this->assertStringNotContainsString('storage/'.$path, $html);
        Storage::disk('public')->assertExists($path);
        $this->assertSame($path, $blog->fresh()->eye_catch_image_path);
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
            ->assertSessionHasErrors(['new_blogs.new_1.published_at' => '投稿日時は必須です。']);
    }

    public function test_bulk_update_requires_publish_state(): void
    {
        $this->actingAs($this->admin())
            ->from(route('admin.blog.index'))
            ->put(route('admin.blog.update'), [
                'new_blogs' => [
                    'new_1' => [
                        'title' => 'タイトル',
                        'body' => '本文',
                        'published_at' => '2026-08-09T16:30',
                        'display_order' => 1,
                    ],
                ],
            ])
            ->assertRedirect(route('admin.blog.index'))
            ->assertSessionHasErrors(['new_blogs.new_1.is_published' => '公開状態を選択してください。']);
    }

    public function test_new_blog_card_starts_with_publish_unselected(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('admin.blog.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(
            "'<input type=\"radio\" name=\"new_blogs[' + key + '][is_published]\" value=\"1\" class=\"admin-segmented-input\" data-blog-is-published>'",
            $html
        );
        $this->assertStringNotContainsString(
            "name=\"new_blogs[' + key + '][is_published]\" value=\"1\" class=\"admin-segmented-input\" checked",
            $html
        );
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
        $this->assertStringContainsString('blog-eyecatch-placeholder', $home);
        $this->assertStringContainsString('blog-eyecatch--placeholder', $home);
        $this->assertStringContainsString('blog-eyecatch-tab', $home);
        $this->assertStringContainsString('blog-card-label', $home);
        $this->assertStringContainsString('blog-card-title', $home);
        $this->assertStringContainsString('blog-card-date', $home);
        $this->assertStringContainsString('home-blog-card-more', $home);
        $this->assertStringContainsString('home-blog-card-hit', $home);
        $this->assertStringContainsString('blog-eyecatch-placeholder-shop', $home);
        $this->assertStringNotContainsString('下書き', $home);
        $this->assertStringNotContainsString('予約投稿', $home);

        $indexHtml = $this->get(route('blog.index'))
            ->assertOk()
            ->assertSee('公開ブログ', false)
            ->assertDontSee('下書き', false)
            ->assertDontSee('予約投稿', false)
            ->getContent();
        $this->assertStringContainsString('blog-eyecatch-placeholder', $indexHtml);
        $this->assertStringContainsString('blog-eyecatch-tab', $indexHtml);
        $this->assertStringContainsString('blog-card-label', $indexHtml);
        $this->assertStringNotContainsString('home-blog-card-more', $indexHtml);
        $this->assertStringContainsString('blog-card-title', $indexHtml);
        $this->assertStringContainsString('blog-card-date', $indexHtml);
        $this->assertStringContainsString('blog-eyecatch-placeholder-shop', $indexHtml);

        $detailHtml = $this->get(route('blog.show', 'public-blog'))
            ->assertOk()
            ->assertSee('公開ブログ', false)
            ->assertSee('段落1', false)
            ->assertSee('段落2', false)
            ->getContent();
        $this->assertStringNotContainsString('blog-eyecatch-placeholder', $detailHtml);

        $this->get(route('blog.show', 'draft-blog'))->assertNotFound();
        $this->get(route('blog.show', 'future-blog'))->assertNotFound();
    }
}
