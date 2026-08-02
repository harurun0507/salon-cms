<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserBulkSaveTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $overrides = []): User
    {
        return User::factory()->admin()->create($overrides);
    }

    private function editor(array $overrides = []): User
    {
        return User::factory()->editor()->create($overrides);
    }

    public function test_users_index_shows_card_bulk_save_ui(): void
    {
        $admin = $this->admin(['name' => '管理者太郎']);

        $html = $this->actingAs($admin)
            ->get(route('admin.system.users'))
            ->assertOk()
            ->assertDontSee('この機能は現在準備中です。', false)
            ->getContent();

        $this->assertStringContainsString('id="users-bulk-form"', $html);
        $this->assertStringContainsString('grid grid-cols-1 gap-4 md:grid-cols-2', $html);
        $this->assertStringContainsString('管理ユーザーを追加', $html);
        $this->assertStringContainsString('管理者太郎', $html);
        $this->assertStringContainsString('data-admin-confirm-trigger', $html);
        $this->assertStringContainsString('管理ユーザーの変更内容を保存します。よろしいですか？', $html);
        $this->assertStringContainsString('保存する', $html);
        $this->assertStringContainsString('admin-required-badge', $html);
        $this->assertStringContainsString('最終ログイン', $html);
        $this->assertStringContainsString('aria-label="権限"', $html);
        $this->assertStringContainsString('aria-label="状態"', $html);
        $this->assertStringContainsString('data-user-self', $html);
        $this->assertStringNotContainsString('data-user-drag-handle', $html);
    }

    public function test_bulk_update_creates_updates_and_hard_deletes_never_logged_in_user(): void
    {
        $admin = $this->admin(['name' => 'Admin', 'email' => 'admin@example.com']);
        $remove = $this->editor([
            'name' => '削除対象',
            'email' => 'remove@example.com',
            'last_login_at' => null,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.system.users.update'), [
                'users' => [
                    $admin->id => [
                        'name' => 'Admin更新',
                        'email' => 'admin@example.com',
                        'role' => 'admin',
                        'is_active' => '1',
                    ],
                ],
                'new_users' => [
                    'new_1' => [
                        'name' => '新規編集者',
                        'email' => 'editor-new@example.com',
                        'role' => 'editor',
                        'is_active' => '1',
                        'password' => 'password123',
                        'password_confirmation' => 'password123',
                    ],
                ],
                'deleted_ids' => [$remove->id],
            ])
            ->assertRedirect(route('admin.system.users'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'name' => 'Admin更新',
            'role' => 'admin',
            'is_active' => true,
        ]);
        $this->assertDatabaseMissing('users', ['id' => $remove->id]);
        $this->assertDatabaseHas('users', [
            'email' => 'editor-new@example.com',
            'name' => '新規編集者',
            'role' => 'editor',
            'is_active' => true,
        ]);

        $created = User::query()->where('email', 'editor-new@example.com')->first();
        $this->assertTrue(Hash::check('password123', $created->password));
    }

    public function test_deleting_logged_in_user_soft_disables_instead_of_hard_delete(): void
    {
        $admin = $this->admin();
        $editor = $this->editor([
            'email' => 'logged@example.com',
            'last_login_at' => now()->subDay(),
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.system.users.update'), [
                'users' => [
                    $admin->id => [
                        'name' => $admin->name,
                        'email' => $admin->email,
                        'role' => 'admin',
                        'is_active' => '1',
                    ],
                ],
                'deleted_ids' => [$editor->id],
            ])
            ->assertRedirect(route('admin.system.users'));

        $this->assertDatabaseHas('users', [
            'id' => $editor->id,
            'is_active' => false,
        ]);
    }

    public function test_bulk_update_rejects_duplicate_email(): void
    {
        $admin = $this->admin(['email' => 'admin@example.com']);
        $other = $this->editor(['email' => 'other@example.com']);

        $this->actingAs($admin)
            ->from(route('admin.system.users'))
            ->put(route('admin.system.users.update'), [
                'users' => [
                    $admin->id => [
                        'name' => $admin->name,
                        'email' => 'admin@example.com',
                        'role' => 'admin',
                        'is_active' => '1',
                    ],
                    $other->id => [
                        'name' => $other->name,
                        'email' => 'admin@example.com',
                        'role' => 'editor',
                        'is_active' => '1',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.system.users'))
            ->assertSessionHasErrors('users.'.$other->id.'.email');
    }

    public function test_password_change_requires_confirmation_and_min_length(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from(route('admin.system.users'))
            ->put(route('admin.system.users.update'), [
                'users' => [
                    $admin->id => [
                        'name' => $admin->name,
                        'email' => $admin->email,
                        'role' => 'admin',
                        'is_active' => '1',
                        'password' => 'short',
                        'password_confirmation' => 'short',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.system.users'))
            ->assertSessionHasErrors('users.'.$admin->id.'.password');

        $this->actingAs($admin)
            ->from(route('admin.system.users'))
            ->put(route('admin.system.users.update'), [
                'users' => [
                    $admin->id => [
                        'name' => $admin->name,
                        'email' => $admin->email,
                        'role' => 'admin',
                        'is_active' => '1',
                        'password' => 'password123',
                        'password_confirmation' => 'mismatch',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.system.users'))
            ->assertSessionHasErrors('users.'.$admin->id.'.password');
    }

    public function test_blank_password_keeps_existing_password(): void
    {
        $admin = $this->admin();
        $originalHash = $admin->password;

        $this->actingAs($admin)
            ->put(route('admin.system.users.update'), [
                'users' => [
                    $admin->id => [
                        'name' => '名前だけ更新',
                        'email' => $admin->email,
                        'role' => 'admin',
                        'is_active' => '1',
                        'password' => '',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.system.users'));

        $admin->refresh();
        $this->assertSame('名前だけ更新', $admin->name);
        $this->assertSame($originalHash, $admin->password);
    }

    public function test_cannot_delete_or_disable_self(): void
    {
        $admin = $this->admin();
        $other = $this->admin(['email' => 'other-admin@example.com']);

        $this->actingAs($admin)
            ->from(route('admin.system.users'))
            ->put(route('admin.system.users.update'), [
                'users' => [
                    $admin->id => [
                        'name' => $admin->name,
                        'email' => $admin->email,
                        'role' => 'admin',
                        'is_active' => '0',
                    ],
                    $other->id => [
                        'name' => $other->name,
                        'email' => $other->email,
                        'role' => 'admin',
                        'is_active' => '1',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.system.users'))
            ->assertSessionHasErrors('users.'.$admin->id.'.is_active');

        $this->actingAs($admin)
            ->from(route('admin.system.users'))
            ->put(route('admin.system.users.update'), [
                'users' => [
                    $other->id => [
                        'name' => $other->name,
                        'email' => $other->email,
                        'role' => 'admin',
                        'is_active' => '1',
                    ],
                ],
                'deleted_ids' => [$admin->id],
            ])
            ->assertRedirect(route('admin.system.users'))
            ->assertSessionHasErrors('deleted_ids');

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'is_active' => true,
        ]);
    }

    public function test_cannot_demote_or_disable_last_active_admin(): void
    {
        $admin = $this->admin();
        $editor = $this->editor();

        $this->actingAs($admin)
            ->from(route('admin.system.users'))
            ->put(route('admin.system.users.update'), [
                'users' => [
                    $admin->id => [
                        'name' => $admin->name,
                        'email' => $admin->email,
                        'role' => 'editor',
                        'is_active' => '1',
                    ],
                    $editor->id => [
                        'name' => $editor->name,
                        'email' => $editor->email,
                        'role' => 'editor',
                        'is_active' => '1',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.system.users'))
            ->assertSessionHasErrors();

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    public function test_editor_gets_403_on_system_pages_and_nav_hides_system(): void
    {
        $editor = $this->editor();

        $this->actingAs($editor)->get(route('admin.system.users'))->assertForbidden();
        $this->actingAs($editor)->get(route('admin.system.seo'))->assertForbidden();
        $this->actingAs($editor)->get(route('admin.system.analytics'))->assertForbidden();
        $this->actingAs($editor)->get(route('admin.system.design'))->assertForbidden();

        $this->actingAs($editor)->get(route('admin.news.index'))->assertOk();
        $this->actingAs($editor)->get(route('admin.settings.edit'))->assertOk();
        $this->actingAs($editor)->get(route('admin.home.hero'))->assertOk();
        $this->actingAs($editor)->get(route('admin.dashboard'))->assertOk();

        $html = $this->actingAs($editor)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('管理ユーザー', $html);
        $this->assertStringNotContainsString('data-nav-key="system"', $html);
        $this->assertStringContainsString('お知らせ', $html);
        $this->assertStringContainsString('基本情報', $html);
    }

    public function test_admin_can_access_all_admin_areas(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('admin.news.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.system.users'))->assertOk();
        $this->actingAs($admin)->get(route('admin.system.seo'))->assertOk();
        $this->actingAs($admin)->get(route('admin.system.analytics'))->assertOk();
        $this->actingAs($admin)->get(route('admin.system.design'))->assertOk();
    }

    public function test_inactive_user_cannot_login_with_generic_message(): void
    {
        $user = $this->admin([
            'email' => 'inactive@example.com',
            'password' => 'password',
            'is_active' => false,
        ]);

        $this->from(route('admin.login'))
            ->post(route('admin.login'), [
                'email' => 'inactive@example.com',
                'password' => 'password',
            ])
            ->assertRedirect(route('admin.login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertSame(
            'メールアドレスまたはパスワードが正しくありません。',
            session('errors')->first('email')
        );
        $this->assertNull($user->fresh()->last_login_at);
    }

    public function test_successful_login_updates_last_login_at(): void
    {
        $user = $this->admin([
            'email' => 'login@example.com',
            'password' => 'password',
            'last_login_at' => null,
        ]);

        $this->post(route('admin.login'), [
            'email' => 'login@example.com',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_validation_errors_restore_old_input_for_new_users(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from(route('admin.system.users'))
            ->put(route('admin.system.users.update'), [
                'users' => [
                    $admin->id => [
                        'name' => $admin->name,
                        'email' => $admin->email,
                        'role' => 'admin',
                        'is_active' => '1',
                    ],
                ],
                'new_users' => [
                    'new_1' => [
                        'name' => '復元ユーザー',
                        'email' => 'restore@example.com',
                        'role' => 'editor',
                        'is_active' => '0',
                        'password' => '',
                        'password_confirmation' => '',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.system.users'))
            ->assertSessionHasErrors('new_users.new_1.password');

        $this->assertSame('復元ユーザー', session()->getOldInput('new_users.new_1.name'));
        $this->assertSame('restore@example.com', session()->getOldInput('new_users.new_1.email'));

        $html = $this->actingAs($admin)
            ->get(route('admin.system.users'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('name="new_users[new_1][name]"', $html);
        $this->assertStringContainsString('value="復元ユーザー"', $html);
        $this->assertStringContainsString('value="restore@example.com"', $html);
    }
}
