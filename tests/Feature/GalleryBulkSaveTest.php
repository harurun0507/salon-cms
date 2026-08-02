<?php

namespace Tests\Feature;

use App\Models\Gallery;
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
        return Gallery::query()->create(array_merge([
            'image_path' => 'galleries/sample.jpg',
            'caption' => 'サンプルキャプション',
            'sort_order' => 1,
            'is_published' => true,
        ], $overrides));
    }

    public function test_gallery_index_shows_inline_cards_and_bulk_save_without_modals(): void
    {
        $gallery = $this->createGallery();

        $html = $this->actingAs($this->admin())
            ->get(route('admin.galleries.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="galleries-bulk-form"', $html);
        $this->assertStringContainsString('data-gallery-workspace', $html);
        $this->assertStringContainsString('grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3', $html);
        $this->assertStringContainsString('id="gallery-add-card"', $html);
        $this->assertStringContainsString('data-gallery-add', $html);
        $this->assertStringContainsString('btn-admin-create', $html);
        $this->assertStringContainsString('カードを追加し、一括保存で登録できます。', $html);
        $this->assertStringContainsString('addCard.before(card)', $html);
        $this->assertStringContainsString('addButton.addEventListener', $html);
        $this->assertStringContainsString('data-gallery-card', $html);
        $this->assertStringContainsString('data-gallery-existing', $html);
        $this->assertStringContainsString('name="galleries['.$gallery->id.'][caption]"', $html);
        $this->assertStringContainsString('name="galleries['.$gallery->id.'][sort_order]"', $html);
        $this->assertStringContainsString('gallery-card-meta', $html);
        $this->assertStringContainsString('gallery-card-meta-sort', $html);
        $this->assertStringContainsString('gallery-card-meta-published', $html);
        $this->assertStringContainsString('gallery-sort-input', $html);
        $this->assertStringContainsString('gallery-card-published', $html);
        $this->assertStringNotContainsString('sm:grid-cols-[5.5rem_1fr]', $html);
        $this->assertStringContainsString('menu-published-control', $html);
        $this->assertStringContainsString('menu-published-checkbox', $html);
        $this->assertStringContainsString('data-published-text', $html);
        $this->assertStringContainsString('gallery-image-label', $html);
        $this->assertStringContainsString('画像1', $html);
        $this->assertStringContainsString('justify-between gap-3', $html);
        $this->assertStringContainsString('min-h-[10.5rem]', $html);
        $this->assertStringContainsString('function renumberGalleryTitles', $html);
        $this->assertStringContainsString('querySelectorAll(\'[data-gallery-card]\')', $html);
        $this->assertStringContainsString('renumberGalleryTitles();', $html);
        $this->assertStringContainsString('admin-icon-btn-delete', $html);
        $this->assertStringContainsString('data-gallery-remove', $html);
        $this->assertStringContainsString('sticky top-[4.5rem]', $html);
        $this->assertStringContainsString('-mx-4 -mt-4 mb-6', $html);
        $this->assertStringContainsString('一括保存', $html);
        $this->assertStringContainsString('data-admin-confirm-trigger', $html);
        $this->assertStringContainsString('data-confirm-form="galleries-bulk-form"', $html);
        $this->assertStringNotContainsString('ギャラリー画像がありません。', $html);
        $this->assertStringNotContainsString('id="gallery-empty-message"', $html);
        $this->assertStringNotContainsString('updateEmptyState', $html);
        $this->assertStringNotContainsString('id="gallery-create-modal"', $html);
        $this->assertStringNotContainsString('id="gallery-edit-modal"', $html);
        $this->assertStringNotContainsString('id="gallery-edit-data"', $html);
        $this->assertStringNotContainsString('data-open-gallery-create', $html);
        $this->assertStringNotContainsString('data-open-gallery-edit', $html);
        $this->assertStringNotContainsString('ギャラリー登録', $html);
        $this->assertStringNotContainsString('ギャラリー編集', $html);
        $this->assertStringNotContainsString(
            'href="'.route('admin.galleries.create').'"',
            $html
        );
        $this->assertStringNotContainsString(
            'href="'.route('admin.galleries.edit', $gallery).'"',
            $html
        );
    }

    public function test_gallery_index_shows_add_card_when_empty(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('admin.galleries.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="gallery-add-card"', $html);
        $this->assertStringContainsString('btn-admin-create', $html);
        $this->assertStringContainsString('data-gallery-add', $html);
        $this->assertStringContainsString('画像を追加', $html);
        $this->assertStringContainsString('カードを追加し、一括保存で登録できます。', $html);
        $this->assertStringNotContainsString('ギャラリー画像がありません。', $html);
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
        $removePath = $remove->image_path;

        $newImage = UploadedFile::fake()->image('new.jpg', 200, 200);
        $replaceImage = UploadedFile::fake()->image('replace.jpg', 200, 200);

        $this->actingAs($this->admin())
            ->put(route('admin.galleries.bulk-update'), [
                'galleries' => [
                    $keep->id => [
                        'caption' => '更新後',
                        'sort_order' => 5,
                        'is_published' => '0',
                        'image' => $replaceImage,
                    ],
                ],
                'new_galleries' => [
                    'new_1' => [
                        'caption' => '新規',
                        'sort_order' => 3,
                        'is_published' => '1',
                        'image' => $newImage,
                    ],
                ],
                'deleted_ids' => [$remove->id],
            ])
            ->assertRedirect(route('admin.galleries.index'))
            ->assertSessionHas('success', 'ギャラリーを一括保存しました。');

        $this->assertDatabaseHas('galleries', [
            'id' => $keep->id,
            'caption' => '更新後',
            'sort_order' => 5,
            'is_published' => false,
        ]);
        $this->assertDatabaseMissing('galleries', ['id' => $remove->id]);
        $this->assertDatabaseHas('galleries', [
            'caption' => '新規',
            'sort_order' => 3,
            'is_published' => true,
        ]);

        Storage::disk('public')->assertMissing($removePath);
        $keep->refresh();
        $this->assertNotSame('galleries/sample.jpg', $keep->image_path);
        Storage::disk('public')->assertExists($keep->image_path);
    }

    public function test_bulk_update_without_new_image_keeps_existing_image(): void
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->image('keep.jpg')->store('galleries', 'public');
        $gallery = $this->createGallery(['image_path' => $path, 'caption' => '旧']);

        $this->actingAs($this->admin())
            ->put(route('admin.galleries.bulk-update'), [
                'galleries' => [
                    $gallery->id => [
                        'caption' => '画像維持',
                        'sort_order' => 2,
                        'is_published' => '1',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.galleries.index'));

        $this->assertDatabaseHas('galleries', [
            'id' => $gallery->id,
            'image_path' => $path,
            'caption' => '画像維持',
            'sort_order' => 2,
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
            ->assertSessionHasErrors('new_galleries.new_1.image');

        $this->assertDatabaseMissing('galleries', ['caption' => '画像なし']);
    }

    public function test_bulk_update_rejects_invalid_image_mime(): void
    {
        Storage::fake('public');
        $gallery = $this->createGallery([
            'image_path' => UploadedFile::fake()->image('ok.jpg')->store('galleries', 'public'),
        ]);

        $this->actingAs($this->admin())
            ->from(route('admin.galleries.index'))
            ->put(route('admin.galleries.bulk-update'), [
                'galleries' => [
                    $gallery->id => [
                        'caption' => $gallery->caption,
                        'sort_order' => 1,
                        'is_published' => '1',
                        'image' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
                    ],
                ],
            ])
            ->assertRedirect(route('admin.galleries.index'))
            ->assertSessionHasErrors('galleries.'.$gallery->id.'.image');
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

        $gallery = Gallery::query()->where('caption', '通常登録')->first();
        $this->assertNotNull($gallery);

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
}
