<?php

namespace Tests\Feature;

use App\Models\SalonSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ShopNameLogoTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    public function test_header_shows_shop_name_text_by_default(): void
    {
        $setting = SalonSetting::current();
        $setting->update([
            'shop_name' => 'Sun＆ Me',
            'shop_name_display_type' => SalonSetting::DISPLAY_TYPE_TEXT,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Sun＆ Me', false)
            ->assertDontSee('settings/logos/', false);
    }

    public function test_header_shows_logo_when_configured(): void
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->image('logo.png')->store('settings/logos', 'public');

        SalonSetting::current()->update([
            'shop_name' => 'Sun＆ Me',
            'shop_name_display_type' => SalonSetting::DISPLAY_TYPE_LOGO,
            'logo_image' => $path,
            'logo_alt_text' => 'サロンロゴ',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('storage/'.$path, false)
            ->assertSee('alt="サロンロゴ"', false)
            ->assertSee('object-contain', false)
            ->assertSee('max-h-[56px]', false)
            ->assertSee('md:max-h-[72px]', false);
    }

    public function test_header_falls_back_to_text_when_logo_missing(): void
    {
        SalonSetting::current()->update([
            'shop_name' => 'Fallback Salon',
            'shop_name_display_type' => SalonSetting::DISPLAY_TYPE_LOGO,
            'logo_image' => null,
        ]);

        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('Fallback Salon', $html);
        $this->assertStringNotContainsString('object-contain object-left', $html);
    }

    public function test_logo_alt_falls_back_to_shop_name(): void
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->image('logo.png')->store('settings/logos', 'public');

        SalonSetting::current()->update([
            'shop_name' => 'Alt Fallback',
            'shop_name_display_type' => SalonSetting::DISPLAY_TYPE_LOGO,
            'logo_image' => $path,
            'logo_alt_text' => null,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('alt="Alt Fallback"', false);
    }

    public function test_admin_can_upload_logo_and_switch_display_type(): void
    {
        Storage::fake('public');
        $user = $this->admin();

        $this->actingAs($user)->put(route('admin.settings.update'), [
            'shop_name' => 'Logo Salon',
            'shop_name_display_type' => SalonSetting::DISPLAY_TYPE_LOGO,
            'logo_alt_text' => 'ロゴ',
            'logo_image' => UploadedFile::fake()->image('brand.png'),
        ])->assertRedirect(route('admin.settings.edit'));

        $setting = SalonSetting::current()->fresh();
        $this->assertSame(SalonSetting::DISPLAY_TYPE_LOGO, $setting->shop_name_display_type);
        $this->assertNotNull($setting->logo_image);
        Storage::disk('public')->assertExists($setting->logo_image);
    }

    public function test_replacing_logo_deletes_old_file(): void
    {
        Storage::fake('public');
        $user = $this->admin();
        $oldPath = UploadedFile::fake()->image('old.png')->store('settings/logos', 'public');

        SalonSetting::current()->update([
            'shop_name' => 'Salon',
            'shop_name_display_type' => SalonSetting::DISPLAY_TYPE_LOGO,
            'logo_image' => $oldPath,
        ]);

        $this->actingAs($user)->put(route('admin.settings.update'), [
            'shop_name' => 'Salon',
            'shop_name_display_type' => SalonSetting::DISPLAY_TYPE_LOGO,
            'logo_image' => UploadedFile::fake()->image('new.png'),
        ])->assertRedirect(route('admin.settings.edit'));

        Storage::disk('public')->assertMissing($oldPath);
        $this->assertNotSame($oldPath, SalonSetting::current()->fresh()->logo_image);
    }

    public function test_deleting_logo_removes_db_and_file(): void
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->image('delete-logo.png')->store('settings/logos', 'public');

        SalonSetting::current()->update([
            'shop_name' => 'Salon',
            'shop_name_display_type' => SalonSetting::DISPLAY_TYPE_LOGO,
            'logo_image' => $path,
        ]);

        $this->actingAs($this->admin())
            ->delete(route('admin.settings.logo.destroy'))
            ->assertRedirect(route('admin.settings.edit'));

        $this->assertNull(SalonSetting::current()->fresh()->logo_image);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_settings_page_uses_custom_shop_name_display_radios(): void
    {
        SalonSetting::current();

        $html = $this->actingAs($this->admin())
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('admin-radio-group', $html);
        $this->assertStringContainsString('admin-radio-control', $html);
        $this->assertStringContainsString('class="admin-radio"', $html);
        $this->assertStringContainsString('name="shop_name_display_type"', $html);
        $this->assertStringContainsString('value="text"', $html);
        $this->assertStringContainsString('value="logo"', $html);
        $this->assertStringContainsString('文字で表示', $html);
        $this->assertStringContainsString('ロゴ画像で表示', $html);
    }
}
