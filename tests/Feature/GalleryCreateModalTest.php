<?php

namespace Tests\Feature;

use App\Models\Gallery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GalleryCreateModalTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    public function test_gallery_index_embeds_create_modal_without_create_link_on_button(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('admin.galleries.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="gallery-create-modal"', $html);
        $this->assertStringContainsString('data-open-gallery-create', $html);
        $this->assertStringContainsString('id="gallery-card-template"', $html);
        $this->assertStringContainsString('role="dialog"', $html);
        $this->assertStringContainsString('ギャラリー登録', $html);

        $this->assertDoesNotMatchRegularExpression(
            '/data-open-gallery-create[^>]*(?:href\s*=\s*["\'][^"\']*galleries\/create|href\s*=\s*["\'][^"\']*'.preg_quote(parse_url(route('admin.galleries.create'), PHP_URL_PATH), '/').')/',
            $html
        );
        $this->assertStringNotContainsString(
            'href="'.route('admin.galleries.create').'"',
            $html
        );
    }

    public function test_create_page_still_available_as_fallback(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.galleries.create'))
            ->assertOk()
            ->assertSee('ギャラリー登録');
    }

    public function test_ajax_store_returns_json_and_persists_gallery(): void
    {
        Storage::fake('public');
        $image = UploadedFile::fake()->image('gallery.jpg', 200, 200);

        $response = $this->actingAs($this->admin())
            ->postJson(route('admin.galleries.store'), [
                'image' => $image,
                'caption' => '新規キャプション',
                'sort_order' => 2,
                'is_published' => true,
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'ギャラリーを登録しました。')
            ->assertJsonPath('gallery.caption', '新規キャプション')
            ->assertJsonPath('gallery.sort_order', 2)
            ->assertJsonPath('gallery.is_published', true);

        $this->assertNotNull($response->json('gallery.id'));
        $this->assertNotNull($response->json('gallery.image_url'));
        $this->assertNotNull($response->json('gallery.image_path'));
        $this->assertNotNull($response->json('gallery.update_url'));
        $this->assertNotNull($response->json('gallery.destroy_url'));

        $this->assertDatabaseHas('galleries', [
            'caption' => '新規キャプション',
            'sort_order' => 2,
            'is_published' => true,
        ]);
    }

    public function test_normal_store_still_redirects_with_flash(): void
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

        $this->assertDatabaseHas('galleries', [
            'caption' => '通常登録',
            'sort_order' => 1,
        ]);
    }

    public function test_ajax_store_without_image_returns_422(): void
    {
        $this->actingAs($this->admin())
            ->postJson(route('admin.galleries.store'), [
                'caption' => '画像なし',
                'sort_order' => 0,
                'is_published' => true,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['image']);

        $this->assertDatabaseMissing('galleries', [
            'caption' => '画像なし',
        ]);
    }

    public function test_ajax_store_with_bad_mime_returns_422(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->postJson(route('admin.galleries.store'), [
                'image' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
                'caption' => '不正MIME',
                'sort_order' => 0,
                'is_published' => true,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['image']);

        $this->assertDatabaseMissing('galleries', [
            'caption' => '不正MIME',
        ]);
    }
}
