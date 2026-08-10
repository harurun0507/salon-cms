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

    public function test_settings_page_uses_segmented_shop_name_display(): void
    {
        SalonSetting::current();

        $html = $this->actingAs($this->admin())
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('admin-segmented', $html);
        $this->assertStringContainsString('admin-segmented-option', $html);
        $this->assertStringContainsString('admin-segmented-input', $html);
        $this->assertStringContainsString('name="shop_name_display_type"', $html);
        $this->assertStringContainsString('value="text"', $html);
        $this->assertStringContainsString('value="logo"', $html);
        $this->assertStringContainsString('文字で表示', $html);
        $this->assertStringContainsString('ロゴ画像で表示', $html);
        $this->assertStringNotContainsString('admin-radio-group', $html);
        $this->assertStringNotContainsString('class="admin-radio"', $html);
    }

    public function test_settings_page_keeps_basic_fields_only(): void
    {
        SalonSetting::current();

        $html = $this->actingAs($this->admin())
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('店舗表示', $html);
        $this->assertStringContainsString('公開サイトのヘッダーに表示する店名・ロゴを設定します。', $html);
        $this->assertStringContainsString('住所、営業時間、定休日、電話番号などの基本情報を設定します。', $html);
        $this->assertStringContainsString('サービス・補足情報', $html);
        $this->assertStringContainsString('Googleマップへのリンクと埋め込み表示を設定します。', $html);
        $this->assertStringContainsString('Googleマップ リンクURL', $html);
        $this->assertStringContainsString('Googleマップ 埋め込みURL', $html);
        $this->assertStringContainsString('banner-dropzone', $html);
        $this->assertStringContainsString('ここにロゴ画像をドロップしてください', $html);
        $this->assertStringContainsString('JPEG・PNG・WebP、5MBまで。', $html);
        $this->assertStringContainsString('name="shop_name"', $html);
        $this->assertStringContainsString('name="address"', $html);
        $this->assertStringContainsString('name="access_directions"', $html);
        $this->assertStringContainsString('name="business_hours"', $html);
        $this->assertStringContainsString('name="closed_days"', $html);
        $this->assertStringContainsString('name="phone"', $html);
        $this->assertStringContainsString('name="payment_methods"', $html);
        $this->assertStringContainsString('name="cut_price"', $html);
        $this->assertStringContainsString('name="seat_count"', $html);
        $this->assertStringContainsString('name="staff_count"', $html);
        $this->assertStringContainsString('name="parking"', $html);
        $this->assertStringContainsString('name="commitment_conditions"', $html);
        $this->assertStringContainsString('name="notes"', $html);
        $this->assertStringContainsString('name="other_info"', $html);
        $this->assertStringContainsString('name="google_map_url"', $html);
        $this->assertStringContainsString('name="google_map_embed_url"', $html);
        $this->assertStringNotContainsString('Google Map リンクURL', $html);
        $this->assertStringNotContainsString('name="hero_label"', $html);
        $this->assertStringNotContainsString('name="hero_title"', $html);
        $this->assertStringNotContainsString('name="concept_title"', $html);
        $this->assertStringNotContainsString('name="concept"', $html);
        $this->assertStringNotContainsString('name="instagram_url"', $html);
        $this->assertStringNotContainsString('name="hot_pepper_url"', $html);
    }

    public function test_settings_update_does_not_clear_moved_fields(): void
    {
        $setting = SalonSetting::current();
        $setting->update([
            'shop_name' => 'Before',
            'hero_label' => 'Keep Label',
            'hero_title' => "Keep\nTitle",
            'concept_title' => 'Keep Concept',
            'concept' => 'Keep body',
            'instagram_url' => 'https://instagram.com/keep',
            'hot_pepper_url' => 'https://beauty.hotpepper.jp/keep',
        ]);

        $this->actingAs($this->admin())->put(route('admin.settings.update'), [
            'shop_name' => 'After',
            'shop_name_display_type' => SalonSetting::DISPLAY_TYPE_TEXT,
            'address' => '東京都',
        ])->assertRedirect(route('admin.settings.edit'));

        $fresh = SalonSetting::current()->fresh();
        $this->assertSame('After', $fresh->shop_name);
        $this->assertSame('東京都', $fresh->address);
        $this->assertSame('Keep Label', $fresh->hero_label);
        $this->assertSame("Keep\nTitle", $fresh->hero_title);
        $this->assertSame('Keep Concept', $fresh->concept_title);
        $this->assertSame('Keep body', $fresh->concept);
        $this->assertSame('https://instagram.com/keep', $fresh->instagram_url);
        $this->assertSame('https://beauty.hotpepper.jp/keep', $fresh->hot_pepper_url);
    }

    public function test_settings_can_save_and_show_store_detail_fields(): void
    {
        $payload = [
            'shop_name' => 'Sun＆ Me',
            'shop_name_display_type' => SalonSetting::DISPLAY_TYPE_TEXT,
            'address' => '埼玉県川口市幸町２－14－27－102号',
            'access_directions' => "銀座通り商店街を抜けて進みます。\n黒い看板が目印です。",
            'business_hours' => "平日 10:00 - 20:00\n土日祝 9:00 - 19:00",
            'closed_days' => '毎週火曜日・第3水曜日',
            'phone' => '0120-111-1111',
            'payment_methods' => 'Visa／Mastercard／JCB',
            'cut_price' => '¥5,940',
            'seat_count' => 'セット面3席',
            'staff_count' => 'スタイリスト1人',
            'parking' => "なし\n近隣のパーキングをご利用ください",
            'commitment_conditions' => '4席以下の小型サロン／禁煙',
            'notes' => '施術中はお電話に出られない場合があります。',
            'other_info' => 'ポイント利用OK、メンズにもオススメ',
        ];

        $this->actingAs($this->admin())
            ->put(route('admin.settings.update'), $payload)
            ->assertRedirect(route('admin.settings.edit'));

        $fresh = SalonSetting::current()->fresh();
        $this->assertSame($payload['access_directions'], $fresh->access_directions);
        $this->assertSame('¥5,940', $fresh->cut_price);
        $this->assertSame('セット面3席', $fresh->seat_count);
        $this->assertSame('スタイリスト1人', $fresh->staff_count);
        $this->assertSame($payload['parking'], $fresh->parking);
        $this->assertSame($payload['commitment_conditions'], $fresh->commitment_conditions);
        $this->assertSame($payload['notes'], $fresh->notes);
        $this->assertSame($payload['other_info'], $fresh->other_info);

        $editHtml = $this->actingAs($this->admin())
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('¥5,940', $editHtml);
        $this->assertStringContainsString('セット面3席', $editHtml);

        $accessHtml = $this->get(route('access'))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('アクセス・道案内', $accessHtml);
        $this->assertStringContainsString('黒い看板が目印です。', $accessHtml);
        $this->assertStringContainsString('支払い方法', $accessHtml);
        $this->assertStringContainsString('カット価格', $accessHtml);
        $this->assertStringContainsString('¥5,940', $accessHtml);
        $this->assertStringContainsString('こだわり条件', $accessHtml);
        $this->assertStringContainsString('施術中はお電話に出られない場合があります。', $accessHtml);
        $this->assertStringNotContainsString('>備考</p>', $accessHtml);
        $this->assertStringContainsString('その他', $accessHtml);
        $this->assertStringContainsString('ポイント利用OK、メンズにもオススメ', $accessHtml);

        // 備考は電話番号の直後に補足として出る（独立ラベルではない）
        $phonePos = strpos($accessHtml, '0120-111-1111');
        $notesPos = strpos($accessHtml, '施術中はお電話に出られない場合があります。');
        $otherPos = strpos($accessHtml, 'ポイント利用OK、メンズにもオススメ');
        $this->assertNotFalse($phonePos);
        $this->assertNotFalse($notesPos);
        $this->assertNotFalse($otherPos);
        $this->assertGreaterThan($phonePos, $notesPos);
        $this->assertGreaterThan($notesPos, $otherPos);
    }

    public function test_public_access_hides_empty_store_detail_fields(): void
    {
        SalonSetting::current()->update([
            'shop_name' => 'Sun＆ Me',
            'address' => '埼玉県川口市',
            'business_hours' => '10:00 - 20:00',
            'closed_days' => '火曜定休',
            'phone' => '0120-111-1111',
            'access_directions' => null,
            'payment_methods' => null,
            'cut_price' => null,
            'seat_count' => null,
            'staff_count' => null,
            'parking' => null,
            'commitment_conditions' => null,
            'notes' => null,
            'other_info' => null,
        ]);

        $html = $this->get(route('access'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('住所', $html);
        $this->assertStringContainsString('埼玉県川口市', $html);
        $this->assertStringNotContainsString('アクセス・道案内', $html);
        $this->assertStringNotContainsString('支払い方法', $html);
        $this->assertStringNotContainsString('カット価格', $html);
        $this->assertStringNotContainsString('席数', $html);
        $this->assertStringNotContainsString('スタッフ数', $html);
        $this->assertStringNotContainsString('駐車場', $html);
        $this->assertStringNotContainsString('こだわり条件', $html);
        $this->assertStringNotContainsString('備考', $html);
        $this->assertStringNotContainsString('その他', $html);
    }
}
