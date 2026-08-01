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

        $response = $this->actingAs($user)->put(route('admin.settings.update'), [
            'shop_name' => 'Test Salon',
            'shop_name_display_type' => 'text',
            'new_hero_images' => [
                UploadedFile::fake()->image('a.jpg'),
                UploadedFile::fake()->image('b.jpg'),
            ],
        ]);

        $response->assertRedirect(route('admin.settings.edit'));
        $this->assertSame(2, $setting->heroImages()->count());

        $images = $setting->heroImages()->ordered()->get();
        $first = $images[0];
        $second = $images[1];

        $this->actingAs($user)->put(route('admin.settings.update'), [
            'shop_name' => 'Test Salon',
            'shop_name_display_type' => 'text',
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
        ])->assertRedirect(route('admin.settings.edit'));

        $first->refresh();
        $second->refresh();
        $this->assertSame(2, $first->sort_order);
        $this->assertSame('正面', $first->alt_text);
        $this->assertTrue($first->is_published);
        $this->assertFalse($second->is_published);
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
    }

    public function test_home_page_falls_back_to_gradient_when_no_published_images(): void
    {
        SalonSetting::current();

        $response = $this->get(route('home'));
        $response->assertOk();
        $response->assertSee('bg-gradient-to-br', false);
    }

    public function test_deleting_hero_image_removes_db_row_and_file(): void
    {
        Storage::fake('public');
        $setting = SalonSetting::current();
        $path = UploadedFile::fake()->image('delete-me.jpg')->store('settings', 'public');

        $image = HeroImage::query()->create([
            'salon_setting_id' => $setting->id,
            'image_path' => $path,
            'sort_order' => 1,
            'is_published' => true,
        ]);

        Storage::disk('public')->assertExists($path);

        $this->actingAs($this->admin())
            ->delete(route('admin.settings.hero-images.destroy', $image))
            ->assertRedirect(route('admin.settings.edit'));

        $this->assertDatabaseMissing('hero_images', ['id' => $image->id]);
        Storage::disk('public')->assertMissing($path);
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

        $this->actingAs($user)->from(route('admin.settings.edit'))->put(route('admin.settings.update'), [
            'shop_name' => 'Test Salon',
            'shop_name_display_type' => 'text',
            'new_hero_images' => [
                UploadedFile::fake()->image('extra.jpg'),
            ],
        ])->assertRedirect(route('admin.settings.edit'))
            ->assertSessionHasErrors('new_hero_images');

        $this->assertSame(HeroImage::MAX_COUNT, $setting->heroImages()->count());
    }

    public function test_settings_page_does_not_reference_legacy_hero_image_column_for_display(): void
    {
        Storage::fake('public');
        $setting = SalonSetting::current();
        $setting->update(['hero_image' => 'settings/legacy-only.png']);

        $response = $this->actingAs($this->admin())->get(route('admin.settings.edit'));
        $response->assertOk();
        $response->assertDontSee('storage/settings/legacy-only.png', false);
        $response->assertSee('現在登録されている画像はありません', false);
    }
}
