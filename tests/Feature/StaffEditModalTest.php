<?php

namespace Tests\Feature;

use App\Models\StaffMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StaffEditModalTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    private function createStaff(array $overrides = []): StaffMember
    {
        return StaffMember::query()->create(array_merge([
            'name' => '山田 花子',
            'role' => 'スタイリスト',
            'profile' => "経歴です。\nよろしくお願いします。",
            'sort_order' => 1,
            'is_published' => true,
            'photo_path' => null,
        ], $overrides));
    }

    public function test_staff_index_embeds_edit_modal_and_json_without_edit_link(): void
    {
        $staff = $this->createStaff();

        $html = $this->actingAs($this->admin())
            ->get(route('admin.staff.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="staff-edit-modal"', $html);
        $this->assertStringContainsString('id="staff-edit-data"', $html);
        $this->assertStringContainsString('id="admin-toast-stack"', $html);
        $this->assertStringContainsString('id="admin-flash-data"', $html);
        $this->assertStringContainsString('data-open-staff-edit', $html);
        $this->assertStringContainsString('role="dialog"', $html);
        $this->assertStringContainsString('スタッフ編集', $html);
        $this->assertStringContainsString('山田 花子', $html);
        $this->assertStringContainsString('よろしくお願いします。', $html);
        $this->assertStringContainsString('menu-published-label is-published', $html);
        $this->assertStringContainsString('menu-published-dot', $html);
        $this->assertStringContainsString('data-published-text>公開</span>', $html);
        $this->assertStringContainsString('menu-published-control', $html);
        $this->assertStringContainsString('menu-published-checkbox', $html);
        $this->assertStringContainsString('syncPublishedLabel', $html);
        $this->assertStringNotContainsString('公開する', $html);
        $this->assertStringNotContainsString(
            'href="'.route('admin.staff.edit', $staff).'"',
            $html
        );
    }

    public function test_staff_index_shows_unpublished_status_badge(): void
    {
        $this->createStaff([
            'name' => '非公開スタッフ',
            'is_published' => false,
        ]);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.staff.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('menu-published-label is-unpublished', $html);
        $this->assertStringContainsString('data-published-text>非公開</span>', $html);
        $this->assertStringNotContainsString('公開する', $html);
    }

    public function test_edit_page_still_available_as_fallback(): void
    {
        $staff = $this->createStaff();

        $this->actingAs($this->admin())
            ->get(route('admin.staff.edit', $staff))
            ->assertOk()
            ->assertSee('スタッフ編集')
            ->assertSee($staff->name);
    }

    public function test_ajax_update_returns_json_and_persists_changes(): void
    {
        Storage::fake('public');
        $staff = $this->createStaff();
        $photo = UploadedFile::fake()->image('staff.jpg', 200, 200);

        $response = $this->actingAs($this->admin())
            ->putJson(route('admin.staff.update', $staff), [
                'name' => '更新後スタッフ',
                'role' => '店長',
                'profile' => '更新後プロフィール',
                'sort_order' => 3,
                'is_published' => false,
                'photo' => $photo,
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'スタッフ情報を更新しました。')
            ->assertJsonPath('staff.name', '更新後スタッフ')
            ->assertJsonPath('staff.role', '店長')
            ->assertJsonPath('staff.sort_order', 3)
            ->assertJsonPath('staff.is_published', false);

        $this->assertNotNull($response->json('staff.photo_url'));

        $this->assertDatabaseHas('staff_members', [
            'id' => $staff->id,
            'name' => '更新後スタッフ',
            'role' => '店長',
            'sort_order' => 3,
            'is_published' => false,
        ]);
    }

    public function test_ajax_update_without_photo_keeps_existing_photo(): void
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->image('keep.jpg')->store('staff', 'public');
        $staff = $this->createStaff(['photo_path' => $path]);

        $this->actingAs($this->admin())
            ->putJson(route('admin.staff.update', $staff), [
                'name' => '写真維持',
                'role' => 'スタイリスト',
                'profile' => 'profile',
                'sort_order' => 1,
                'is_published' => true,
            ])
            ->assertOk()
            ->assertJsonPath('staff.photo_path', $path);

        $this->assertDatabaseHas('staff_members', [
            'id' => $staff->id,
            'photo_path' => $path,
            'name' => '写真維持',
        ]);
    }

    public function test_normal_update_still_redirects_to_index(): void
    {
        $staff = $this->createStaff();

        $this->actingAs($this->admin())
            ->put(route('admin.staff.update', $staff), [
                'name' => '通常更新',
                'role' => 'アシスタント',
                'profile' => '通常',
                'sort_order' => 2,
                'is_published' => '1',
            ])
            ->assertRedirect(route('admin.staff.index'))
            ->assertSessionHas('success', 'スタッフを更新しました。');
    }

    public function test_ajax_update_validation_errors_return_json_422(): void
    {
        $staff = $this->createStaff();

        $this->actingAs($this->admin())
            ->putJson(route('admin.staff.update', $staff), [
                'name' => '',
                'photo' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'photo']);

        $this->assertDatabaseHas('staff_members', [
            'id' => $staff->id,
            'name' => '山田 花子',
        ]);
    }
}
