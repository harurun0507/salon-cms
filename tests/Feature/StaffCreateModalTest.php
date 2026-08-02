<?php

namespace Tests\Feature;

use App\Models\StaffMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StaffCreateModalTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    public function test_staff_index_embeds_create_modal_without_create_link_on_button(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('admin.staff.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="staff-create-modal"', $html);
        $this->assertStringContainsString('data-open-staff-create', $html);
        $this->assertStringContainsString('id="staff-row-template"', $html);
        $this->assertStringContainsString('role="dialog"', $html);
        $this->assertStringContainsString('スタッフ追加', $html);
        $this->assertStringContainsString('menu-published-control', $html);
        $this->assertStringContainsString('menu-published-checkbox', $html);
        $this->assertStringContainsString('data-published-control', $html);
        $this->assertStringContainsString('menu-published-label is-published', $html);
        $this->assertStringContainsString('menu-published-dot', $html);
        $this->assertStringContainsString('syncPublishedLabel', $html);
        $this->assertStringNotContainsString('公開する', $html);

        $this->assertDoesNotMatchRegularExpression(
            '/data-open-staff-create[^>]*(?:href\s*=\s*["\'][^"\']*staff\/create|href\s*=\s*["\'][^"\']*'.preg_quote(parse_url(route('admin.staff.create'), PHP_URL_PATH), '/').')/',
            $html
        );
        $this->assertStringNotContainsString(
            'href="'.route('admin.staff.create').'"',
            $html
        );
    }

    public function test_create_page_still_available_as_fallback(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.staff.create'))
            ->assertOk()
            ->assertSee('スタッフ登録');
    }

    public function test_ajax_store_returns_json_without_photo(): void
    {
        $response = $this->actingAs($this->admin())
            ->postJson(route('admin.staff.store'), [
                'name' => '新規スタッフ',
                'role' => 'スタイリスト',
                'profile' => 'プロフィールです',
                'sort_order' => 2,
                'is_published' => true,
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'スタッフを登録しました。')
            ->assertJsonPath('staff.name', '新規スタッフ')
            ->assertJsonPath('staff.role', 'スタイリスト')
            ->assertJsonPath('staff.profile', 'プロフィールです')
            ->assertJsonPath('staff.sort_order', 2)
            ->assertJsonPath('staff.is_published', true)
            ->assertJsonPath('staff.photo_url', null)
            ->assertJsonPath('staff.photo_path', null);

        $this->assertNotNull($response->json('staff.id'));
        $this->assertNotNull($response->json('staff.update_url'));
        $this->assertNotNull($response->json('staff.destroy_url'));

        $this->assertDatabaseHas('staff_members', [
            'name' => '新規スタッフ',
            'role' => 'スタイリスト',
            'sort_order' => 2,
            'is_published' => true,
            'photo_path' => null,
        ]);
    }

    public function test_ajax_store_returns_json_with_photo(): void
    {
        Storage::fake('public');
        $photo = UploadedFile::fake()->image('staff.jpg', 200, 200);

        $response = $this->actingAs($this->admin())
            ->postJson(route('admin.staff.store'), [
                'name' => '写真付きスタッフ',
                'role' => '店長',
                'profile' => '写真あり',
                'sort_order' => 1,
                'is_published' => true,
                'photo' => $photo,
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'スタッフを登録しました。')
            ->assertJsonPath('staff.name', '写真付きスタッフ')
            ->assertJsonPath('staff.is_published', true);

        $this->assertNotNull($response->json('staff.id'));
        $this->assertNotNull($response->json('staff.photo_url'));
        $this->assertNotNull($response->json('staff.photo_path'));
        $this->assertNotNull($response->json('staff.update_url'));
        $this->assertNotNull($response->json('staff.destroy_url'));

        $this->assertDatabaseHas('staff_members', [
            'name' => '写真付きスタッフ',
            'role' => '店長',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $this->assertNotNull(StaffMember::query()->where('name', '写真付きスタッフ')->value('photo_path'));
    }

    public function test_normal_store_still_redirects_with_flash(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.staff.store'), [
                'name' => '通常登録',
                'role' => 'アシスタント',
                'profile' => '通常',
                'sort_order' => 0,
                'is_published' => '1',
            ])
            ->assertRedirect(route('admin.staff.index'))
            ->assertSessionHas('success', 'スタッフを登録しました。');

        $this->assertDatabaseHas('staff_members', [
            'name' => '通常登録',
            'role' => 'アシスタント',
            'sort_order' => 0,
            'is_published' => true,
        ]);
    }

    public function test_ajax_store_validation_errors_return_json_422(): void
    {
        $this->actingAs($this->admin())
            ->postJson(route('admin.staff.store'), [
                'name' => '',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        $this->assertDatabaseMissing('staff_members', [
            'name' => '',
        ]);
    }

    public function test_ajax_store_with_bad_photo_mime_returns_422(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->postJson(route('admin.staff.store'), [
                'name' => '不正MIME',
                'photo' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['photo']);

        $this->assertDatabaseMissing('staff_members', [
            'name' => '不正MIME',
        ]);
    }
}
