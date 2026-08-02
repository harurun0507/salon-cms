<?php

namespace Tests\Feature;

use App\Models\StaffMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StaffBulkSaveTest extends TestCase
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

    public function test_staff_index_shows_inline_cards_and_bulk_save_without_modals(): void
    {
        $staff = $this->createStaff();

        $html = $this->actingAs($this->admin())
            ->get(route('admin.staff.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="staff-bulk-form"', $html);
        $this->assertStringContainsString('data-staff-workspace', $html);
        $this->assertStringContainsString('grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3', $html);
        $this->assertStringContainsString('id="staff-add-card"', $html);
        $this->assertStringContainsString('data-staff-add', $html);
        $this->assertStringContainsString('btn-admin-create', $html);
        $this->assertStringContainsString('スタッフを追加', $html);
        $this->assertStringContainsString('カードを追加し、保存で登録できます。', $html);
        $this->assertStringContainsString('addCard.before(card)', $html);
        $this->assertStringContainsString('addButton.addEventListener', $html);
        $this->assertStringContainsString('data-staff-card', $html);
        $this->assertStringContainsString('data-staff-existing', $html);
        $this->assertStringContainsString('name="staff['.$staff->id.'][name]"', $html);
        $this->assertStringContainsString('name="staff['.$staff->id.'][role]"', $html);
        $this->assertStringContainsString('name="staff['.$staff->id.'][profile]"', $html);
        $this->assertStringContainsString('name="staff['.$staff->id.'][sort_order]"', $html);
        $this->assertStringContainsString('data-staff-order', $html);
        $this->assertStringContainsString('type="hidden"', $html);
        $this->assertStringContainsString('data-staff-drag-handle', $html);
        $this->assertStringContainsString('staff-drag-handle', $html);
        $this->assertStringContainsString('function syncDisplayOrders', $html);
        $this->assertStringContainsString('admin-segmented-input', $html);
        $this->assertStringContainsString('aria-label="公開状態"', $html);
        $this->assertStringContainsString('banner-dropzone', $html);
        $this->assertStringContainsString('banner-dropzone-main', $html);
        $this->assertStringContainsString('banner-dropzone-drag-message', $html);
        $this->assertStringContainsString('ここに写真をドロップしてください', $html);
        $this->assertStringContainsString('写真をドラッグ＆ドロップ、またはクリックして選択', $html);
        $this->assertStringContainsString('JPEG / PNG / WebP、5MBまで', $html);
        $this->assertStringContainsString('_staffDragCounter', $html);
        $this->assertStringContainsString('data-staff-card-title', $html);
        $this->assertStringContainsString('data-staff-name-input', $html);
        $this->assertStringContainsString('新規スタッフ', $html);
        $this->assertStringContainsString('山田 花子', $html);
        $this->assertStringContainsString('function syncCardHeading', $html);
        $this->assertStringContainsString('banner-card-label', $html);
        $this->assertStringContainsString('admin-icon-btn-delete', $html);
        $this->assertStringContainsString('data-staff-remove', $html);
        $this->assertStringContainsString('admin-required-badge', $html);
        $this->assertStringContainsString('sticky top-[4.5rem]', $html);
        $this->assertStringContainsString('-mx-4 -mt-4 mb-6', $html);
        $this->assertStringContainsString('保存する', $html);
        $this->assertStringContainsString('公開サイトに表示するスタッフ情報を登録・編集します。', $html);
        $this->assertStringContainsString('data-admin-confirm-trigger', $html);
        $this->assertStringContainsString('data-confirm-form="staff-bulk-form"', $html);
        $this->assertStringContainsString('data-confirm-submit-label="保存する"', $html);
        $this->assertStringContainsString('aspect-square', $html);
        $this->assertStringNotContainsString('menu-published-control', $html);
        $this->assertStringNotContainsString('menu-published-checkbox', $html);
        $this->assertStringNotContainsString('スタッフ追加', $html);
        $this->assertStringNotContainsString('id="staff-create-modal"', $html);
        $this->assertStringNotContainsString('id="staff-edit-modal"', $html);
        $this->assertStringNotContainsString('id="staff-edit-data"', $html);
        $this->assertStringNotContainsString('data-open-staff-create', $html);
        $this->assertStringNotContainsString('data-open-staff-edit', $html);
        $this->assertStringNotContainsString('admin-icon-btn-edit', $html);
        $this->assertStringNotContainsString('admin-action-group', $html);
        $this->assertStringNotContainsString('admin-table', $html);
        $this->assertStringNotContainsString('スタッフが登録されていません。', $html);
    }

    public function test_staff_index_shows_add_card_when_empty(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('admin.staff.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="staff-add-card"', $html);
        $this->assertStringContainsString('btn-admin-create', $html);
        $this->assertStringContainsString('data-staff-add', $html);
        $this->assertStringContainsString('スタッフを追加', $html);
        $this->assertStringContainsString('カードを追加し、保存で登録できます。', $html);
        $this->assertStringContainsString('新規スタッフ', $html);
        $this->assertStringNotContainsString('スタッフが登録されていません。', $html);
    }

    public function test_bulk_update_creates_updates_and_deletes_staff(): void
    {
        Storage::fake('public');

        $keep = $this->createStaff([
            'name' => '残すスタッフ',
            'photo_path' => UploadedFile::fake()->image('keep.jpg')->store('staff', 'public'),
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $remove = $this->createStaff([
            'name' => '消すスタッフ',
            'photo_path' => UploadedFile::fake()->image('remove.jpg')->store('staff', 'public'),
            'sort_order' => 2,
            'is_published' => true,
        ]);
        $removePath = $remove->photo_path;

        $newPhoto = UploadedFile::fake()->image('new.jpg', 200, 200);
        $replacePhoto = UploadedFile::fake()->image('replace.jpg', 200, 200);

        $this->actingAs($this->admin())
            ->put(route('admin.staff.bulk-update'), [
                'staff' => [
                    $keep->id => [
                        'name' => '更新後スタッフ',
                        'role' => '店長',
                        'profile' => '更新プロフィール',
                        'sort_order' => 2,
                        'is_published' => '0',
                        'photo' => $replacePhoto,
                    ],
                ],
                'new_staff' => [
                    'new_1' => [
                        'name' => '新規スタッフ',
                        'role' => 'アシスタント',
                        'profile' => '新規プロフィール',
                        'sort_order' => 1,
                        'is_published' => '1',
                        'photo' => $newPhoto,
                    ],
                ],
                'deleted_ids' => [$remove->id],
            ])
            ->assertRedirect(route('admin.staff.index'))
            ->assertSessionHas('success', 'スタッフを一括保存しました。');

        $this->assertDatabaseHas('staff_members', [
            'id' => $keep->id,
            'name' => '更新後スタッフ',
            'role' => '店長',
            'profile' => '更新プロフィール',
            'sort_order' => 2,
            'is_published' => false,
        ]);
        $this->assertDatabaseMissing('staff_members', ['id' => $remove->id]);
        $this->assertDatabaseHas('staff_members', [
            'name' => '新規スタッフ',
            'role' => 'アシスタント',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        Storage::disk('public')->assertMissing($removePath);
        $keep->refresh();
        $this->assertNotNull($keep->photo_path);
        Storage::disk('public')->assertExists($keep->photo_path);
    }

    public function test_bulk_update_normalizes_sort_order_to_sequential(): void
    {
        $first = $this->createStaff([
            'name' => 'A',
            'sort_order' => 10,
        ]);
        $second = $this->createStaff([
            'name' => 'B',
            'sort_order' => 20,
        ]);

        $this->actingAs($this->admin())
            ->put(route('admin.staff.bulk-update'), [
                'staff' => [
                    $first->id => [
                        'name' => 'A',
                        'sort_order' => 5,
                        'is_published' => '1',
                    ],
                    $second->id => [
                        'name' => 'B',
                        'sort_order' => 1,
                        'is_published' => '1',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.staff.index'));

        $this->assertDatabaseHas('staff_members', [
            'id' => $second->id,
            'sort_order' => 1,
        ]);
        $this->assertDatabaseHas('staff_members', [
            'id' => $first->id,
            'sort_order' => 2,
        ]);
    }

    public function test_bulk_update_without_new_photo_keeps_existing_photo(): void
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->image('keep.jpg')->store('staff', 'public');
        $staff = $this->createStaff(['photo_path' => $path, 'name' => '旧']);

        $this->actingAs($this->admin())
            ->put(route('admin.staff.bulk-update'), [
                'staff' => [
                    $staff->id => [
                        'name' => '写真維持',
                        'role' => 'スタイリスト',
                        'profile' => 'profile',
                        'sort_order' => 2,
                        'is_published' => '1',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.staff.index'));

        $this->assertDatabaseHas('staff_members', [
            'id' => $staff->id,
            'photo_path' => $path,
            'name' => '写真維持',
            'sort_order' => 1,
        ]);
    }

    public function test_bulk_update_allows_new_staff_without_photo(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.staff.bulk-update'), [
                'new_staff' => [
                    'new_1' => [
                        'name' => '写真なしスタッフ',
                        'role' => 'アシスタント',
                        'sort_order' => 1,
                        'is_published' => '1',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.staff.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('staff_members', [
            'name' => '写真なしスタッフ',
            'role' => 'アシスタント',
            'photo_path' => null,
            'is_published' => true,
        ]);
    }

    public function test_bulk_update_requires_name(): void
    {
        $this->actingAs($this->admin())
            ->from(route('admin.staff.index'))
            ->put(route('admin.staff.bulk-update'), [
                'new_staff' => [
                    'new_1' => [
                        'name' => '',
                        'sort_order' => 1,
                        'is_published' => '1',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.staff.index'))
            ->assertSessionHasErrors('new_staff.new_1.name');

        $this->assertDatabaseMissing('staff_members', [
            'role' => null,
            'sort_order' => 1,
        ]);
    }

    public function test_bulk_update_preserves_input_on_validation_errors(): void
    {
        $this->actingAs($this->admin())
            ->from(route('admin.staff.index'))
            ->put(route('admin.staff.bulk-update'), [
                'new_staff' => [
                    'new_1' => [
                        'name' => '',
                        'role' => '復元役職',
                        'profile' => '復元プロフィール',
                        'sort_order' => 3,
                        'is_published' => '0',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.staff.index'))
            ->assertSessionHasErrors('new_staff.new_1.name');

        $this->assertSame('復元役職', session()->getOldInput('new_staff.new_1.role'));
        $this->assertSame('復元プロフィール', session()->getOldInput('new_staff.new_1.profile'));

        $html = $this->actingAs($this->admin())
            ->get(route('admin.staff.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('name="new_staff[new_1][role]"', $html);
        $this->assertStringContainsString('value="復元役職"', $html);
        $this->assertStringContainsString('復元プロフィール', $html);
        $this->assertStringContainsString('name="new_staff[new_1][is_published]" value="0"', $html);
    }

    public function test_bulk_update_rejects_invalid_photo_mime(): void
    {
        Storage::fake('public');
        $staff = $this->createStaff([
            'photo_path' => UploadedFile::fake()->image('ok.jpg')->store('staff', 'public'),
        ]);

        $this->actingAs($this->admin())
            ->from(route('admin.staff.index'))
            ->put(route('admin.staff.bulk-update'), [
                'staff' => [
                    $staff->id => [
                        'name' => $staff->name,
                        'sort_order' => 1,
                        'is_published' => '1',
                        'photo' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
                    ],
                ],
            ])
            ->assertRedirect(route('admin.staff.index'))
            ->assertSessionHasErrors('staff.'.$staff->id.'.photo');
    }

    public function test_published_staff_appear_on_public_pages_in_sort_order(): void
    {
        $this->createStaff([
            'name' => '公開B',
            'sort_order' => 2,
            'is_published' => true,
        ]);
        $this->createStaff([
            'name' => '公開A',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $this->createStaff([
            'name' => '非公開',
            'sort_order' => 0,
            'is_published' => false,
        ]);

        $html = $this->get(route('staff'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('公開A', $html);
        $this->assertStringContainsString('公開B', $html);
        $this->assertStringNotContainsString('非公開', $html);
        $this->assertTrue(strpos($html, '公開A') < strpos($html, '公開B'));
    }
}
