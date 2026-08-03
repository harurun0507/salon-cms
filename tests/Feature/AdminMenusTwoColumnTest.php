<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\MenuCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class AdminMenusTwoColumnTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    public function test_menus_index_renders_two_column_workspace_and_keeps_all_panels_in_dom(): void
    {
        $first = MenuCategory::query()->create(['name' => 'カット', 'sort_order' => 1]);
        $second = MenuCategory::query()->create(['name' => 'カラー', 'sort_order' => 2]);
        $menu = Menu::query()->create([
            'menu_category_id' => $first->id,
            'name' => 'カットベーシック',
            'price' => '¥5,000',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.menus.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-menus-workspace', $html);
        $this->assertStringContainsString('data-category-aside', $html);
        $this->assertStringContainsString('data-category-tabs', $html);
        $this->assertStringContainsString('data-select-category="'.$first->id.'"', $html);
        $this->assertStringContainsString('data-select-category="'.$second->id.'"', $html);
        $this->assertStringContainsString('data-category-panel="'.$first->id.'"', $html);
        $this->assertStringContainsString('data-category-panel="'.$second->id.'"', $html);
        $this->assertStringContainsString('data-category-name-input="'.$first->id.'"', $html);
        $this->assertStringContainsString('name="categories['.$first->id.'][name]"', $html);
        $this->assertStringContainsString('name="categories['.$first->id.'][sort_order]"', $html);
        $this->assertStringContainsString('data-category-sort-order', $html);
        $this->assertStringContainsString('type="number"', $html);
        $this->assertMatchesRegularExpression(
            '/type="number"[^>]*name="categories\['.$first->id.'\]\[sort_order\]"|name="categories\['.$first->id.'\]\[sort_order\]"[^>]*type="number"/',
            $html
        );
        $this->assertStringContainsString('for="category-sort-'.$first->id.'"', $html);
        $this->assertStringContainsString('flex flex-col items-start gap-0.5', $html);
        $this->assertStringContainsString('data-menu-drag-handle', $html);
        $this->assertStringContainsString('data-menu-sort-order', $html);
        $this->assertStringContainsString('menu-col-handle', $html);
        $this->assertStringContainsString('menu-col-sort', $html);
        $this->assertStringContainsString('>表示順</th>', $html);
        $this->assertStringContainsString('whitespace-nowrap">操作</th>', $html);
        $this->assertStringContainsString('.menu-col-actions { width: 4.25rem; }', $html);
        $this->assertStringContainsString('name="menus[', $html);
        $this->assertStringContainsString('[is_published]"', $html);
        $this->assertStringContainsString('admin-switch--compact', $html);
        $this->assertStringContainsString('admin-switch-input', $html);
        $this->assertStringContainsString('data-published-checkbox', $html);
        $this->assertStringContainsString('data-published-text', $html);
        $this->assertStringContainsString('name="menus['.$menu->id.'][is_published]"', $html);
        $this->assertStringNotContainsString('menu-published-checkbox', $html);
        $this->assertDoesNotMatchRegularExpression(
            '/name="menus\['.$menu->id.'\]\[is_published\]"[^>]*type="radio"|type="radio"[^>]*name="menus\['.$menu->id.'\]\[is_published\]"/',
            $html
        );
        $this->assertDoesNotMatchRegularExpression('/<select[^>]*name="menus\[[^\]]+\]\[is_published\]"/', $html);
        $this->assertStringNotContainsString('>公開</option>', $html);
        $this->assertStringNotContainsString('>非公開</option>', $html);
        $this->assertStringContainsString('data-initial-category-id="'.$first->id.'"', $html);
        $this->assertStringContainsString('selectCategory', $html);
        $this->assertStringContainsString("addEventListener('invalid'", $html);
        $this->assertStringContainsString('このカテゴリにメニューはありません。', $html);
        $this->assertStringContainsString('data-admin-confirm-trigger', $html);
        $this->assertStringContainsString('id="menus-bulk-form"', $html);
        $this->assertStringNotContainsString('id="menu-add-modal"', $html);
        $this->assertStringNotContainsString('id="menu-detail-modal"', $html);
        $this->assertStringContainsString('メニュー ', $html);
        $this->assertStringContainsString('name="selected_category_id"', $html);
        $this->assertStringContainsString('id="new-menu-row-template"', $html);
        $this->assertStringContainsString('addInlineMenu', $html);
        $this->assertStringContainsString('table-fixed', $html);
        $this->assertStringContainsString('menu-description-textarea', $html);
        $this->assertStringContainsString('menu-col-actions', $html);
        $this->assertStringContainsString('placeholder="説明（任意）"', $html);
        $this->assertStringNotContainsString('data-open-detail', $html);
        $this->assertStringNotContainsString('menu-description-preview-', $html);
        $this->assertStringContainsString("target.tagName === 'TEXTAREA'", $html);
        $this->assertStringContainsString('.menu-list-row:hover', $html);
        $this->assertStringContainsString('.menu-list-row.is-selected', $html);
        $this->assertStringContainsString('.menu-list-row:hover .category-delete-x', $html);
        $this->assertStringContainsString('.menu-list-row.is-selected .category-delete-x', $html);
        $this->assertStringContainsString('function selectMenu', $html);
        $this->assertStringContainsString("closest('[data-menu-row]')", $html);
    }

    public function test_existing_menu_description_is_inline_textarea(): void
    {
        $category = MenuCategory::query()->create(['name' => 'カット', 'sort_order' => 1]);
        $longDescription = str_repeat('長い説明テキストです。', 20);
        $menu = Menu::query()->create([
            'menu_category_id' => $category->id,
            'name' => 'カットベーシック',
            'price' => '¥5,000',
            'description' => $longDescription,
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.menus.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="menu-description-'.$menu->id.'"', $html);
        $this->assertStringContainsString('name="menus['.$menu->id.'][description]"', $html);
        $this->assertStringContainsString('<textarea', $html);
        $this->assertStringContainsString('rows="3"', $html);
        $this->assertStringContainsString('menu-description-textarea', $html);
        $this->assertStringContainsString($longDescription, $html);
        $this->assertStringNotContainsString('data-open-detail', $html);
        $this->assertStringNotContainsString('menu-description-preview-', $html);
        $this->assertStringNotContainsString('id="menu-detail-modal"', $html);
        $this->assertStringNotContainsString('max-w-full truncate', $html);
    }

    public function test_toolbar_has_no_category_add_modal_trigger_and_left_list_has_add_button(): void
    {
        MenuCategory::query()->create(['name' => 'カット', 'sort_order' => 1]);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.menus.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('id="category-add-modal"', $html);
        $this->assertStringNotContainsString('data-open-modal="category-add-modal"', $html);
        $this->assertStringContainsString('data-add-category', $html);
        $this->assertStringContainsString('＋ カテゴリを追加', $html);
        $this->assertStringNotContainsString('data-menu-add-hint', $html);
        $this->assertStringNotContainsString('カテゴリを一括保存後にメニューを追加できます', $html);
        $this->assertStringContainsString('メニューを追加してみましょう。', $html);
        $this->assertStringContainsString('data-menu-add-btn', $html);
        $this->assertStringContainsString('data-menu-list-header', $html);
        $this->assertStringNotContainsString("showToast('カテゴリを一括保存するとメニューを追加できます。'", $html);
        $this->assertStringNotContainsString('カテゴリを一括保存するとメニューを追加できます。', $html);
        $this->assertStringNotContainsString('data-open-modal="menu-add-modal"', $html);
    }

    public function test_menu_add_lives_in_panel_not_sticky_toolbar(): void
    {
        $withMenus = MenuCategory::query()->create(['name' => 'カット', 'sort_order' => 1]);
        Menu::query()->create([
            'menu_category_id' => $withMenus->id,
            'name' => 'カットベーシック',
            'price' => '¥5,000',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        MenuCategory::query()->create(['name' => 'カラー', 'sort_order' => 2]);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.menus.index'))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/admin-save-bar sticky top-0[\s\S]*?data-bulk-save-btn[\s\S]*?<\/div>\s*<\/div>/',
            $html
        );
        preg_match('/admin-save-bar sticky top-0[\s\S]*?<\/div>\s*<\/div>/', $html, $toolbar);
        $this->assertNotEmpty($toolbar);
        $this->assertStringNotContainsString('data-menu-add-btn', $toolbar[0]);
        $this->assertStringNotContainsString('data-menu-add-hint', $toolbar[0]);
        $this->assertStringContainsString('id="menus-bulk-form"', $html);
        $this->assertStringContainsString('data-menu-list-header', $html);
        $this->assertStringContainsString('data-menu-add-btn', $html);
        $this->assertStringContainsString('このカテゴリにメニューはありません。', $html);
    }

    public function test_empty_categories_shows_workspace_with_inline_add(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('admin.menus.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-menus-workspace', $html);
        $this->assertStringContainsString('data-menus-empty-workspace', $html);
        $this->assertStringContainsString('カテゴリがありません。まずカテゴリを追加してください。', $html);
        $this->assertStringContainsString('id="menus-bulk-form"', $html);
        $this->assertStringContainsString('data-add-category', $html);
        $this->assertStringNotContainsString('data-open-modal="category-add-modal"', $html);
    }

    public function test_existing_category_delete_trigger_still_present(): void
    {
        $category = MenuCategory::query()->create(['name' => 'カット', 'sort_order' => 1]);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.menus.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-admin-delete-trigger', $html);
        $this->assertStringContainsString('data-delete-form="delete-category-'.$category->id.'"', $html);
        $this->assertStringContainsString('aria-label="カテゴリを削除"', $html);
        $this->assertStringContainsString('category-delete-x', $html);
    }

    public function test_store_category_flashes_selected_category_id(): void
    {
        $response = $this->actingAs($this->admin())
            ->post(route('admin.menus.categories.store'), [
                'name' => 'トリートメント',
                'sort_order' => 1,
            ]);

        $category = MenuCategory::query()->where('name', 'トリートメント')->first();
        $this->assertNotNull($category);

        $response->assertRedirect(route('admin.menus.index'));
        $response->assertSessionHas('success', 'カテゴリを登録しました。');
        $response->assertSessionHas('selected_category_id', $category->id);

        $html = $this->actingAs($this->admin())
            ->withSession([
                'success' => 'カテゴリを登録しました。',
                'selected_category_id' => $category->id,
            ])
            ->get(route('admin.menus.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-initial-category-id="'.$category->id.'"', $html);
    }

    public function test_store_menu_flashes_selected_category_id_for_destination(): void
    {
        $category = MenuCategory::query()->create(['name' => 'カット', 'sort_order' => 1]);
        MenuCategory::query()->create(['name' => 'カラー', 'sort_order' => 2]);

        $response = $this->actingAs($this->admin())
            ->post(route('admin.menus.store', $category), [
                'name' => 'カットスペシャル',
                'price' => '¥7,000',
                'sort_order' => 1,
                'is_published' => '1',
            ]);

        $response->assertRedirect(route('admin.menus.index'));
        $response->assertSessionHas('success', 'メニューを登録しました。');
        $response->assertSessionHas('selected_category_id', $category->id);
    }

    public function test_validation_error_category_is_preferred_over_first_when_no_flash(): void
    {
        $first = MenuCategory::query()->create(['name' => 'カット', 'sort_order' => 1]);
        $second = MenuCategory::query()->create(['name' => 'カラー', 'sort_order' => 2]);
        $menu = Menu::query()->create([
            'menu_category_id' => $second->id,
            'name' => 'カラーベーシック',
            'price' => '¥8,000',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $errors = new ViewErrorBag;
        $errors->put('default', new MessageBag([
            'menus.'.$menu->id.'.name' => ['メニュー名は必須です。'],
        ]));

        $html = $this->actingAs($this->admin())
            ->withSession(['errors' => $errors])
            ->get(route('admin.menus.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-initial-category-id="'.$second->id.'"', $html);
        $this->assertStringNotContainsString('data-initial-category-id="'.$first->id.'"', $html);
    }

    public function test_session_selected_category_takes_priority_over_validation_error(): void
    {
        $first = MenuCategory::query()->create(['name' => 'カット', 'sort_order' => 1]);
        $second = MenuCategory::query()->create(['name' => 'カラー', 'sort_order' => 2]);
        $menu = Menu::query()->create([
            'menu_category_id' => $second->id,
            'name' => 'カラーベーシック',
            'price' => '¥8,000',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $errors = new ViewErrorBag;
        $errors->put('default', new MessageBag([
            'menus.'.$menu->id.'.name' => ['メニュー名は必須です。'],
        ]));

        $html = $this->actingAs($this->admin())
            ->withSession([
                'selected_category_id' => $first->id,
                'errors' => $errors,
            ])
            ->get(route('admin.menus.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-initial-category-id="'.$first->id.'"', $html);
    }

    public function test_bulk_update_still_works_with_nested_names(): void
    {
        $category = MenuCategory::query()->create(['name' => 'カット', 'sort_order' => 1]);
        $menu = Menu::query()->create([
            'menu_category_id' => $category->id,
            'name' => 'カットベーシック',
            'price' => '¥5,000',
            'sort_order' => 1,
            'is_published' => true,
            'description' => '旧説明',
        ]);

        $this->actingAs($this->admin())
            ->put(route('admin.menus.bulk-update'), [
                'selected_category_id' => $category->id,
                'categories' => [
                    $category->id => [
                        'name' => 'カット更新',
                        'sort_order' => 3,
                    ],
                ],
                'menus' => [
                    $menu->id => [
                        'name' => 'カット更新メニュー',
                        'price' => '¥5,500',
                        'sort_order' => 2,
                        'is_published' => '0',
                        'description' => '新説明',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.menus.index'))
            ->assertSessionHas('success', 'メニュー情報を一括保存しました。')
            ->assertSessionHas('selected_category_id', $category->id);

        $this->assertDatabaseHas('menu_categories', [
            'id' => $category->id,
            'name' => 'カット更新',
            'sort_order' => 3,
        ]);
        $this->assertDatabaseHas('menus', [
            'id' => $menu->id,
            'name' => 'カット更新メニュー',
            'price' => '¥5,500',
            'sort_order' => 2,
            'is_published' => false,
            'description' => '新説明',
        ]);
    }

    public function test_bulk_update_creates_new_category_from_new_key(): void
    {
        $response = $this->actingAs($this->admin())
            ->put(route('admin.menus.bulk-update'), [
                'categories' => [
                    'new_1' => [
                        'name' => 'パーマ',
                        'sort_order' => 2,
                    ],
                ],
            ]);

        $response->assertRedirect(route('admin.menus.index'))
            ->assertSessionHas('success', 'メニュー情報を一括保存しました。');

        $created = MenuCategory::query()->where('name', 'パーマ')->first();
        $this->assertNotNull($created);
        $this->assertSame(2, (int) $created->sort_order);
        $response->assertSessionHas('selected_category_id', $created->id);
    }

    public function test_bulk_update_new_category_flashes_last_created_id(): void
    {
        $response = $this->actingAs($this->admin())
            ->put(route('admin.menus.bulk-update'), [
                'categories' => [
                    'new_1' => [
                        'name' => 'パーマ',
                        'sort_order' => 1,
                    ],
                    'new_2' => [
                        'name' => 'ヘッドスパ',
                        'sort_order' => 2,
                    ],
                ],
            ]);

        $last = MenuCategory::query()->where('name', 'ヘッドスパ')->first();
        $this->assertNotNull($last);
        $this->assertDatabaseHas('menu_categories', ['name' => 'パーマ']);
        $response->assertSessionHas('selected_category_id', $last->id);
    }

    public function test_bulk_update_creates_new_and_updates_existing_together(): void
    {
        $existing = MenuCategory::query()->create(['name' => 'カット', 'sort_order' => 1]);

        $this->actingAs($this->admin())
            ->put(route('admin.menus.bulk-update'), [
                'selected_category_id' => $existing->id,
                'categories' => [
                    $existing->id => [
                        'name' => 'カット改',
                        'sort_order' => 1,
                    ],
                    'new_1' => [
                        'name' => 'カラー新規',
                        'sort_order' => 2,
                    ],
                ],
            ])
            ->assertRedirect(route('admin.menus.index'));

        $this->assertDatabaseHas('menu_categories', [
            'id' => $existing->id,
            'name' => 'カット改',
        ]);
        $created = MenuCategory::query()->where('name', 'カラー新規')->first();
        $this->assertNotNull($created);
        $this->assertEquals($created->id, session('selected_category_id'));
    }

    public function test_bulk_validation_failure_restores_old_new_category_and_selects_it(): void
    {
        MenuCategory::query()->create(['name' => 'カット', 'sort_order' => 1]);

        $response = $this->actingAs($this->admin())
            ->from(route('admin.menus.index'))
            ->put(route('admin.menus.bulk-update'), [
                'categories' => [
                    'new_1' => [
                        'name' => '',
                        'sort_order' => 1,
                    ],
                ],
            ]);

        $response->assertRedirect(route('admin.menus.index'));
        $response->assertSessionHasErrors('categories.new_1.name');

        $html = $this->actingAs($this->admin())
            ->withSession([
                '_old_input' => [
                    'categories' => [
                        'new_1' => [
                            'name' => '',
                            'sort_order' => 1,
                        ],
                    ],
                ],
                'errors' => tap(new ViewErrorBag, function (ViewErrorBag $bag) {
                    $bag->put('default', new MessageBag([
                        'categories.new_1.name' => ['カテゴリ名は必須です。'],
                    ]));
                }),
            ])
            ->get(route('admin.menus.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('name="categories[new_1][name]"', $html);
        $this->assertStringContainsString('data-category-panel="new_1"', $html);
        $this->assertStringContainsString('data-initial-category-id="new_1"', $html);
        $this->assertStringContainsString('data-category-row="new_1"', $html);
    }

    public function test_bulk_update_preserves_selected_when_only_existing_updated(): void
    {
        $first = MenuCategory::query()->create(['name' => 'カット', 'sort_order' => 1]);
        $second = MenuCategory::query()->create(['name' => 'カラー', 'sort_order' => 2]);

        $this->actingAs($this->admin())
            ->put(route('admin.menus.bulk-update'), [
                'selected_category_id' => $second->id,
                'categories' => [
                    $first->id => ['name' => 'カット', 'sort_order' => 1],
                    $second->id => ['name' => 'カラー更新', 'sort_order' => 2],
                ],
            ])
            ->assertRedirect(route('admin.menus.index'))
            ->assertSessionHas('selected_category_id', $second->id);
    }

    public function test_menu_add_btn_has_no_open_modal_for_add(): void
    {
        $category = MenuCategory::query()->create(['name' => 'カット', 'sort_order' => 1]);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.menus.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-menu-add-btn', $html);
        $this->assertStringNotContainsString('data-open-modal="menu-add-modal"', $html);
        $this->assertStringContainsString('data-menu-empty', $html);
        $this->assertStringContainsString('data-menu-table', $html);
        $this->assertStringContainsString('data-next-new-menu-index', $html);
        $this->assertDatabaseHas('menu_categories', ['id' => $category->id]);
    }

    public function test_existing_menu_delete_trigger_still_present_without_detail_edit(): void
    {
        $category = MenuCategory::query()->create(['name' => 'カット', 'sort_order' => 1]);
        $menu = Menu::query()->create([
            'menu_category_id' => $category->id,
            'name' => 'カットベーシック',
            'price' => '¥5,000',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.menus.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('data-open-detail', $html);
        $this->assertStringNotContainsString('id="menu-detail-modal"', $html);
        $this->assertStringContainsString('data-delete-form="delete-menu-'.$menu->id.'"', $html);
        $this->assertStringContainsString('id="delete-menu-'.$menu->id.'"', $html);
        $this->assertStringContainsString('aria-label="メニューを削除"', $html);
        $this->assertTrue((bool) preg_match(
            '/<tr[^>]*data-menu-row="'.preg_quote((string) $menu->id, '/').'"[^>]*>(.*?)<\/tr>/s',
            $html,
            $menuRowMatches
        ));
        $this->assertStringContainsString('class="category-delete-x"', $menuRowMatches[1]);
        $this->assertStringNotContainsString('!opacity-100', $menuRowMatches[1]);
    }

    public function test_bulk_update_creates_new_menu_with_category_id(): void
    {
        $category = MenuCategory::query()->create(['name' => 'カット', 'sort_order' => 1]);

        $this->actingAs($this->admin())
            ->put(route('admin.menus.bulk-update'), [
                'selected_category_id' => $category->id,
                'categories' => [
                    $category->id => [
                        'name' => 'カット',
                        'sort_order' => 1,
                    ],
                ],
                'menus' => [
                    'new_menu_1' => [
                        'category_id' => $category->id,
                        'name' => 'カット新規',
                        'price' => '¥6,000',
                        'sort_order' => 1,
                        'is_published' => '1',
                        'description' => '説明',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.menus.index'))
            ->assertSessionHas('success', 'メニュー情報を一括保存しました。');

        $this->assertDatabaseHas('menus', [
            'menu_category_id' => $category->id,
            'name' => 'カット新規',
            'price' => '¥6,000',
            'sort_order' => 1,
            'is_published' => true,
            'description' => '説明',
        ]);
    }

    public function test_bulk_update_creates_new_category_and_linked_new_menu(): void
    {
        $response = $this->actingAs($this->admin())
            ->put(route('admin.menus.bulk-update'), [
                'selected_category_id' => 'new_1',
                'categories' => [
                    'new_1' => [
                        'name' => 'パーマ',
                        'sort_order' => 1,
                    ],
                ],
                'menus' => [
                    'new_menu_1' => [
                        'category_id' => 'new_1',
                        'name' => 'デジタルパーマ',
                        'price' => '¥12,000',
                        'sort_order' => 1,
                        'is_published' => '1',
                        'description' => '',
                    ],
                ],
            ]);

        $response->assertRedirect(route('admin.menus.index'))
            ->assertSessionHas('success', 'メニュー情報を一括保存しました。');

        $created = MenuCategory::query()->where('name', 'パーマ')->first();
        $this->assertNotNull($created);
        $response->assertSessionHas('selected_category_id', $created->id);

        $this->assertDatabaseHas('menus', [
            'menu_category_id' => $created->id,
            'name' => 'デジタルパーマ',
            'price' => '¥12,000',
            'sort_order' => 1,
        ]);
    }

    public function test_bulk_validation_failure_restores_old_new_menu_rows(): void
    {
        $category = MenuCategory::query()->create(['name' => 'カット', 'sort_order' => 1]);

        $response = $this->actingAs($this->admin())
            ->from(route('admin.menus.index'))
            ->put(route('admin.menus.bulk-update'), [
                'selected_category_id' => $category->id,
                'categories' => [
                    $category->id => [
                        'name' => 'カット',
                        'sort_order' => 1,
                    ],
                ],
                'menus' => [
                    'new_menu_1' => [
                        'category_id' => $category->id,
                        'name' => '',
                        'price' => '¥1,000',
                        'sort_order' => 1,
                        'is_published' => '1',
                        'description' => '復元確認',
                    ],
                ],
            ]);

        $response->assertRedirect(route('admin.menus.index'));
        $response->assertSessionHasErrors('menus.new_menu_1.name');

        $html = $this->actingAs($this->admin())
            ->withSession([
                '_old_input' => [
                    'selected_category_id' => $category->id,
                    'categories' => [
                        $category->id => [
                            'name' => 'カット',
                            'sort_order' => 1,
                        ],
                    ],
                    'menus' => [
                        'new_menu_1' => [
                            'category_id' => $category->id,
                            'name' => '',
                            'price' => '¥1,000',
                            'sort_order' => 1,
                            'is_published' => '1',
                            'description' => '復元確認',
                        ],
                    ],
                ],
                'errors' => tap(new ViewErrorBag, function (ViewErrorBag $bag) {
                    $bag->put('default', new MessageBag([
                        'menus.new_menu_1.name' => ['メニュー名は必須です。'],
                    ]));
                }),
            ])
            ->get(route('admin.menus.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('name="menus[new_menu_1][name]"', $html);
        $this->assertStringContainsString('name="menus[new_menu_1][category_id]"', $html);
        $this->assertStringContainsString('name="menus[new_menu_1][description]"', $html);
        $this->assertStringContainsString('>復元確認</textarea>', $html);
        $this->assertStringContainsString('data-new-menu="new_menu_1"', $html);
        $this->assertStringContainsString('data-initial-category-id="'.$category->id.'"', $html);
        $this->assertStringContainsString('data-discard-menu="new_menu_1"', $html);
        $this->assertStringContainsString('メニュー追加を取り消す', $html);
        $this->assertDoesNotMatchRegularExpression(
            '/data-discard-menu="new_menu_1"[^>]*class="category-delete-x !opacity-100"|class="category-delete-x !opacity-100"[^>]*data-discard-menu="new_menu_1"/',
            $html
        );
    }

    public function test_category_list_has_drag_handles_for_existing_and_new_rows(): void
    {
        $first = MenuCategory::query()->create(['name' => 'カット', 'sort_order' => 1]);
        $second = MenuCategory::query()->create(['name' => 'カラー', 'sort_order' => 2]);

        $html = $this->actingAs($this->admin())
            ->withSession([
                '_old_input' => [
                    'categories' => [
                        $first->id => ['name' => 'カット', 'sort_order' => 1],
                        $second->id => ['name' => 'カラー', 'sort_order' => 2],
                        'new_1' => ['name' => 'パーマ', 'sort_order' => 3],
                    ],
                ],
                'errors' => tap(new ViewErrorBag, function (ViewErrorBag $bag) {
                    $bag->put('default', new MessageBag([
                        'categories.new_1.name' => ['検証用'],
                    ]));
                }),
            ])
            ->get(route('admin.menus.index'))
            ->assertOk()
            ->getContent();

        preg_match('/data-category-list[\s>][\s\S]*?<\/ul>/', $html, $listHtml);
        $this->assertNotEmpty($listHtml);
        $this->assertSame(3, substr_count($listHtml[0], 'data-category-drag-handle'));
        $this->assertStringContainsString('aria-label="カテゴリを並び替え"', $listHtml[0]);
        $this->assertStringContainsString('title="ドラッグして並び替え"', $listHtml[0]);
        $this->assertStringContainsString('draggable="true"', $listHtml[0]);
        $this->assertStringContainsString('class="category-drag-handle"', $listHtml[0]);
        $this->assertStringContainsString('data-category-row="'.$first->id.'"', $listHtml[0]);
        $this->assertStringContainsString('data-category-row="new_1"', $listHtml[0]);
        $this->assertStringContainsString('data-new-category="new_1"', $listHtml[0]);
        $this->assertMatchesRegularExpression(
            '/name="categories\['.$first->id.'\]\[sort_order\]"/',
            $listHtml[0]
        );
        $this->assertStringContainsString('name="categories[new_1][sort_order]"', $listHtml[0]);
        $this->assertStringContainsString('type="number"', $listHtml[0]);
        $this->assertStringContainsString('>表示順</label>', $listHtml[0]);
        $this->assertStringContainsString('flex flex-col items-start gap-0.5', $listHtml[0]);
        $this->assertStringNotContainsString('flex flex-col items-center gap-0.5', $listHtml[0]);

        preg_match('/id="new-category-row-template"[\s\S]*?<\/template>/', $html, $template);
        $this->assertNotEmpty($template);
        $this->assertStringContainsString('data-category-drag-handle', $template[0]);
        $this->assertStringContainsString('aria-label="カテゴリを並び替え"', $template[0]);
        $this->assertStringContainsString('name="categories[__ID__][sort_order]"', $template[0]);
        $this->assertStringContainsString('type="number"', $template[0]);
        $this->assertStringContainsString('flex flex-col items-start gap-0.5', $template[0]);
    }

    public function test_category_dnd_script_renumbers_sort_order_and_compacts_on_discard(): void
    {
        MenuCategory::query()->create(['name' => 'カット', 'sort_order' => 1]);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.menus.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('function renumberCategorySortOrders', $html);
        $this->assertStringContainsString('function initCategoryDragDrop', $html);
        $this->assertStringContainsString('function syncCategoryTabsOrder', $html);
        $this->assertStringContainsString("input.value = String(index + 1);", $html);
        $this->assertStringContainsString('[data-category-sort-order]', $html);
        $this->assertStringContainsString('initCategoryDragDrop();', $html);
        $this->assertStringContainsString('renumberCategorySortOrders();', $html);
        $this->assertStringContainsString("e.key === 'ArrowUp'", $html);
        $this->assertStringContainsString('is-dragging', $html);
        $this->assertStringContainsString('drag-insert-before', $html);
        $this->assertStringContainsString('prefers-reduced-motion', $html);
        $this->assertStringContainsString('cursor: grab', $html);
        $this->assertStringContainsString('cursor: grabbing', $html);

        // discard paths call renumber (compact remaining sort_order)
        $this->assertMatchesRegularExpression(
            '/function requestDiscard[\s\S]*?renumberCategorySortOrders\(\);[\s\S]*?function confirmDiscard[\s\S]*?renumberCategorySortOrders\(\);/',
            $html
        );

        // new category gets trailing sort index; drop path renumbers
        $this->assertStringContainsString("row.querySelector('[data-category-sort-order]')", $html);
        $this->assertStringContainsString('categoryList.insertBefore(dragRow', $html);
    }

    public function test_menu_list_has_drag_handles_and_visible_sort_order(): void
    {
        $category = MenuCategory::query()->create(['name' => 'カット', 'sort_order' => 1]);
        $menu = Menu::query()->create([
            'menu_category_id' => $category->id,
            'name' => 'カットベーシック',
            'price' => '¥5,000',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $html = $this->actingAs($this->admin())
            ->withSession([
                '_old_input' => [
                    'categories' => [
                        $category->id => ['name' => 'カット', 'sort_order' => 1],
                    ],
                    'menus' => [
                        $menu->id => [
                            'name' => 'カットベーシック',
                            'price' => '¥5,000',
                            'sort_order' => 1,
                            'is_published' => '1',
                        ],
                        'new_menu_1' => [
                            'category_id' => $category->id,
                            'name' => '新規',
                            'price' => '¥1,000',
                            'sort_order' => 2,
                            'is_published' => '1',
                            'description' => '',
                        ],
                    ],
                ],
                'errors' => tap(new ViewErrorBag, function (ViewErrorBag $bag) {
                    $bag->put('default', new MessageBag([
                        'menus.new_menu_1.name' => ['検証用'],
                    ]));
                }),
            ])
            ->get(route('admin.menus.index'))
            ->assertOk()
            ->getContent();

        preg_match('/data-category-panel="'.$category->id.'"[\s\S]*?<\/table>/', $html, $panelHtml);
        $this->assertNotEmpty($panelHtml);
        $this->assertSame(2, substr_count($panelHtml[0], 'data-menu-drag-handle'));
        $this->assertStringContainsString('aria-label="メニューを並び替え"', $panelHtml[0]);
        $this->assertStringContainsString('title="ドラッグして並び替え"', $panelHtml[0]);
        $this->assertStringContainsString('class="menu-drag-handle"', $panelHtml[0]);
        $this->assertMatchesRegularExpression(
            '/name="menus\['.$menu->id.'\]\[sort_order\]"/',
            $panelHtml[0]
        );
        $this->assertStringContainsString('name="menus[new_menu_1][sort_order]"', $panelHtml[0]);
        $this->assertStringContainsString('type="number"', $panelHtml[0]);
        $this->assertStringContainsString('data-menu-sort-order', $panelHtml[0]);
        $this->assertStringContainsString('>表示順</th>', $panelHtml[0]);
        $this->assertStringNotContainsString('type="hidden" name="menus['.$menu->id.'][sort_order]"', $panelHtml[0]);

        preg_match('/id="new-menu-row-template"[\s\S]*?<\/template>/', $html, $template);
        $this->assertNotEmpty($template);
        $this->assertStringContainsString('data-menu-drag-handle', $template[0]);
        $this->assertStringContainsString('name="menus[__MENU_ID__][sort_order]"', $template[0]);
        $this->assertStringContainsString('type="number"', $template[0]);
        $this->assertStringContainsString('aria-label="メニューを並び替え"', $template[0]);
    }

    public function test_menu_dnd_script_renumbers_sort_order_and_compacts_on_discard(): void
    {
        MenuCategory::query()->create(['name' => 'カット', 'sort_order' => 1]);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.menus.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('function renumberMenuSortOrders', $html);
        $this->assertStringContainsString('function initMenuDragDrop', $html);
        $this->assertStringContainsString('initMenuDragDrop();', $html);
        $this->assertStringContainsString('[data-menu-drag-handle]', $html);
        $this->assertStringContainsString('dragTbody.insertBefore(dragRow', $html);
        $this->assertStringContainsString("input.value = String(index + 1);", $html);
        $this->assertMatchesRegularExpression(
            '/function discardNewMenu[\s\S]*?renumberMenuSortOrders\(panel\);/',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/function addInlineMenu[\s\S]*?renumberMenuSortOrders\(panel\);/',
            $html
        );
        $this->assertStringContainsString('menu-col-handle', $html);
        $this->assertStringContainsString('menu-col-sort', $html);
    }

    public function test_old_menu_sort_order_restores_row_order(): void
    {
        $category = MenuCategory::query()->create(['name' => 'カット', 'sort_order' => 1]);
        $first = Menu::query()->create([
            'menu_category_id' => $category->id,
            'name' => '先頭だった',
            'price' => '¥1,000',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $second = Menu::query()->create([
            'menu_category_id' => $category->id,
            'name' => '二番目だった',
            'price' => '¥2,000',
            'sort_order' => 2,
            'is_published' => true,
        ]);

        $html = $this->actingAs($this->admin())
            ->withSession([
                '_old_input' => [
                    'categories' => [
                        $category->id => ['name' => 'カット', 'sort_order' => 1],
                    ],
                    'menus' => [
                        $first->id => [
                            'name' => '先頭だった',
                            'price' => '¥1,000',
                            'sort_order' => 2,
                            'is_published' => '1',
                        ],
                        $second->id => [
                            'name' => '二番目だった',
                            'price' => '¥2,000',
                            'sort_order' => 1,
                            'is_published' => '1',
                        ],
                    ],
                ],
                'errors' => tap(new ViewErrorBag, function (ViewErrorBag $bag) use ($first) {
                    $bag->put('default', new MessageBag([
                        'menus.'.$first->id.'.name' => ['検証用'],
                    ]));
                }),
            ])
            ->get(route('admin.menus.index'))
            ->assertOk()
            ->getContent();

        preg_match('/data-menu-tbody[\s\S]*?<\/tbody>/', $html, $tbody);
        $this->assertNotEmpty($tbody);
        $posSecond = strpos($tbody[0], 'data-menu-row="'.$second->id.'"');
        $posFirst = strpos($tbody[0], 'data-menu-row="'.$first->id.'"');
        $this->assertNotFalse($posSecond);
        $this->assertNotFalse($posFirst);
        $this->assertTrue($posSecond < $posFirst);
        $this->assertMatchesRegularExpression(
            '/data-menu-row="'.$second->id.'"[\s\S]*?name="menus\['.$second->id.'\]\[sort_order\]"[^>]*value="1"/',
            $tbody[0]
        );
        $this->assertMatchesRegularExpression(
            '/data-menu-row="'.$first->id.'"[\s\S]*?name="menus\['.$first->id.'\]\[sort_order\]"[^>]*value="2"/',
            $tbody[0]
        );
    }

    public function test_bulk_update_accepts_one_based_category_sort_orders_after_reorder(): void
    {
        $first = MenuCategory::query()->create(['name' => 'カット', 'sort_order' => 1]);
        $second = MenuCategory::query()->create(['name' => 'カラー', 'sort_order' => 2]);
        $third = MenuCategory::query()->create(['name' => 'パーマ', 'sort_order' => 3]);

        $this->actingAs($this->admin())
            ->put(route('admin.menus.bulk-update'), [
                'selected_category_id' => $second->id,
                'categories' => [
                    $first->id => ['name' => 'カット', 'sort_order' => 3],
                    $second->id => ['name' => 'カラー', 'sort_order' => 1],
                    $third->id => ['name' => 'パーマ', 'sort_order' => 2],
                ],
            ])
            ->assertRedirect(route('admin.menus.index'));

        $this->assertSame(3, (int) $first->fresh()->sort_order);
        $this->assertSame(1, (int) $second->fresh()->sort_order);
        $this->assertSame(2, (int) $third->fresh()->sort_order);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.menus.index'))
            ->assertOk()
            ->getContent();

        // left list order follows sort_order (カラー → パーマ → カット)
        $posColor = strpos($html, 'data-category-row="'.$second->id.'"');
        $posPerm = strpos($html, 'data-category-row="'.$third->id.'"');
        $posCut = strpos($html, 'data-category-row="'.$first->id.'"');
        $this->assertNotFalse($posColor);
        $this->assertNotFalse($posPerm);
        $this->assertNotFalse($posCut);
        $this->assertTrue($posColor < $posPerm && $posPerm < $posCut);
    }

    public function test_bulk_update_autorenumbers_duplicate_sort_orders(): void
    {
        $first = MenuCategory::query()->create(['name' => 'カット', 'sort_order' => 1]);
        $second = MenuCategory::query()->create(['name' => 'カラー', 'sort_order' => 2]);
        $menuA = Menu::query()->create([
            'menu_category_id' => $first->id,
            'name' => 'A',
            'price' => '¥1,000',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $menuB = Menu::query()->create([
            'menu_category_id' => $first->id,
            'name' => 'B',
            'price' => '¥2,000',
            'sort_order' => 2,
            'is_published' => true,
        ]);

        $this->actingAs($this->admin())
            ->put(route('admin.menus.bulk-update'), [
                'selected_category_id' => $first->id,
                'categories' => [
                    $first->id => ['name' => 'カット', 'sort_order' => 1],
                    $second->id => ['name' => 'カラー', 'sort_order' => 1],
                ],
                'menus' => [
                    $menuA->id => [
                        'name' => 'A',
                        'price' => '¥1,000',
                        'sort_order' => 1,
                        'is_published' => '1',
                    ],
                    $menuB->id => [
                        'name' => 'B',
                        'price' => '¥2,000',
                        'sort_order' => 2,
                        'is_published' => '1',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.menus.index'))
            ->assertSessionHas('success')
            ->assertSessionDoesntHaveErrors();

        $this->assertSame(1, (int) $first->fresh()->sort_order);
        $this->assertSame(2, (int) $second->fresh()->sort_order);

        $this->actingAs($this->admin())
            ->put(route('admin.menus.bulk-update'), [
                'selected_category_id' => $first->id,
                'categories' => [
                    $first->id => ['name' => 'カット', 'sort_order' => 1],
                    $second->id => ['name' => 'カラー', 'sort_order' => 2],
                ],
                'menus' => [
                    $menuA->id => [
                        'name' => 'A',
                        'price' => '¥1,000',
                        'sort_order' => 2,
                        'is_published' => '1',
                    ],
                    $menuB->id => [
                        'name' => 'B',
                        'price' => '¥2,000',
                        'sort_order' => 2,
                        'is_published' => '1',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.menus.index'))
            ->assertSessionHas('success')
            ->assertSessionDoesntHaveErrors();

        $this->assertSame(1, (int) $menuA->fresh()->sort_order);
        $this->assertSame(2, (int) $menuB->fresh()->sort_order);
    }

    public function test_bulk_update_autorenumbers_empty_or_zero_sort_orders(): void
    {
        $first = MenuCategory::query()->create(['name' => 'カット', 'sort_order' => 1]);
        $second = MenuCategory::query()->create(['name' => 'カラー', 'sort_order' => 2]);
        $menuA = Menu::query()->create([
            'menu_category_id' => $first->id,
            'name' => 'カットベーシック',
            'price' => '¥5,000',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $menuB = Menu::query()->create([
            'menu_category_id' => $first->id,
            'name' => 'カットスペシャル',
            'price' => '¥7,000',
            'sort_order' => 2,
            'is_published' => true,
        ]);

        $this->actingAs($this->admin())
            ->put(route('admin.menus.bulk-update'), [
                'selected_category_id' => $first->id,
                'categories' => [
                    $first->id => ['name' => 'カット', 'sort_order' => 0],
                    $second->id => ['name' => 'カラー', 'sort_order' => ''],
                ],
                'menus' => [
                    $menuA->id => [
                        'name' => 'カットベーシック',
                        'price' => '¥5,000',
                        'sort_order' => '',
                        'is_published' => '1',
                    ],
                    $menuB->id => [
                        'name' => 'カットスペシャル',
                        'price' => '¥7,000',
                        'sort_order' => 0,
                        'is_published' => '1',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.menus.index'))
            ->assertSessionHas('success')
            ->assertSessionDoesntHaveErrors();

        $this->assertSame(1, (int) $first->fresh()->sort_order);
        $this->assertSame(2, (int) $second->fresh()->sort_order);
        $this->assertSame(1, (int) $menuA->fresh()->sort_order);
        $this->assertSame(2, (int) $menuB->fresh()->sort_order);
    }

    public function test_bulk_update_saves_one_based_sort_orders_successfully(): void
    {
        $first = MenuCategory::query()->create(['name' => 'カット', 'sort_order' => 1]);
        $second = MenuCategory::query()->create(['name' => 'カラー', 'sort_order' => 2]);
        $menuA = Menu::query()->create([
            'menu_category_id' => $first->id,
            'name' => 'A',
            'price' => '¥1,000',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $menuB = Menu::query()->create([
            'menu_category_id' => $first->id,
            'name' => 'B',
            'price' => '¥2,000',
            'sort_order' => 2,
            'is_published' => true,
        ]);

        $this->actingAs($this->admin())
            ->put(route('admin.menus.bulk-update'), [
                'selected_category_id' => $first->id,
                'categories' => [
                    $first->id => ['name' => 'カット', 'sort_order' => 1],
                    $second->id => ['name' => 'カラー', 'sort_order' => 2],
                ],
                'menus' => [
                    $menuA->id => [
                        'name' => 'A',
                        'price' => '¥1,000',
                        'sort_order' => 1,
                        'is_published' => '1',
                    ],
                    $menuB->id => [
                        'name' => 'B',
                        'price' => '¥2,000',
                        'sort_order' => 2,
                        'is_published' => '1',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.menus.index'))
            ->assertSessionHas('success');

        $this->assertSame(1, (int) $first->fresh()->sort_order);
        $this->assertSame(2, (int) $second->fresh()->sort_order);
        $this->assertSame(1, (int) $menuA->fresh()->sort_order);
        $this->assertSame(2, (int) $menuB->fresh()->sort_order);
    }
}
