<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminToastTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    public function test_admin_layout_has_bottom_toast_stack_without_top_success_flash_markup(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="admin-toast-stack"', $html);
        $this->assertStringContainsString('id="admin-flash-data"', $html);
        $this->assertStringContainsString('window.showToast', $html);
        $this->assertStringContainsString('AdminToast', $html);
        $this->assertStringContainsString('data-toast-keep-open', $html);
        $this->assertStringContainsString('dismissToast(item, true)', $html);
        $this->assertStringNotContainsString('border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800', $html);
    }

    public function test_redirect_success_flash_is_passed_to_toast_payload(): void
    {
        News::query()->create([
            'title' => '削除対象',
            'slug' => 'to-delete',
            'body' => 'body',
            'is_published' => false,
            'display_order' => 1,
        ]);

        $news = News::query()->first();

        $this->actingAs($this->admin())
            ->put(route('admin.news.update'), [
                'deleted_ids' => [$news->id],
            ])
            ->assertRedirect(route('admin.news.index'))
            ->assertSessionHas('success', 'お知らせを保存しました。');

        $html = $this->actingAs($this->admin())
            ->withSession(['success' => 'お知らせを保存しました。'])
            ->get(route('admin.news.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('お知らせを保存しました。', $html);
        $this->assertStringContainsString('id="admin-flash-data"', $html);
        $this->assertStringNotContainsString(
            'mb-6 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800',
            $html
        );
    }

    public function test_news_validation_errors_are_passed_to_toast_payload_without_inline_or_banner(): void
    {
        $html = $this->actingAs($this->admin())
            ->followingRedirects()
            ->from(route('admin.news.index'))
            ->put(route('admin.news.update'), [
                'new_news' => [
                    'new_1' => [
                        'title' => 'タイトルあり',
                        'body' => '',
                        'category' => 'other',
                        'display_order' => 1,
                    ],
                ],
            ])
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('"validationErrors"', $html);
        $this->assertStringNotContainsString('本文は必須です。', $html);
        $this->assertStringContainsString('公開日時は必須です。', $html);
        $this->assertStringContainsString('公開状態を選択してください。', $html);
        $this->assertStringContainsString('入力内容を確認してください。', $html);
        $this->assertStringContainsString('duration: 12000', $html);
        $this->assertStringNotContainsString('mb-6 rounded-lg border border-red-200 bg-red-50', $html);
        $this->assertStringNotContainsString('text-sm text-red-600', $html);
        $this->assertStringContainsString('タイトルあり', $html);
        $this->assertStringContainsString('name="new_news[new_1][title]"', $html);
    }

    public function test_menu_validation_errors_are_passed_to_toast_payload_without_inline_or_banner(): void
    {
        $category = \App\Models\MenuCategory::query()->create(['name' => 'カット', 'sort_order' => 1]);
        $menu = \App\Models\Menu::query()->create([
            'name' => 'カットベーシック',
            'price' => '¥5,000',
            'description' => '説明あり',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $menu->categories()->attach($category->id, ['sort_order' => 1]);

        $html = $this->actingAs($this->admin())
            ->followingRedirects()
            ->from(route('admin.menus.index'))
            ->put(route('admin.menus.bulk-update'), [
                'selected_category_id' => $category->id,
                'categories' => [
                    $category->id => ['name' => 'カット', 'sort_order' => 1],
                ],
                'menus' => [
                    $menu->id => [
                        'category_ids' => [$category->id],
                        'name' => '',
                        'price' => '¥5,000',
                        'description' => '説明あり',
                        'sorts' => [
                            $category->id => 1,
                        ],
                        'is_published' => '1',
                    ],
                ],
            ])
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('"validationErrors"', $html);
        $this->assertStringContainsString('メニュー名は必須です。', $html);
        $this->assertStringContainsString('入力内容を確認してください。', $html);
        $this->assertStringContainsString('duration: 12000', $html);
        $this->assertStringNotContainsString('mb-6 rounded-lg border border-red-200 bg-red-50', $html);
        $this->assertStringNotContainsString('text-xs text-admin-danger', $html);
        $this->assertStringNotContainsString('text-sm text-admin-danger', $html);
        $this->assertStringContainsString('説明あり', $html);
        $this->assertStringContainsString('name="menus['.$menu->id.'][description]"', $html);
        $this->assertDoesNotMatchRegularExpression(
            '/name="menus\['.$menu->id.'\]\[name\]"[^>]*\brequired\b/',
            $html
        );

        preg_match('/id="admin-flash-data">(.*?)<\/script>/s', $html, $flashMatch);
        $flash = json_decode($flashMatch[1] ?? '{}', true);
        $this->assertSame(['メニュー名は必須です。'], $flash['validationErrors'] ?? null);

        $categoryHtml = $this->actingAs($this->admin())
            ->followingRedirects()
            ->from(route('admin.menus.index'))
            ->put(route('admin.menus.bulk-update'), [
                'selected_category_id' => $category->id,
                'categories' => [
                    $category->id => ['name' => 'カット', 'sort_order' => 1],
                ],
                'menus' => [
                    $menu->id => [
                        'category_ids' => [],
                        'name' => '保持されるメニュー名',
                        'price' => '¥5,000',
                        'description' => '説明あり',
                        'sorts' => [
                            $category->id => 1,
                        ],
                        'is_published' => '1',
                    ],
                ],
            ])
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('カテゴリを1つ以上選択してください。', $categoryHtml);
        $this->assertStringContainsString('入力内容を確認してください。', $categoryHtml);
        $this->assertStringContainsString('保持されるメニュー名', $categoryHtml);
        $this->assertStringNotContainsString('mb-6 rounded-lg border border-red-200 bg-red-50', $categoryHtml);
    }

    public function test_blog_validation_errors_are_passed_to_toast_payload_without_inline_or_banner(): void
    {
        $html = $this->actingAs($this->admin())
            ->followingRedirects()
            ->from(route('admin.blog.index'))
            ->put(route('admin.blog.update'), [
                'new_blogs' => [
                    'new_1' => [
                        'title' => 'タイトルあり',
                        'body' => '本文あり',
                        'display_order' => 1,
                    ],
                ],
            ])
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('"validationErrors"', $html);
        $this->assertStringContainsString('投稿日時は必須です。', $html);
        $this->assertStringContainsString('公開状態を選択してください。', $html);
        $this->assertStringContainsString('入力内容を確認してください。', $html);
        $this->assertStringContainsString('duration: 12000', $html);
        $this->assertStringNotContainsString('mb-6 rounded-lg border border-red-200 bg-red-50', $html);
        $this->assertStringNotContainsString('mb-4 rounded-lg border border-red-200 bg-red-50', $html);
        $this->assertStringNotContainsString('text-sm text-red-600', $html);
        $this->assertStringNotContainsString('text-sm text-red-700', $html);
        $this->assertStringContainsString('タイトルあり', $html);
        $this->assertStringContainsString('name="new_blogs[new_1][title]"', $html);
    }
}
