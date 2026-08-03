<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\TopPageSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BannerAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    private function createBanner(array $overrides = []): Banner
    {
        return Banner::query()->create(array_merge([
            'title' => 'キャンペーンバナー',
            'description' => '説明文',
            'image_path' => 'banners/sample.jpg',
            'alt_text' => '代替テキスト',
            'link_url' => 'https://example.com',
            'link_target' => '_self',
            'display_location' => Banner::LOCATION_TOP,
            'display_order' => 1,
            'is_published' => true,
            'published_from' => null,
            'published_until' => null,
        ], $overrides));
    }

    public function test_banner_edit_shows_card_ui_and_sticky_save(): void
    {
        $banner = $this->createBanner();

        $html = $this->actingAs($this->admin())
            ->get(route('admin.home.banners'))
            ->assertOk()
            ->assertDontSee('この機能は現在準備中です。', false)
            ->getContent();

        $this->assertStringContainsString('id="banners-bulk-form"', $html);
        $this->assertStringContainsString('grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3', $html);
        $this->assertStringContainsString('id="banner-add-card"', $html);
        $this->assertStringContainsString('バナーを追加', $html);
        $this->assertStringContainsString('カードを追加し、保存で登録できます。', $html);
        $this->assertStringContainsString('data-banner-drag-handle', $html);
        $this->assertStringContainsString('data-banner-card-title', $html);
        $this->assertStringContainsString('キャンペーンバナー', $html);
        $this->assertStringContainsString('admin-required-badge', $html);
        $this->assertStringContainsString('必須', $html);
        $this->assertStringContainsString('name="banners['.$banner->id.'][title]"', $html);
        $this->assertStringContainsString('name="banners['.$banner->id.'][link_url]"', $html);
        $this->assertStringContainsString('name="banners['.$banner->id.'][display_location]"', $html);
        $this->assertStringContainsString('banner-location-chips', $html);
        $this->assertStringContainsString('banner-location-chip-input', $html);
        $this->assertStringContainsString('banner-dropzone', $html);
        $this->assertStringContainsString('aria-label="公開状態"', $html);
        $this->assertStringContainsString('name="banners['.$banner->id.'][is_published]"', $html);
        $this->assertStringContainsString('admin-segmented-input', $html);
        $this->assertStringContainsString('>公開</span>', $html);
        $this->assertStringContainsString('>非公開</span>', $html);
        $this->assertStringContainsString('sticky top-0', $html);
        $this->assertStringContainsString('data-admin-confirm-trigger', $html);
        $this->assertStringContainsString('data-confirm-form="banners-bulk-form"', $html);
        $this->assertStringContainsString('data-confirm-submit-label="保存する"', $html);
        $this->assertStringContainsString('推奨サイズ：1200×400', $html);
        $this->assertStringNotContainsString('ありません', $html);
    }

    public function test_banner_edit_shows_add_card_when_empty(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('admin.home.banners'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="banner-add-card"', $html);
        $this->assertStringContainsString('バナーを追加', $html);
        $this->assertStringNotContainsString('ありません', $html);
    }

    public function test_bulk_update_creates_updates_and_deletes_banners(): void
    {
        Storage::fake('public');

        $keep = $this->createBanner([
            'image_path' => UploadedFile::fake()->image('keep.jpg')->store('banners', 'public'),
            'title' => '残す',
            'display_order' => 1,
            'is_published' => true,
        ]);
        $remove = $this->createBanner([
            'image_path' => UploadedFile::fake()->image('remove.jpg')->store('banners', 'public'),
            'title' => '消す',
            'display_order' => 2,
        ]);
        $removePath = $remove->image_path;

        $newImage = UploadedFile::fake()->image('new.jpg', 1200, 400);
        $replaceImage = UploadedFile::fake()->image('replace.jpg', 1200, 400);

        $this->actingAs($this->admin())
            ->put(route('admin.home.banners.update'), [
                'banners' => [
                    $keep->id => [
                        'title' => '更新後',
                        'description' => '更新説明',
                        'alt_text' => 'alt更新',
                        'link_url' => 'https://example.com/updated',
                        'link_target' => '_blank',
                        'display_location' => Banner::LOCATION_TOP,
                        'display_order' => 2,
                        'is_published' => '0',
                        'published_from' => '',
                        'published_until' => '',
                        'image' => $replaceImage,
                    ],
                ],
                'new_banners' => [
                    'new_1' => [
                        'title' => '新規バナー',
                        'description' => '新規説明',
                        'alt_text' => '',
                        'link_url' => '',
                        'link_target' => '_self',
                        'display_location' => Banner::LOCATION_TOP,
                        'display_order' => 1,
                        'is_published' => '1',
                        'published_from' => '',
                        'published_until' => '',
                        'image' => $newImage,
                    ],
                ],
                'deleted_ids' => [$remove->id],
            ])
            ->assertRedirect(route('admin.home.banners'))
            ->assertSessionHas('success', 'バナーを保存しました。');

        $this->assertDatabaseMissing('banners', ['id' => $remove->id]);
        Storage::disk('public')->assertMissing($removePath);

        $keep->refresh();
        $this->assertSame('更新後', $keep->title);
        $this->assertFalse($keep->is_published);
        $this->assertSame('_blank', $keep->link_target);
        $this->assertSame(2, $keep->display_order);
        Storage::disk('public')->assertExists($keep->image_path);

        $created = Banner::query()->where('title', '新規バナー')->first();
        $this->assertNotNull($created);
        $this->assertSame(1, $created->display_order);
        $this->assertNull($created->link_url);
        Storage::disk('public')->assertExists($created->image_path);
    }

    public function test_bulk_update_without_new_image_keeps_existing_image(): void
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->image('keep.jpg')->store('banners', 'public');
        $banner = $this->createBanner(['image_path' => $path, 'title' => '旧']);

        $this->actingAs($this->admin())
            ->put(route('admin.home.banners.update'), [
                'banners' => [
                    $banner->id => [
                        'title' => '画像維持',
                        'description' => null,
                        'alt_text' => null,
                        'link_url' => null,
                        'link_target' => '_self',
                        'display_location' => Banner::LOCATION_TOP,
                        'display_order' => 1,
                        'is_published' => '1',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.home.banners'));

        $this->assertDatabaseHas('banners', [
            'id' => $banner->id,
            'image_path' => $path,
            'title' => '画像維持',
        ]);
    }

    public function test_bulk_update_requires_title_and_image_for_new_banner(): void
    {
        $this->actingAs($this->admin())
            ->from(route('admin.home.banners'))
            ->put(route('admin.home.banners.update'), [
                'new_banners' => [
                    'new_1' => [
                        'title' => '',
                        'link_target' => '_self',
                        'display_location' => Banner::LOCATION_TOP,
                        'display_order' => 1,
                        'is_published' => '1',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.home.banners'))
            ->assertSessionHasErrors(['new_banners.new_1.title', 'new_banners.new_1.image']);
    }

    public function test_bulk_update_validates_link_url_and_publish_window(): void
    {
        Storage::fake('public');
        $banner = $this->createBanner([
            'image_path' => UploadedFile::fake()->image('ok.jpg')->store('banners', 'public'),
        ]);

        $this->actingAs($this->admin())
            ->from(route('admin.home.banners'))
            ->put(route('admin.home.banners.update'), [
                'banners' => [
                    $banner->id => [
                        'title' => '不正URL',
                        'link_url' => 'not-a-url',
                        'link_target' => '_self',
                        'display_location' => Banner::LOCATION_TOP,
                        'display_order' => 1,
                        'is_published' => '1',
                        'published_from' => '2026-08-10T10:00',
                        'published_until' => '2026-08-01T10:00',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.home.banners'))
            ->assertSessionHasErrors([
                'banners.'.$banner->id.'.link_url',
                'banners.'.$banner->id.'.published_until',
            ]);
    }

    public function test_bulk_update_rejects_invalid_image_mime(): void
    {
        Storage::fake('public');
        $banner = $this->createBanner([
            'image_path' => UploadedFile::fake()->image('ok.jpg')->store('banners', 'public'),
        ]);

        $this->actingAs($this->admin())
            ->from(route('admin.home.banners'))
            ->put(route('admin.home.banners.update'), [
                'banners' => [
                    $banner->id => [
                        'title' => $banner->title,
                        'link_target' => '_self',
                        'display_location' => Banner::LOCATION_TOP,
                        'display_order' => 1,
                        'is_published' => '1',
                        'image' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
                    ],
                ],
            ])
            ->assertRedirect(route('admin.home.banners'))
            ->assertSessionHasErrors('banners.'.$banner->id.'.image');
    }

    public function test_bulk_update_normalizes_display_order(): void
    {
        Storage::fake('public');
        $a = $this->createBanner([
            'image_path' => UploadedFile::fake()->image('a.jpg')->store('banners', 'public'),
            'title' => 'A',
            'display_order' => 10,
        ]);
        $b = $this->createBanner([
            'image_path' => UploadedFile::fake()->image('b.jpg')->store('banners', 'public'),
            'title' => 'B',
            'display_order' => 20,
        ]);

        $this->actingAs($this->admin())
            ->put(route('admin.home.banners.update'), [
                'banners' => [
                    $b->id => [
                        'title' => 'B',
                        'link_target' => '_self',
                        'display_location' => Banner::LOCATION_TOP,
                        'display_order' => 1,
                        'is_published' => '1',
                    ],
                    $a->id => [
                        'title' => 'A',
                        'link_target' => '_self',
                        'display_location' => Banner::LOCATION_TOP,
                        'display_order' => 2,
                        'is_published' => '1',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.home.banners'));

        $this->assertSame(1, $b->fresh()->display_order);
        $this->assertSame(2, $a->fresh()->display_order);
    }

    public function test_guest_cannot_access_banner_admin(): void
    {
        $this->get(route('admin.home.banners'))->assertRedirect();
        $this->put(route('admin.home.banners.update'), [])->assertRedirect();
    }

    public function test_home_shows_visible_top_banners_in_order(): void
    {
        Storage::fake('public');
        TopPageSection::ensureDefaults();

        $first = $this->createBanner([
            'title' => '先に表示',
            'image_path' => UploadedFile::fake()->image('1.jpg')->store('banners', 'public'),
            'display_order' => 1,
            'link_url' => 'https://example.com/first',
            'link_target' => '_blank',
            'alt_text' => '',
        ]);
        $this->createBanner([
            'title' => '後に表示',
            'image_path' => UploadedFile::fake()->image('2.jpg')->store('banners', 'public'),
            'display_order' => 2,
            'link_url' => null,
        ]);

        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('id="banners"', $html);
        $this->assertStringContainsString('先に表示', $html);
        $this->assertStringContainsString('後に表示', $html);
        $this->assertStringContainsString('href="https://example.com/first"', $html);
        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringContainsString('alt="先に表示"', $html);
        $this->assertLessThan(strpos($html, '後に表示'), strpos($html, '先に表示'));
        $this->assertLessThan(strpos($html, 'id="news"'), strpos($html, 'id="banners"'));
        $this->assertNotNull($first);
    }

    public function test_home_hides_unpublished_outside_period_and_wrong_location(): void
    {
        Storage::fake('public');
        TopPageSection::ensureDefaults();

        $this->createBanner([
            'title' => '非公開',
            'image_path' => UploadedFile::fake()->image('u.jpg')->store('banners', 'public'),
            'is_published' => false,
        ]);
        $this->createBanner([
            'title' => '期間前',
            'image_path' => UploadedFile::fake()->image('f.jpg')->store('banners', 'public'),
            'published_from' => now()->addDay(),
        ]);
        $this->createBanner([
            'title' => '期間後',
            'image_path' => UploadedFile::fake()->image('p.jpg')->store('banners', 'public'),
            'published_until' => now()->subDay(),
        ]);
        $this->createBanner([
            'title' => 'メニュー用',
            'image_path' => UploadedFile::fake()->image('m.jpg')->store('banners', 'public'),
            'display_location' => Banner::LOCATION_MENU,
        ]);

        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringNotContainsString('id="banners"', $html);
        $this->assertStringNotContainsString('非公開', $html);
        $this->assertStringNotContainsString('期間前', $html);
        $this->assertStringNotContainsString('期間後', $html);
        $this->assertStringNotContainsString('メニュー用', $html);
    }

    public function test_home_hides_banner_section_when_top_section_disabled(): void
    {
        Storage::fake('public');
        TopPageSection::ensureDefaults();
        TopPageSection::query()->where('section_key', TopPageSection::KEY_BANNER)->update([
            'is_visible' => false,
        ]);

        $this->createBanner([
            'title' => 'セクションOFF',
            'image_path' => UploadedFile::fake()->image('x.jpg')->store('banners', 'public'),
        ]);

        $html = $this->get(route('home'))->assertOk()->getContent();
        $this->assertStringNotContainsString('id="banners"', $html);
        $this->assertStringNotContainsString('セクションOFF', $html);
    }
}
