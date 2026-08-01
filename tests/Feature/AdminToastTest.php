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
        $this->assertStringNotContainsString('border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800', $html);
    }

    public function test_redirect_success_flash_is_passed_to_toast_payload(): void
    {
        News::query()->create([
            'title' => '削除対象',
            'slug' => 'to-delete',
            'body' => 'body',
            'is_published' => false,
        ]);

        $news = News::query()->first();

        $this->actingAs($this->admin())
            ->delete(route('admin.news.destroy', $news))
            ->assertRedirect(route('admin.news.index'))
            ->assertSessionHas('success', 'お知らせを削除しました。');

        $html = $this->actingAs($this->admin())
            ->withSession(['success' => 'お知らせを削除しました。'])
            ->get(route('admin.news.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('お知らせを削除しました。', $html);
        $this->assertStringContainsString('id="admin-flash-data"', $html);
        $this->assertStringNotContainsString(
            'mb-6 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800',
            $html
        );
    }
}
