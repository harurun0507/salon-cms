<?php

namespace Tests\Feature;

use App\Models\HeroImage;
use App\Models\SalonSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HeroImageTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    public function test_legacy_hero_image_migration_logic_is_idempotent(): void
    {
        $setting = SalonSetting::current();
        $setting->update(['hero_image' => 'settings/legacy.png']);

        $migrateOnce = function () use ($setting): void {
            $exists = HeroImage::query()
                ->where('salon_setting_id', $setting->id)
                ->where('image_path', $setting->hero_image)
                ->exists();

            if ($exists) {
                return;
            }

            HeroImage::query()->create([
                'salon_setting_id' => $setting->id,
                'image_path' => $setting->hero_image,
                'alt_text' => null,
                'sort_order' => 1,
                'is_published' => true,
            ]);
        };

        $migrateOnce();
        $migrateOnce();

        $this->assertSame(1, HeroImage::query()->count());
        $this->assertDatabaseHas('hero_images', [
            'salon_setting_id' => $setting->id,
            'image_path' => 'settings/legacy.png',
            'is_published' => 1,
        ]);
        $this->assertSame('settings/legacy.png', $setting->fresh()->hero_image);
    }

    public function test_admin_can_upload_multiple_hero_images_and_update_meta(): void
    {
        Storage::fake('public');
        $setting = SalonSetting::current();
        $user = $this->admin();

        $response = $this->actingAs($user)->put(route('admin.home.hero.update'), [
            'new_hero_images' => [
                'new_1' => UploadedFile::fake()->image('a.jpg'),
                'new_2' => UploadedFile::fake()->image('b.jpg'),
            ],
            'new_hero_meta' => [
                'new_1' => ['sort_order' => 1, 'is_published' => '1'],
                'new_2' => ['sort_order' => 2, 'is_published' => '1'],
            ],
        ]);

        $response->assertRedirect(route('admin.home.hero'));
        $this->assertSame(2, $setting->heroImages()->count());

        $images = $setting->heroImages()->ordered()->get();
        $first = $images[0];
        $second = $images[1];

        $this->actingAs($user)->put(route('admin.home.hero.update'), [
            'hero_images' => [
                $first->id => [
                    'sort_order' => 2,
                    'alt_text' => '正面',
                    'is_published' => '1',
                ],
                $second->id => [
                    'sort_order' => 1,
                    'alt_text' => '',
                    'is_published' => '0',
                ],
            ],
        ])->assertRedirect(route('admin.home.hero'));

        $first->refresh();
        $second->refresh();
        $this->assertSame(2, $first->sort_order);
        $this->assertSame('正面', $first->alt_text);
        $this->assertTrue($first->is_published);
        $this->assertSame(1, $second->sort_order);
        $this->assertFalse($second->is_published);
    }

    public function test_bulk_update_normalizes_sort_order_including_unpublished(): void
    {
        Storage::fake('public');
        $setting = SalonSetting::current();

        $a = HeroImage::query()->create([
            'salon_setting_id' => $setting->id,
            'image_path' => 'settings/a.jpg',
            'sort_order' => 10,
            'is_published' => true,
        ]);
        $b = HeroImage::query()->create([
            'salon_setting_id' => $setting->id,
            'image_path' => 'settings/b.jpg',
            'sort_order' => 20,
            'is_published' => false,
        ]);

        $this->actingAs($this->admin())->put(route('admin.home.hero.update'), [
            'hero_images' => [
                $b->id => [
                    'sort_order' => 1,
                    'alt_text' => '',
                    'is_published' => '0',
                ],
                $a->id => [
                    'sort_order' => 2,
                    'alt_text' => '',
                    'is_published' => '1',
                ],
            ],
        ])->assertRedirect(route('admin.home.hero'));

        $this->assertSame(1, $b->fresh()->sort_order);
        $this->assertFalse($b->fresh()->is_published);
        $this->assertSame(2, $a->fresh()->sort_order);
        $this->assertTrue($a->fresh()->is_published);
    }

    public function test_home_page_shows_only_published_hero_images_and_hides_controls_for_single_image(): void
    {
        Storage::fake('public');
        $setting = SalonSetting::current();

        HeroImage::query()->create([
            'salon_setting_id' => $setting->id,
            'image_path' => 'settings/one.jpg',
            'alt_text' => '公開1',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        HeroImage::query()->create([
            'salon_setting_id' => $setting->id,
            'image_path' => 'settings/hidden.jpg',
            'alt_text' => '非公開',
            'sort_order' => 2,
            'is_published' => false,
        ]);

        $response = $this->get(route('home'));
        $response->assertOk();
        $response->assertSee('storage/settings/one.jpg', false);
        $response->assertDontSee('storage/settings/hidden.jpg', false);
        $response->assertDontSee('id="hero-prev"', false);
        $response->assertDontSee('id="hero-dots"', false);
    }

    public function test_home_page_shows_slider_controls_for_multiple_published_images(): void
    {
        Storage::fake('public');
        $setting = SalonSetting::current();

        HeroImage::query()->create([
            'salon_setting_id' => $setting->id,
            'image_path' => 'settings/a.jpg',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        HeroImage::query()->create([
            'salon_setting_id' => $setting->id,
            'image_path' => 'settings/b.jpg',
            'sort_order' => 2,
            'is_published' => true,
        ]);

        $response = $this->get(route('home'));
        $response->assertOk();
        $response->assertSee('id="hero-prev"', false);
        $response->assertSee('id="hero-dots"', false);
        $response->assertSee('data-autoplay="5000"', false);
        $html = $response->getContent();
        $this->assertStringContainsString('hero-slider-controls', $html);
        $this->assertStringContainsString('data-hero-controls', $html);
        $this->assertStringContainsString('hero-slider-nav', $html);
        $this->assertStringContainsString('hero-slider--draggable', $html);
        $this->assertStringContainsString('data-hero-drag', $html);
        $this->assertStringContainsString('draggable="false"', $html);
        $this->assertStringContainsString('hero-slides-track', $html);
        $this->assertStringContainsString('data-slide-carousel', $html);
        $this->assertStringContainsString('data-slide-carousel-track', $html);
        $this->assertStringContainsString('data-slide-carousel-prev', $html);
        $this->assertStringContainsString('data-slide-carousel-next', $html);
        $this->assertStringContainsString('hero-slide-image', $html);
        $this->assertStringNotContainsString('transition-opacity duration-700', $html);
    }

    public function test_home_page_falls_back_to_gradient_when_no_published_images(): void
    {
        SalonSetting::current();

        $response = $this->get(route('home'));
        $response->assertOk();
        $response->assertSee('bg-gradient-to-br', false);
    }

    public function test_deleting_hero_image_via_bulk_save_removes_db_row_and_file(): void
    {
        Storage::fake('public');
        $setting = SalonSetting::current();
        $keepPath = UploadedFile::fake()->image('keep.jpg')->store('settings', 'public');
        $removePath = UploadedFile::fake()->image('delete-me.jpg')->store('settings', 'public');

        $keep = HeroImage::query()->create([
            'salon_setting_id' => $setting->id,
            'image_path' => $keepPath,
            'sort_order' => 1,
            'alt_text' => '残す',
            'is_published' => true,
        ]);
        $remove = HeroImage::query()->create([
            'salon_setting_id' => $setting->id,
            'image_path' => $removePath,
            'sort_order' => 2,
            'is_published' => true,
        ]);

        Storage::disk('public')->assertExists($removePath);

        $this->actingAs($this->admin())
            ->put(route('admin.home.hero.update'), [
                'hero_images' => [
                    $keep->id => [
                        'sort_order' => 1,
                        'alt_text' => '更新後',
                        'is_published' => '1',
                    ],
                ],
                'deleted_ids' => [$remove->id],
            ])
            ->assertRedirect(route('admin.home.hero'));

        $this->assertDatabaseMissing('hero_images', ['id' => $remove->id]);
        Storage::disk('public')->assertMissing($removePath);

        $keep->refresh();
        $this->assertSame(1, $keep->sort_order);
        $this->assertSame('更新後', $keep->alt_text);
        Storage::disk('public')->assertExists($keepPath);
    }

    public function test_hero_page_defers_delete_to_bulk_save_without_nested_delete_form(): void
    {
        Storage::fake('public');
        $setting = SalonSetting::current();
        HeroImage::query()->create([
            'salon_setting_id' => $setting->id,
            'image_path' => 'settings/exists.jpg',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.home.hero'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="hero-form"', $html);
        $this->assertStringContainsString('name="_method" value="PUT"', $html);
        $this->assertStringContainsString('id="hero-deleted-ids"', $html);
        $this->assertStringContainsString('data-hero-remove', $html);
        $this->assertStringContainsString("hidden.name = 'deleted_ids[]'", $html);
        $this->assertStringContainsString('markHeroBlockRemoved', $html);
        $this->assertStringNotContainsString('data-delete-message="メインビジュアル画像を削除しますか？"', $html);
        $this->assertStringNotContainsString('name="_method" value="DELETE"', $html);
        $this->assertStringNotContainsString('/admin/home/hero/images/', $html);
    }

    public function test_cannot_upload_more_than_max_hero_images(): void
    {
        Storage::fake('public');
        $setting = SalonSetting::current();
        $user = $this->admin();

        for ($i = 0; $i < HeroImage::MAX_COUNT; $i++) {
            HeroImage::query()->create([
                'salon_setting_id' => $setting->id,
                'image_path' => 'settings/img-'.$i.'.jpg',
                'sort_order' => $i + 1,
                'is_published' => true,
            ]);
        }

        $this->actingAs($user)->from(route('admin.home.hero'))->put(route('admin.home.hero.update'), [
            'new_hero_images' => [
                'new_extra' => UploadedFile::fake()->image('extra.jpg'),
            ],
            'new_hero_meta' => [
                'new_extra' => ['sort_order' => 99, 'is_published' => '1'],
            ],
        ])->assertRedirect(route('admin.home.hero'))
            ->assertSessionHasErrors('new_hero_images');

        $this->assertSame(HeroImage::MAX_COUNT, $setting->heroImages()->count());
    }

    public function test_hero_page_does_not_reference_legacy_hero_image_column_for_display(): void
    {
        Storage::fake('public');
        $setting = SalonSetting::current();
        $setting->update(['hero_image' => 'settings/legacy-only.png']);

        $response = $this->actingAs($this->admin())->get(route('admin.home.hero'));
        $response->assertOk();
        $response->assertDontSee('storage/settings/legacy-only.png', false);
        $response->assertDontSee('現在登録されている画像はありません', false);
        $response->assertSee('id="hero-image-add-card"', false);
        $response->assertSee('カードを追加し、保存で登録できます。', false);
    }

    public function test_hero_page_uses_card_grid_dnd_and_segmented_publish(): void
    {
        Storage::fake('public');
        $setting = SalonSetting::current();
        $image = HeroImage::query()->create([
            'salon_setting_id' => $setting->id,
            'image_path' => 'settings/exists.jpg',
            'alt_text' => '正面エントランス',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.home.hero'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="hero-image-add-card"', $html);
        $this->assertStringContainsString('id="hero-image-add-card-btn"', $html);
        $this->assertStringContainsString('data-hero-add', $html);
        $this->assertStringContainsString('admin-empty-state-icon', $html);
        $this->assertStringContainsString('min-h-[22rem]', $html);
        $this->assertStringContainsString('カードを追加し、保存で登録できます。', $html);
        $this->assertDoesNotMatchRegularExpression('/id="hero-image-add-card"\s[^>]*\bhidden\b/', $html);
        $this->assertStringContainsString('画像を追加', $html);
        $this->assertStringContainsString('id="hero-images-list"', $html);
        $this->assertStringContainsString('grid grid-cols-1 gap-4 lg:grid-cols-2', $html);
        $this->assertStringContainsString('data-hero-grid', $html);
        $this->assertStringContainsString('data-hero-card', $html);
        $this->assertStringContainsString('data-hero-existing', $html);
        $this->assertStringContainsString('hero-drag-handle', $html);
        $this->assertStringContainsString('data-hero-drag-handle', $html);
        $this->assertStringContainsString('data-hero-order', $html);
        $this->assertStringContainsString('name="hero_images['.$image->id.'][sort_order]"', $html);
        $this->assertStringContainsString('type="hidden"', $html);
        $this->assertStringContainsString('function syncSortOrders', $html);
        $this->assertStringContainsString('function syncCardHeading', $html);
        $this->assertStringContainsString('data-hero-card-title', $html);
        $this->assertStringContainsString('data-hero-alt-input', $html);
        $this->assertStringContainsString('メインビジュアル', $html);
        $this->assertStringContainsString('正面エントランス', $html);
        $this->assertStringContainsString('aspect-[16/9]', $html);
        $this->assertStringContainsString('admin-segmented-input', $html);
        $this->assertStringContainsString('aria-label="公開状態"', $html);
        $this->assertStringContainsString('name="hero_images['.$image->id.'][is_published]" value="1"', $html);
        $this->assertStringContainsString('name="hero_images['.$image->id.'][is_published]" value="0"', $html);
        $this->assertStringContainsString('banner-dropzone', $html);
        $this->assertStringContainsString('createHeroSlot', $html);
        $this->assertStringContainsString('heroAddCard.before(card)', $html);
        $this->assertStringContainsString('syncHeroAddUi', $html);
        $this->assertStringContainsString('data-confirm-form="hero-form"', $html);
        $this->assertStringContainsString('トップページのメインビジュアルを登録します。推奨：横長画像', $html);
        $this->assertStringContainsString('id="hero-image-count"', $html);
        $this->assertStringContainsString('>登録数</span>', $html);
        $this->assertStringContainsString('1 / '.HeroImage::MAX_COUNT.'枚', $html);
        $this->assertStringContainsString('hero-count-badge', $html);
        $this->assertMatchesRegularExpression(
            '/sticky top-0.*?id="hero-image-count"/s',
            $html
        );
        $this->assertStringNotContainsString('登録数:', $html);
        $this->assertDoesNotMatchRegularExpression(
            '/mb-6 flex justify-end[\s\S]*id="hero-image-count"/',
            $html
        );

        $this->assertStringNotContainsString('menu-published-checkbox', $html);
        $this->assertStringNotContainsString('menu-published-control', $html);
        $this->assertStringNotContainsString('menu-published-label', $html);
        $this->assertStringNotContainsString('syncPublishedLabel', $html);
        $this->assertStringNotContainsString('hero-image-label', $html);
        $this->assertStringNotContainsString('画像1', $html);
        $this->assertStringNotContainsString('type="number"', $html);
        $this->assertStringNotContainsString('sm:grid-cols-2 lg:grid-cols-3', $html);
        $this->assertStringNotContainsString('id="hero-image-add-footer"', $html);
        $this->assertStringNotContainsString('id="hero-image-add-btn"', $html);
        $this->assertStringNotContainsString('空の枠はプレビュー欄から画像を選択できます。', $html);
        $this->assertStringNotContainsString('現在登録されている画像はありません', $html);
        $this->assertStringNotContainsString('公開する', $html);
        $this->assertStringNotContainsString('id="hero-image-dropzone"', $html);
        $this->assertStringNotContainsString('name="new_hero_images[]"', $html);
        $this->assertStringNotContainsString('この機能は現在準備中です。', $html);
        $this->assertStringNotContainsString('カードを追加し、「保存する」で登録できます。', $html);
    }

    public function test_hero_page_keeps_add_card_after_existing_hero_images(): void
    {
        Storage::fake('public');
        $setting = SalonSetting::current();
        HeroImage::query()->create([
            'salon_setting_id' => $setting->id,
            'image_path' => 'settings/exists.jpg',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.home.hero'))
            ->assertOk()
            ->getContent();

        $this->assertDoesNotMatchRegularExpression('/id="hero-image-add-card"\s[^>]*\bhidden\b/', $html);
        $this->assertStringContainsString('id="hero-image-add-card"', $html);
        $this->assertStringContainsString('id="hero-image-add-card-btn"', $html);
        $this->assertStringContainsString('heroAddCard.before(card)', $html);
        $this->assertStringContainsString('syncHeroAddUi', $html);
        $this->assertStringNotContainsString('id="hero-image-add-footer"', $html);
        $this->assertStringNotContainsString('id="hero-image-add-btn"', $html);
        $this->assertStringNotContainsString('空の枠はプレビュー欄から画像を選択できます。', $html);
        $this->assertStringNotContainsString('現在登録されている画像はありません', $html);

        $existingPos = strpos($html, 'data-hero-existing');
        $addCardPos = strpos($html, 'id="hero-image-add-card"');
        $this->assertNotFalse($existingPos);
        $this->assertNotFalse($addCardPos);
        $this->assertGreaterThan($existingPos, $addCardPos);
    }

    public function test_hero_page_hides_add_card_when_hero_images_at_max(): void
    {
        Storage::fake('public');
        $setting = SalonSetting::current();
        for ($i = 0; $i < HeroImage::MAX_COUNT; $i++) {
            HeroImage::query()->create([
                'salon_setting_id' => $setting->id,
                'image_path' => 'settings/max-'.$i.'.jpg',
                'sort_order' => $i + 1,
                'is_published' => true,
            ]);
        }

        $html = $this->actingAs($this->admin())
            ->get(route('admin.home.hero'))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/id="hero-image-add-card"\s[^>]*\bhidden\b/', $html);
        $this->assertStringContainsString('syncHeroAddUi', $html);
        $this->assertStringNotContainsString('id="hero-image-add-footer"', $html);
    }

    public function test_hero_page_shows_fallback_heading_when_alt_empty(): void
    {
        Storage::fake('public');
        $setting = SalonSetting::current();
        HeroImage::query()->create([
            'salon_setting_id' => $setting->id,
            'image_path' => 'settings/no-alt.jpg',
            'alt_text' => null,
            'sort_order' => 1,
            'is_published' => true,
        ]);
        HeroImage::query()->create([
            'salon_setting_id' => $setting->id,
            'image_path' => 'settings/no-alt-2.jpg',
            'alt_text' => '',
            'sort_order' => 2,
            'is_published' => true,
        ]);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.home.hero'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('メインビジュアル1', $html);
        $this->assertStringContainsString('メインビジュアル2', $html);
        $this->assertStringContainsString("return 'メインビジュアル' + (index + 1)", $html);
        $this->assertStringNotContainsString('画像1', $html);
        $this->assertStringNotContainsString('画像2', $html);
    }

    public function test_admin_can_upload_new_hero_image_with_meta(): void
    {
        Storage::fake('public');
        $setting = SalonSetting::current();

        $this->actingAs($this->admin())->put(route('admin.home.hero.update'), [
            'new_hero_images' => [
                'new_1' => UploadedFile::fake()->image('with-meta.jpg'),
            ],
            'new_hero_meta' => [
                'new_1' => [
                    'sort_order' => 7,
                    'alt_text' => '新規alt',
                    'is_published' => '0',
                ],
            ],
        ])->assertRedirect(route('admin.home.hero'));

        $image = $setting->heroImages()->first();
        $this->assertNotNull($image);
        $this->assertSame(1, $image->sort_order);
        $this->assertSame('新規alt', $image->alt_text);
        $this->assertFalse($image->is_published);
    }

    public function test_settings_page_does_not_show_hero_image_ui(): void
    {
        Storage::fake('public');
        $setting = SalonSetting::current();
        HeroImage::query()->create([
            'salon_setting_id' => $setting->id,
            'image_path' => 'settings/exists.jpg',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('id="hero-images-list"', $html);
        $this->assertStringNotContainsString('id="hero-image-add-card"', $html);
        $this->assertStringNotContainsString('createHeroSlot', $html);
        $this->assertStringNotContainsString('メインビジュアル画像', $html);
        $this->assertStringContainsString('id="settings-form"', $html);
        $this->assertStringContainsString('name="shop_name"', $html);
    }
}
