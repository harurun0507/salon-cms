<?php

namespace Tests\Feature;

use App\Models\Gallery;
use App\Models\GalleryImage;
use App\Models\StaffMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GalleryBulkSaveTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    private function createGallery(array $overrides = []): Gallery
    {
        $imagePath = $overrides['image_path'] ?? 'galleries/sample.jpg';
        unset($overrides['image_path']);

        $gallery = Gallery::query()->create(array_merge([
            'caption' => 'サンプルキャプション',
            'sort_order' => 1,
            'is_published' => true,
        ], $overrides));

        GalleryImage::query()->create([
            'gallery_id' => $gallery->id,
            'image_path' => $imagePath,
            'alt_text' => $gallery->caption,
            'display_order' => 1,
        ]);

        return $gallery->fresh(['images']);
    }

    public function test_gallery_index_shows_inline_cards_and_bulk_save_without_modals(): void
    {
        $staff = StaffMember::query()->create([
            'name' => 'タカナ コウヘイ',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $gallery = $this->createGallery(['staff_id' => $staff->id]);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.galleries.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="galleries-bulk-form"', $html);
        $this->assertStringContainsString('data-gallery-workspace', $html);
        $this->assertStringContainsString('id="gallery-add-card"', $html);
        $this->assertStringContainsString('data-gallery-add', $html);
        $this->assertStringContainsString('data-gallery-add-top', $html);
        $this->assertStringContainsString('ギャラリーを追加', $html);
        $this->assertStringContainsString('＋画像を追加', $html);
        $this->assertStringContainsString('data-gallery-image-grid', $html);
        $this->assertStringContainsString('data-gallery-image-item', $html);
        $this->assertStringContainsString('data-gallery-image-add', $html);
        $this->assertStringContainsString('担当スタッフ', $html);
        $this->assertStringContainsString('name="galleries['.$gallery->id.'][caption]"', $html);
        $this->assertStringContainsString('name="galleries['.$gallery->id.'][title]"', $html);
        $this->assertStringContainsString('>タイトル</label>', $html);
        $this->assertStringContainsString('>詳細</label>', $html);
        $this->assertStringContainsString('name="galleries['.$gallery->id.'][sort_order]"', $html);
        $this->assertStringContainsString('name="galleries['.$gallery->id.'][staff_id]"', $html);
        $this->assertStringContainsString('data-gallery-staff-input', $html);
        $this->assertStringContainsString('data-gallery-staff-option', $html);
        $this->assertStringContainsString('gallery-staff-chip', $html);
        $this->assertStringContainsString('data-staff-id="'.$staff->id.'"', $html);
        $this->assertStringContainsString('is-selected', $html);
        $this->assertStringContainsString('value="'.$staff->id.'"', $html);
        $this->assertStringContainsString('タカナ コウヘイ', $html);
        $this->assertStringNotContainsString('<select', $html);
        $this->assertStringContainsString('data-gallery-drag-handle', $html);
        $this->assertStringContainsString('data-gallery-image-drag-handle', $html);
        $this->assertStringContainsString('画像1', $html);
        $this->assertStringContainsString('admin-segmented-input', $html);
        $this->assertStringContainsString('data-admin-confirm-trigger', $html);
        $this->assertStringContainsString('data-confirm-form="galleries-bulk-form"', $html);
        $this->assertStringNotContainsString('id="gallery-create-modal"', $html);
        $this->assertStringNotContainsString(
            'href="'.route('admin.galleries.create').'"',
            $html
        );
    }

    public function test_gallery_staff_picker_does_not_auto_select_single_staff_on_unassigned_gallery(): void
    {
        StaffMember::query()->create([
            'name' => '単一スタッフ',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $gallery = $this->createGallery(['staff_id' => null]);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.galleries.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('単一スタッフ', $html);
        $this->assertStringContainsString('id="gallery_staff_'.$gallery->id.'"', $html);
        $this->assertMatchesRegularExpression(
            '/id="gallery_staff_'.$gallery->id.'"[^>]*value=""/',
            $html
        );
        $this->assertStringNotContainsString('gallery-staff-chip is-selected', $html);
    }

    public function test_gallery_index_shows_add_card_when_empty(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('admin.galleries.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="gallery-add-card"', $html);
        $this->assertStringContainsString('ギャラリーを追加', $html);
        $this->assertStringContainsString('data-gallery-add', $html);
        $this->assertStringContainsString('data-gallery-add-top', $html);
        $this->assertStringContainsString('新規ギャラリー', $html);
    }

    public function test_bulk_update_creates_updates_and_deletes_galleries(): void
    {
        Storage::fake('public');

        $keep = $this->createGallery([
            'image_path' => UploadedFile::fake()->image('keep.jpg')->store('galleries', 'public'),
            'caption' => '残す',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $remove = $this->createGallery([
            'image_path' => UploadedFile::fake()->image('remove.jpg')->store('galleries', 'public'),
            'caption' => '消す',
            'sort_order' => 2,
            'is_published' => true,
        ]);
        $removePath = $remove->images->first()->image_path;
        $keepImageId = $keep->images->first()->id;

        $newImage = UploadedFile::fake()->image('new.jpg', 200, 200);
        $extraImage = UploadedFile::fake()->image('extra.jpg', 200, 200);
        $replaceImage = UploadedFile::fake()->image('replace.jpg', 200, 200);

        $this->actingAs($this->admin())
            ->put(route('admin.galleries.bulk-update'), [
                'galleries' => [
                    $keep->id => [
                        'caption' => '更新後',
                        'sort_order' => 2,
                        'is_published' => '0',
                        'images' => [
                            $keepImageId => [
                                'display_order' => 1,
                                'alt_text' => '更新後',
                                'image' => $replaceImage,
                            ],
                        ],
                        'new_images' => [
                            'n1' => [
                                'display_order' => 2,
                                'alt_text' => '追加',
                                'image' => $extraImage,
                            ],
                        ],
                    ],
                ],
                'new_galleries' => [
                    'new_1' => [
                        'caption' => '新規',
                        'sort_order' => 1,
                        'is_published' => '1',
                        'new_images' => [
                            'n1' => [
                                'display_order' => 1,
                                'image' => $newImage,
                            ],
                        ],
                    ],
                ],
                'deleted_ids' => [$remove->id],
            ])
            ->assertRedirect(route('admin.galleries.index'))
            ->assertSessionHas('success', 'ギャラリーを一括保存しました。');

        $this->assertDatabaseHas('galleries', [
            'id' => $keep->id,
            'caption' => '更新後',
            'sort_order' => 2,
            'is_published' => false,
        ]);
        $this->assertDatabaseMissing('galleries', ['id' => $remove->id]);
        $this->assertDatabaseHas('galleries', [
            'caption' => '新規',
            'sort_order' => 1,
            'is_published' => true,
        ]);

        Storage::disk('public')->assertMissing($removePath);
        $keep->refresh()->load('images');
        $this->assertCount(2, $keep->images);
        Storage::disk('public')->assertExists($keep->images->first()->image_path);

        $created = Gallery::query()->where('caption', '新規')->with('images')->first();
        $this->assertNotNull($created);
        $this->assertCount(1, $created->images);
    }

    public function test_bulk_update_normalizes_sort_order_to_sequential(): void
    {
        Storage::fake('public');

        $first = $this->createGallery([
            'image_path' => UploadedFile::fake()->image('a.jpg')->store('galleries', 'public'),
            'caption' => 'A',
            'sort_order' => 10,
        ]);
        $second = $this->createGallery([
            'image_path' => UploadedFile::fake()->image('b.jpg')->store('galleries', 'public'),
            'caption' => 'B',
            'sort_order' => 20,
        ]);

        $this->actingAs($this->admin())
            ->put(route('admin.galleries.bulk-update'), [
                'galleries' => [
                    $first->id => [
                        'caption' => 'A',
                        'sort_order' => 5,
                        'is_published' => '1',
                        'images' => [
                            $first->images->first()->id => ['display_order' => 1],
                        ],
                    ],
                    $second->id => [
                        'caption' => 'B',
                        'sort_order' => 1,
                        'is_published' => '1',
                        'images' => [
                            $second->images->first()->id => ['display_order' => 1],
                        ],
                    ],
                ],
            ])
            ->assertRedirect(route('admin.galleries.index'));

        $this->assertDatabaseHas('galleries', [
            'id' => $second->id,
            'sort_order' => 1,
        ]);
        $this->assertDatabaseHas('galleries', [
            'id' => $first->id,
            'sort_order' => 2,
        ]);
    }

    public function test_bulk_update_without_new_image_keeps_existing_image(): void
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->image('keep.jpg')->store('galleries', 'public');
        $gallery = $this->createGallery(['image_path' => $path, 'caption' => '旧']);
        $imageId = $gallery->images->first()->id;

        $this->actingAs($this->admin())
            ->put(route('admin.galleries.bulk-update'), [
                'galleries' => [
                    $gallery->id => [
                        'caption' => '画像維持',
                        'sort_order' => 2,
                        'is_published' => '1',
                        'images' => [
                            $imageId => [
                                'display_order' => 1,
                                'alt_text' => '画像維持',
                            ],
                        ],
                    ],
                ],
            ])
            ->assertRedirect(route('admin.galleries.index'));

        $this->assertDatabaseHas('galleries', [
            'id' => $gallery->id,
            'caption' => '画像維持',
            'sort_order' => 1,
        ]);
        $this->assertDatabaseHas('gallery_images', [
            'id' => $imageId,
            'image_path' => $path,
            'display_order' => 1,
        ]);
    }

    public function test_bulk_update_requires_image_for_new_gallery(): void
    {
        $this->actingAs($this->admin())
            ->from(route('admin.galleries.index'))
            ->put(route('admin.galleries.bulk-update'), [
                'new_galleries' => [
                    'new_1' => [
                        'caption' => '画像なし',
                        'sort_order' => 0,
                        'is_published' => '1',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.galleries.index'))
            ->assertSessionHasErrors('new_galleries.new_1.new_images');

        $this->assertDatabaseMissing('galleries', ['caption' => '画像なし']);
    }

    public function test_bulk_update_rejects_invalid_image_mime(): void
    {
        Storage::fake('public');
        $gallery = $this->createGallery([
            'image_path' => UploadedFile::fake()->image('ok.jpg')->store('galleries', 'public'),
        ]);
        $imageId = $gallery->images->first()->id;

        $this->actingAs($this->admin())
            ->from(route('admin.galleries.index'))
            ->put(route('admin.galleries.bulk-update'), [
                'galleries' => [
                    $gallery->id => [
                        'caption' => $gallery->caption,
                        'sort_order' => 1,
                        'is_published' => '1',
                        'images' => [
                            $imageId => [
                                'display_order' => 1,
                                'image' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
                            ],
                        ],
                    ],
                ],
            ])
            ->assertRedirect(route('admin.galleries.index'))
            ->assertSessionHasErrors('galleries.'.$gallery->id.'.images.'.$imageId.'.image');
    }

    public function test_bulk_update_can_assign_staff_and_reorder_images(): void
    {
        Storage::fake('public');
        $staff = StaffMember::query()->create([
            'name' => '山田',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $gallery = $this->createGallery([
            'image_path' => UploadedFile::fake()->image('one.jpg')->store('galleries', 'public'),
            'caption' => '複数',
        ]);
        $firstId = $gallery->images->first()->id;
        $second = GalleryImage::query()->create([
            'gallery_id' => $gallery->id,
            'image_path' => UploadedFile::fake()->image('two.jpg')->store('galleries', 'public'),
            'display_order' => 2,
        ]);

        $this->actingAs($this->admin())
            ->put(route('admin.galleries.bulk-update'), [
                'galleries' => [
                    $gallery->id => [
                        'caption' => '複数',
                        'staff_id' => $staff->id,
                        'sort_order' => 1,
                        'is_published' => '1',
                        'images' => [
                            $firstId => ['display_order' => 2],
                            $second->id => ['display_order' => 1],
                        ],
                    ],
                ],
            ])
            ->assertRedirect(route('admin.galleries.index'));

        $this->assertDatabaseHas('galleries', [
            'id' => $gallery->id,
            'staff_id' => $staff->id,
        ]);
        $this->assertDatabaseHas('gallery_images', [
            'id' => $second->id,
            'display_order' => 1,
        ]);
        $this->assertDatabaseHas('gallery_images', [
            'id' => $firstId,
            'display_order' => 2,
        ]);

        $gallery->refresh()->load('images');
        $this->assertSame($second->id, $gallery->coverImage()?->id);
    }

    public function test_public_gallery_list_uses_cover_and_hides_empty(): void
    {
        Storage::fake('public');
        $withImage = $this->createGallery([
            'image_path' => UploadedFile::fake()->image('cover.jpg')->store('galleries', 'public'),
            'caption' => '表示する',
            'is_published' => true,
        ]);
        Gallery::query()->create([
            'caption' => '画像なし',
            'sort_order' => 2,
            'is_published' => true,
        ]);

        $html = $this->get(route('gallery'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('表示する', $html);
        $this->assertStringContainsString(route('gallery.show', $withImage), $html);
        $this->assertStringNotContainsString('画像なし', $html);
        $this->assertStringContainsString('gallery-media-image', $html);
        $this->assertStringContainsString('gallery-media-card', $html);
        $this->assertStringContainsString('gallery-media-frame', $html);
        $this->assertStringNotContainsString('gallery-media-image--fluid', $html);
        // Default detail display is page transition.
        $this->assertStringNotContainsString('data-gallery-modal-trigger', $html);
        $this->assertStringNotContainsString('data-gallery-modal-data', $html);

        \App\Models\DesignSetting::current()->update([
            'gallery_detail_display' => \App\Models\DesignSetting::DETAIL_DISPLAY_MODAL,
        ]);

        $modalHtml = $this->get(route('gallery'))->assertOk()->getContent();
        $this->assertStringContainsString('data-gallery-modal', $modalHtml);
        $this->assertStringContainsString('data-gallery-modal-trigger', $modalHtml);
        $this->assertStringContainsString('data-gallery-id="'.$withImage->id.'"', $modalHtml);
        $this->assertStringContainsString('data-gallery-modal-data', $modalHtml);
        $this->assertStringContainsString('data-gallery-modal-thumbs', $modalHtml);
        $this->assertStringContainsString('"categoryLabel":"HAIR STYLE"', $modalHtml);
        $this->assertStringContainsString('canSwitchGalleries', $modalHtml);
        $this->assertStringContainsString('IMAGE_AUTO_INTERVAL_MS', $modalHtml);
        $this->assertStringContainsString('isVerticalIndicator()', $modalHtml);
    }

    public function test_home_gallery_section_hides_when_no_published_galleries(): void
    {
        \App\Models\SalonSetting::current();

        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringNotContainsString('id="gallery"', $html);
        $this->assertStringNotContainsString('ヘアギャラリー', $html);
        $this->assertStringNotContainsString('ギャラリー準備中です。', $html);
        $this->assertStringNotContainsString(route('gallery'), $html);
        // Nav Gallery follows top-page section ON/OFF only (not published count).
        $this->assertStringContainsString('/#gallery', $html);
        $this->assertStringContainsString('>Gallery</a>', $html);
    }

    public function test_home_gallery_section_shows_when_published_gallery_exists(): void
    {
        Storage::fake('public');
        \App\Models\SalonSetting::current();

        $gallery = $this->createGallery([
            'image_path' => UploadedFile::fake()->image('home-cover.jpg')->store('galleries', 'public'),
            'title' => 'ショートボブ',
            'caption' => 'ホーム表示',
            'is_published' => true,
        ]);
        $untitled = $this->createGallery([
            'image_path' => UploadedFile::fake()->image('home-cover-2.jpg')->store('galleries', 'public'),
            'title' => null,
            'caption' => 'タイトルなし',
            'sort_order' => 2,
            'is_published' => true,
        ]);

        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('id="gallery"', $html);
        $this->assertStringContainsString('ヘアギャラリー', $html);
        $this->assertStringContainsString(route('gallery.show', $gallery), $html);
        $this->assertStringContainsString(route('gallery'), $html);
        $this->assertStringContainsString('/#gallery', $html);
        $this->assertStringContainsString('>Gallery</a>', $html);
        $this->assertStringContainsString('gallery-media-card--home', $html);
        $this->assertStringContainsString('gallery-media-card--home-titled', $html);
        $this->assertStringContainsString('home-gallery-card-more', $html);
        $this->assertStringContainsString('home-gallery-card-hit', $html);
        $this->assertStringContainsString('gallery-media-tab-label', $html);
        $this->assertStringContainsString('ショートボブ', $html);
        $this->assertStringNotContainsString('data-gallery-modal-trigger', $html);

        \App\Models\DesignSetting::current()->update([
            'gallery_detail_display' => \App\Models\DesignSetting::DETAIL_DISPLAY_MODAL,
        ]);
        $modalHtml = $this->get(route('home'))->assertOk()->getContent();
        $this->assertStringContainsString('data-gallery-modal', $modalHtml);
        $this->assertStringContainsString('data-gallery-modal-trigger', $modalHtml);
        $this->assertStringContainsString('data-gallery-id="'.$gallery->id.'"', $modalHtml);

        preg_match(
            '/href="'.preg_quote(route('gallery.show', $untitled), '/').'"[\s\S]*?<\/article>/',
            $html,
            $untitledCard
        );
        $this->assertNotEmpty($untitledCard);
        $this->assertStringNotContainsString('gallery-media-card--home-titled', $untitledCard[0]);
        $this->assertStringNotContainsString('gallery-media-tab-label', $untitledCard[0]);

        $listHtml = $this->get(route('gallery'))->assertOk()->getContent();
        $this->assertStringNotContainsString('gallery-media-card--home-titled', $listHtml);
        $this->assertStringNotContainsString('gallery-media-tab-label', $listHtml);
        $this->assertStringNotContainsString('home-gallery-card-more', $listHtml);
    }

    public function test_public_gallery_detail_page_remains_available_via_direct_url(): void
    {
        Storage::fake('public');
        $gallery = $this->createGallery([
            'image_path' => UploadedFile::fake()->image('direct.jpg')->store('galleries', 'public'),
            'title' => '直接アクセス作品',
            'caption' => '詳細ページは残す',
            'is_published' => true,
        ]);

        $html = $this->get(route('gallery.show', $gallery))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('直接アクセス作品', $html);
        $this->assertStringContainsString('詳細ページは残す', $html);
        $this->assertStringContainsString('gallery-detail-layout', $html);
        $this->assertStringNotContainsString('data-gallery-modal-trigger', $html);
    }

    public function test_public_gallery_detail_shows_carousel_only_for_multiple_images(): void
    {
        Storage::fake('public');
        \App\Models\SalonSetting::current()->update([
            'hot_pepper_url' => 'https://beauty.hotpepper.jp/example',
        ]);
        $staff = StaffMember::query()->create([
            'name' => '山田花子',
            'role' => 'スタイリスト',
            'photo_path' => UploadedFile::fake()->image('staff.jpg')->store('staff', 'public'),
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $single = $this->createGallery([
            'image_path' => UploadedFile::fake()->image('one.jpg')->store('galleries', 'public'),
            'caption' => '1枚',
        ]);
        $multi = $this->createGallery([
            'image_path' => UploadedFile::fake()->image('a.jpg')->store('galleries', 'public'),
            'caption' => "ショートボブ\n外はねアレンジ",
            'sort_order' => 2,
            'staff_id' => $staff->id,
        ]);
        GalleryImage::query()->create([
            'gallery_id' => $multi->id,
            'image_path' => UploadedFile::fake()->image('b.jpg')->store('galleries', 'public'),
            'display_order' => 2,
        ]);

        $singleHtml = $this->get(route('gallery.show', $single))->assertOk()->getContent();
        $this->assertStringContainsString('1枚', $singleHtml);
        $this->assertStringContainsString('gallery-detail-image-frame', $singleHtml);
        $this->assertStringContainsString('gallery-media-image gallery-media-image--contain', $singleHtml);
        $this->assertStringNotContainsString('aspect-[3/4]', $singleHtml);
        $this->assertStringNotContainsString('data-gallery-prev', $singleHtml);
        $this->assertStringNotContainsString('data-gallery-dot', $singleHtml);
        $this->assertStringNotContainsString('data-slide-carousel', $singleHtml);
        $this->assertStringNotContainsString('担当スタイリスト', $singleHtml);

        $multiHtml = $this->get(route('gallery.show', $multi))->assertOk()->getContent();
        $this->assertStringContainsString('data-gallery-prev', $multiHtml);
        $this->assertStringContainsString('data-gallery-next', $multiHtml);
        $this->assertStringContainsString('data-gallery-dot', $multiHtml);
        $this->assertStringContainsString('data-gallery-interval="5000"', $multiHtml);
        $this->assertStringContainsString('data-slide-carousel', $multiHtml);
        $this->assertStringContainsString('data-slide-carousel-track', $multiHtml);
        $this->assertStringContainsString('data-slide-carousel-draggable', $multiHtml);
        $this->assertStringContainsString('gallery-detail-track', $multiHtml);
        $this->assertStringContainsString('aria-label="前の画像"', $multiHtml);
        $this->assertStringContainsString('aria-label="次の画像"', $multiHtml);
        $this->assertStringContainsString('画像1を表示', $multiHtml);
        $this->assertStringContainsString('gallery-media-image gallery-media-image--contain', $multiHtml);
        $this->assertStringContainsString('予約する', $multiHtml);
        $this->assertStringContainsString('gallery-detail-layout', $multiHtml);
        $this->assertStringContainsString('max-w-2xl', $multiHtml);
        $this->assertStringContainsString('担当スタイリスト', $multiHtml);
        $this->assertStringContainsString('山田花子', $multiHtml);
        $this->assertStringContainsString('ショートボブ', $multiHtml);
        $this->assertStringContainsString('whitespace-pre-line', $multiHtml);
        $this->assertStringContainsString('ギャラリー一覧へ戻る', $multiHtml);
    }

    public function test_title_and_description_are_saved_and_shown_separately(): void
    {
        Storage::fake('public');
        $gallery = $this->createGallery([
            'image_path' => UploadedFile::fake()->image('style.jpg')->store('galleries', 'public'),
            'title' => null,
            'caption' => '旧詳細',
        ]);
        $imageId = $gallery->images->first()->id;
        $description = "外はね・内巻き・切りっぱなし\nどれもアレンジ可能です♪";

        $this->actingAs($this->admin())
            ->put(route('admin.galleries.bulk-update'), [
                'galleries' => [
                    $gallery->id => [
                        'title' => 'ショートボブ',
                        'caption' => $description,
                        'sort_order' => 1,
                        'is_published' => '1',
                        'images' => [
                            $imageId => ['display_order' => 1],
                        ],
                    ],
                ],
            ])
            ->assertRedirect(route('admin.galleries.index'));

        $this->assertDatabaseHas('galleries', [
            'id' => $gallery->id,
            'title' => 'ショートボブ',
            'caption' => $description,
        ]);

        $html = $this->get(route('gallery.show', $gallery))->assertOk()->getContent();
        $this->assertStringContainsString('ショートボブ', $html);
        $this->assertStringContainsString('どれもアレンジ可能です♪', $html);
        $this->assertStringContainsString('<h1 class="mt-6 text-3xl font-semibold tracking-wide text-salon-text md:text-[2rem]">', $html);
        $this->assertMatchesRegularExpression('/<h1[^>]*>\s*ショートボブ\s*<\/h1>/u', $html);
        $this->assertDoesNotMatchRegularExpression('/whitespace-pre-line[^>]*>\s*ショートボブ/u', $html);
    }


    public function test_validation_error_uses_toast_and_preserves_prepended_new_gallery(): void
    {
        Storage::fake('public');

        $existing = $this->createGallery([
            'title' => '既存ギャラリー',
            'caption' => '既存詳細',
            'image_path' => UploadedFile::fake()->image('existing.jpg')->store('galleries', 'public'),
            'sort_order' => 1,
        ]);

        $html = $this->actingAs($this->admin())
            ->followingRedirects()
            ->from(route('admin.galleries.index'))
            ->put(route('admin.galleries.bulk-update'), [
                'galleries' => [
                    $existing->id => [
                        'title' => '既存ギャラリー',
                        'caption' => '既存詳細',
                        'sort_order' => 2,
                        'is_published' => '1',
                        'images' => [
                            $existing->images->first()->id => ['display_order' => 1],
                        ],
                    ],
                ],
                'new_galleries' => [
                    'new_1' => [
                        'title' => '先頭の新規',
                        'caption' => '新規詳細',
                        'sort_order' => 1,
                        'is_published' => '0',
                        'new_images' => [
                            'img_1' => [
                                'display_order' => 1,
                                'image' => UploadedFile::fake()->image('new.jpg', 800, 500),
                            ],
                        ],
                    ],
                    'new_2' => [
                        'title' => '画像なし新規',
                        'caption' => 'エラー用',
                        'sort_order' => 3,
                        'is_published' => '1',
                    ],
                ],
            ])
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('validationErrors', $html);
        $this->assertStringContainsString('画像は1枚以上必要です。', $html);
        $this->assertStringNotContainsString('border-red-200 bg-red-50', $html);
        $this->assertStringContainsString('data-gallery-add-top', $html);
        $this->assertStringContainsString('先頭の新規', $html);
        preg_match('/id="gallery-grid"(.*?)<div\s+id="gallery-add-card"/s', $html, $matches);
        $this->assertNotEmpty($matches);
        $grid = $matches[1];

        $posNew = strpos($grid, 'data-gallery-new');
        $posExisting = strpos($grid, 'data-gallery-id="'.$existing->id.'"');
        $this->assertNotFalse($posNew);
        $this->assertNotFalse($posExisting);
        $this->assertLessThan($posExisting, $posNew);
        $this->assertStringContainsString('storage/galleries/tmp/', $grid);
        $this->assertMatchesRegularExpression('/name="new_galleries\[new_1\]\[new_images\]\[[^"]+\]\[pending_image_path\]"/', $grid);
        $this->assertStringContainsString('name="new_galleries[new_1][is_published]" value="0"', $grid);
        $this->assertStringContainsString('>新規詳細</textarea>', $grid);
    }

    public function test_validation_error_keeps_new_gallery_pending_image_for_retry(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->from(route('admin.galleries.index'))
            ->put(route('admin.galleries.bulk-update'), [
                'new_galleries' => [
                    'new_1' => [
                        'title' => '再保存ギャラリー',
                        'caption' => '詳細',
                        'sort_order' => 1,
                        'is_published' => '1',
                        'new_images' => [
                            'img_1' => [
                                'display_order' => 1,
                                'image' => UploadedFile::fake()->image('retry.jpg', 800, 500),
                            ],
                        ],
                    ],
                    'new_2' => [
                        'title' => '画像なし',
                        'sort_order' => 2,
                        'is_published' => '1',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.galleries.index'))
            ->assertSessionHasErrors(['new_galleries.new_2.new_images']);

        $pendingPath = session()->getOldInput('new_galleries.new_1.new_images.img_1.pending_image_path');
        $this->assertIsString($pendingPath);
        Storage::disk('public')->assertExists($pendingPath);

        $this->actingAs($this->admin())
            ->put(route('admin.galleries.bulk-update'), [
                'new_galleries' => [
                    'new_1' => [
                        'title' => '再保存ギャラリー',
                        'caption' => '詳細',
                        'sort_order' => 1,
                        'is_published' => '1',
                        'new_images' => [
                            'img_1' => [
                                'display_order' => 1,
                                'pending_image_path' => $pendingPath,
                            ],
                        ],
                    ],
                ],
            ])
            ->assertRedirect(route('admin.galleries.index'))
            ->assertSessionHasNoErrors();

        $created = Gallery::query()->where('title', '再保存ギャラリー')->first();
        $this->assertNotNull($created);
        $this->assertSame(1, $created->images()->count());
        Storage::disk('public')->assertExists($created->images->first()->image_path);
        Storage::disk('public')->assertMissing($pendingPath);
    }

    public function test_caption_preserves_newlines_on_save_and_public_display(): void
    {
        Storage::fake('public');
        $gallery = $this->createGallery([
            'image_path' => UploadedFile::fake()->image('cap.jpg')->store('galleries', 'public'),
            'caption' => '旧',
        ]);
        $imageId = $gallery->images->first()->id;
        $caption = "ショートボブ外はね・内巻き・切りっぱなし\nどれもアレンジ可能です♪\n\nオン眉が可愛いスタイル。";

        $this->actingAs($this->admin())
            ->put(route('admin.galleries.bulk-update'), [
                'galleries' => [
                    $gallery->id => [
                        'caption' => $caption,
                        'sort_order' => 1,
                        'is_published' => '1',
                        'images' => [
                            $imageId => ['display_order' => 1],
                        ],
                    ],
                ],
            ])
            ->assertRedirect(route('admin.galleries.index'));

        $this->assertDatabaseHas('galleries', [
            'id' => $gallery->id,
            'caption' => $caption,
        ]);

        $indexHtml = $this->actingAs($this->admin())
            ->get(route('admin.galleries.index'))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('<textarea', $indexHtml);
        $this->assertStringContainsString('rows="4"', $indexHtml);
        $this->assertStringContainsString('ショートボブ外はね・内巻き・切りっぱなし', $indexHtml);

        $publicHtml = $this->get(route('gallery.show', $gallery))->assertOk()->getContent();
        $this->assertStringContainsString('whitespace-pre-line', $publicHtml);
        $this->assertStringContainsString('どれもアレンジ可能です♪', $publicHtml);
        $this->assertStringContainsString('オン眉が可愛いスタイル。', $publicHtml);
    }

    public function test_fallback_create_and_edit_pages_still_available(): void
    {
        $gallery = $this->createGallery();

        $this->actingAs($this->admin())
            ->get(route('admin.galleries.create'))
            ->assertOk()
            ->assertSee('ギャラリー登録');

        $this->actingAs($this->admin())
            ->get(route('admin.galleries.edit', $gallery))
            ->assertOk()
            ->assertSee('ギャラリー編集')
            ->assertSee($gallery->caption);
    }

    public function test_fallback_store_and_update_still_work(): void
    {
        Storage::fake('public');
        $image = UploadedFile::fake()->image('gallery.jpg', 200, 200);

        $this->actingAs($this->admin())
            ->post(route('admin.galleries.store'), [
                'image' => $image,
                'caption' => '通常登録',
                'sort_order' => 1,
                'is_published' => '1',
            ])
            ->assertRedirect(route('admin.galleries.index'))
            ->assertSessionHas('success', 'ギャラリー画像を登録しました。');

        $gallery = Gallery::query()->where('caption', '通常登録')->with('images')->first();
        $this->assertNotNull($gallery);
        $this->assertCount(1, $gallery->images);

        $this->actingAs($this->admin())
            ->put(route('admin.galleries.update', $gallery), [
                'caption' => '通常更新',
                'sort_order' => 2,
                'is_published' => '1',
            ])
            ->assertRedirect(route('admin.galleries.index'))
            ->assertSessionHas('success', 'ギャラリー画像を更新しました。');

        $this->assertDatabaseHas('galleries', [
            'id' => $gallery->id,
            'caption' => '通常更新',
            'sort_order' => 2,
        ]);
    }

    public function test_migration_keeps_existing_image_as_first_gallery_image(): void
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->image('legacy.jpg')->store('galleries', 'public');
        $gallery = $this->createGallery([
            'image_path' => $path,
            'caption' => '移行',
        ]);

        $this->assertDatabaseHas('gallery_images', [
            'gallery_id' => $gallery->id,
            'image_path' => $path,
            'display_order' => 1,
        ]);
        $this->assertSame($path, $gallery->coverImagePath());
    }
}
