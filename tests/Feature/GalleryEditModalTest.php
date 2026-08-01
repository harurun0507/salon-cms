<?php

namespace Tests\Feature;

use App\Models\Gallery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GalleryEditModalTest extends TestCase
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

    public function test_gallery_index_embeds_edit_modal_and_json_without_edit_link(): void
    {
        $gallery = $this->createGallery();

        $html = $this->actingAs($this->admin())
            ->get(route('admin.galleries.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="gallery-edit-modal"', $html);
        $this->assertStringContainsString('id="gallery-edit-data"', $html);
        $this->assertStringContainsString('id="admin-toast-stack"', $html);
        $this->assertStringContainsString('id="admin-flash-data"', $html);
        $this->assertStringContainsString('data-open-gallery-edit', $html);
        $this->assertStringContainsString('role="dialog"', $html);
        $this->assertStringContainsString('ギャラリー編集', $html);
        $this->assertStringContainsString('サンプルキャプション', $html);
        $this->assertStringNotContainsString(
            'href="'.route('admin.galleries.edit', $gallery).'"',
            $html
        );
    }

    public function test_edit_page_still_available_as_fallback(): void
    {
        $gallery = $this->createGallery();

        $this->actingAs($this->admin())
            ->get(route('admin.galleries.edit', $gallery))
            ->assertOk()
            ->assertSee('ギャラリー編集')
            ->assertSee($gallery->caption);
    }

    public function test_ajax_update_returns_json_and_persists_changes(): void
    {
        Storage::fake('public');
        $gallery = $this->createGallery([
            'image_path' => UploadedFile::fake()->image('old.jpg')->store('galleries', 'public'),
        ]);
        $image = UploadedFile::fake()->image('gallery.jpg', 200, 200);

        $response = $this->actingAs($this->admin())
            ->putJson(route('admin.galleries.update', $gallery), [
                'caption' => '更新後キャプション',
                'sort_order' => 3,
                'is_published' => false,
                'image' => $image,
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'ギャラリーを更新しました。')
            ->assertJsonPath('gallery.caption', '更新後キャプション')
            ->assertJsonPath('gallery.sort_order', 3)
            ->assertJsonPath('gallery.is_published', false);

        $this->assertNotNull($response->json('gallery.image_url'));
        $this->assertNotNull($response->json('gallery.image_path'));

        $this->assertDatabaseHas('galleries', [
            'id' => $gallery->id,
            'caption' => '更新後キャプション',
            'sort_order' => 3,
            'is_published' => false,
        ]);
    }

    public function test_ajax_update_without_image_keeps_existing_image(): void
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->image('keep.jpg')->store('galleries', 'public');
        $gallery = $this->createGallery(['image_path' => $path]);

        $this->actingAs($this->admin())
            ->putJson(route('admin.galleries.update', $gallery), [
                'caption' => '画像維持',
                'sort_order' => 1,
                'is_published' => true,
            ])
            ->assertOk()
            ->assertJsonPath('gallery.image_path', $path);

        $this->assertDatabaseHas('galleries', [
            'id' => $gallery->id,
            'image_path' => $path,
            'caption' => '画像維持',
        ]);
    }

    public function test_normal_update_still_redirects_to_index(): void
    {
        $gallery = $this->createGallery();

        $this->actingAs($this->admin())
            ->put(route('admin.galleries.update', $gallery), [
                'caption' => '通常更新',
                'sort_order' => 2,
                'is_published' => '1',
            ])
            ->assertRedirect(route('admin.galleries.index'))
            ->assertSessionHas('success', 'ギャラリー画像を更新しました。');
    }

    public function test_ajax_update_validation_errors_return_json_422(): void
    {
        $gallery = $this->createGallery();

        $this->actingAs($this->admin())
            ->putJson(route('admin.galleries.update', $gallery), [
                'image' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['image']);

        $this->assertDatabaseHas('galleries', [
            'id' => $gallery->id,
            'caption' => 'サンプルキャプション',
            'image_path' => 'galleries/sample.jpg',
        ]);
    }
}
