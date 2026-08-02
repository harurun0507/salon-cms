<?php

namespace Tests\Feature;

use App\Models\SalonSetting;
use App\Models\SocialLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialLinksTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    private function editor(): User
    {
        return User::factory()->editor()->create();
    }

    private function snsPayload(array $overrides = []): array
    {
        $links = [];
        foreach (config('social_links.services') as $key => $meta) {
            $links[$key] = array_merge([
                'url' => null,
                'is_visible' => '0',
                'display_order' => (int) $meta['default_order'],
            ], $overrides[$key] ?? []);
        }

        return ['links' => $links];
    }

    public function test_migration_seeds_services_and_copies_legacy_instagram_url(): void
    {
        SalonSetting::current()->update([
            'instagram_url' => 'https://instagram.com/legacy-salon',
        ]);

        // Simulate post-migration empty Instagram row + legacy column present.
        SocialLink::query()->where('service_key', 'instagram')->update([
            'url' => null,
            'is_visible' => false,
        ]);

        $links = SocialLink::ensureDefaults();

        $this->assertCount(7, $links);
        $this->assertSame(
            ['instagram', 'line', 'youtube', 'tiktok', 'facebook', 'x', 'threads'],
            $links->pluck('service_key')->all()
        );

        $instagram = $links->firstWhere('service_key', 'instagram');
        $this->assertSame('https://instagram.com/legacy-salon', $instagram->url);
        $this->assertTrue($instagram->is_visible);
    }

    public function test_admin_and_public_show_migrated_instagram(): void
    {
        SalonSetting::current()->update([
            'instagram_url' => 'https://instagram.com/migrated',
        ]);
        SocialLink::query()->where('service_key', 'instagram')->update([
            'url' => null,
            'is_visible' => false,
        ]);

        $adminHtml = $this->actingAs($this->admin())
            ->get(route('admin.store.sns'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('https://instagram.com/migrated', $adminHtml);
        $this->assertStringContainsString('name="links[instagram][url]"', $adminHtml);

        $footer = $this->get(route('home'))->assertOk()->getContent();
        $this->assertStringContainsString('https://instagram.com/migrated', $footer);
        $this->assertStringContainsString('rel="noopener noreferrer"', $footer);
        $this->assertStringContainsString('target="_blank"', $footer);

        $access = $this->get(route('access'))->assertOk()->getContent();
        $this->assertStringContainsString('https://instagram.com/migrated', $access);
        $this->assertStringContainsString('Instagram', $access);
    }

    public function test_save_multiple_services_visibility_and_order(): void
    {
        $payload = $this->snsPayload([
            'line' => [
                'url' => 'https://lin.ee/abc',
                'is_visible' => '1',
                'display_order' => 1,
            ],
            'instagram' => [
                'url' => 'https://instagram.com/salon',
                'is_visible' => '1',
                'display_order' => 2,
            ],
            'youtube' => [
                'url' => 'https://youtu.be/channel',
                'is_visible' => '0',
                'display_order' => 3,
            ],
            'x' => [
                'url' => 'https://x.com/salon',
                'is_visible' => '1',
                'display_order' => 4,
            ],
        ]);

        $this->actingAs($this->admin())
            ->put(route('admin.store.sns.update'), $payload)
            ->assertRedirect(route('admin.store.sns'));

        $ordered = SocialLink::query()->ordered()->get()->keyBy('service_key');

        $this->assertSame(1, $ordered['line']->display_order);
        $this->assertSame(2, $ordered['instagram']->display_order);
        $this->assertSame('https://lin.ee/abc', $ordered['line']->url);
        $this->assertTrue($ordered['line']->is_visible);
        $this->assertFalse($ordered['youtube']->is_visible);
        $this->assertSame('https://instagram.com/salon', SalonSetting::current()->fresh()->instagram_url);
    }

    public function test_public_hides_empty_url_even_when_visible(): void
    {
        SocialLink::ensureDefaults();
        SocialLink::query()->where('service_key', 'instagram')->update([
            'url' => null,
            'is_visible' => true,
        ]);
        SalonSetting::current()->update(['instagram_url' => null]);

        $html = $this->get(route('home'))->assertOk()->getContent();
        $this->assertStringNotContainsString('>Instagram</span>', $html);
        $this->assertStringNotContainsString('aria-label="Instagram"', $html);
    }

    public function test_public_hides_hidden_links_with_url(): void
    {
        SocialLink::ensureDefaults();
        SocialLink::query()->where('service_key', 'instagram')->update([
            'url' => 'https://instagram.com/hidden',
            'is_visible' => false,
            'display_order' => 1,
        ]);
        SocialLink::syncLegacyInstagramColumn('https://instagram.com/hidden');

        $html = $this->get(route('home'))->assertOk()->getContent();
        $this->assertStringNotContainsString('https://instagram.com/hidden', $html);
    }

    public function test_public_respects_display_order(): void
    {
        SocialLink::ensureDefaults();
        SocialLink::query()->where('service_key', 'x')->update([
            'url' => 'https://x.com/first',
            'is_visible' => true,
            'display_order' => 1,
        ]);
        SocialLink::query()->where('service_key', 'instagram')->update([
            'url' => 'https://instagram.com/second',
            'is_visible' => true,
            'display_order' => 2,
        ]);
        SocialLink::query()->where('service_key', 'line')->update([
            'url' => 'https://lin.ee/third',
            'is_visible' => true,
            'display_order' => 3,
        ]);

        $html = $this->get(route('access'))->assertOk()->getContent();
        $xPos = strpos($html, 'https://x.com/first');
        $igPos = strpos($html, 'https://instagram.com/second');
        $linePos = strpos($html, 'https://lin.ee/third');

        $this->assertNotFalse($xPos);
        $this->assertNotFalse($igPos);
        $this->assertNotFalse($linePos);
        $this->assertLessThan($igPos, $xPos);
        $this->assertLessThan($linePos, $igPos);
    }

    public function test_invalid_url_is_rejected(): void
    {
        $payload = $this->snsPayload([
            'instagram' => [
                'url' => 'not-a-valid-url',
                'is_visible' => '1',
                'display_order' => 1,
            ],
        ]);

        $this->actingAs($this->admin())
            ->from(route('admin.store.sns'))
            ->put(route('admin.store.sns.update'), $payload)
            ->assertRedirect(route('admin.store.sns'))
            ->assertSessionHasErrors('links.instagram.url');
    }

    public function test_ftp_url_is_rejected(): void
    {
        $payload = $this->snsPayload([
            'line' => [
                'url' => 'ftp://example.com/path',
                'is_visible' => '1',
                'display_order' => 1,
            ],
        ]);

        $this->actingAs($this->admin())
            ->from(route('admin.store.sns'))
            ->put(route('admin.store.sns.update'), $payload)
            ->assertRedirect(route('admin.store.sns'))
            ->assertSessionHasErrors('links.line.url');
    }

    public function test_editor_can_access_sns_screen(): void
    {
        $this->actingAs($this->editor())
            ->get(route('admin.store.sns'))
            ->assertOk()
            ->assertSee('SNS・公式アカウント');

        $payload = $this->snsPayload([
            'tiktok' => [
                'url' => 'https://www.tiktok.com/@salon',
                'is_visible' => '1',
                'display_order' => 1,
            ],
        ]);

        $this->actingAs($this->editor())
            ->put(route('admin.store.sns.update'), $payload)
            ->assertRedirect(route('admin.store.sns'));

        $this->assertSame(
            'https://www.tiktok.com/@salon',
            SocialLink::query()->where('service_key', 'tiktok')->value('url')
        );
    }

    public function test_duplicate_display_orders_are_renumbered_on_save(): void
    {
        $payload = $this->snsPayload([
            'instagram' => ['url' => 'https://instagram.com/a', 'is_visible' => '1', 'display_order' => 5],
            'line' => ['url' => 'https://lin.ee/b', 'is_visible' => '1', 'display_order' => 5],
            'youtube' => ['url' => null, 'is_visible' => '0', 'display_order' => 1],
        ]);

        $this->actingAs($this->admin())
            ->put(route('admin.store.sns.update'), $payload)
            ->assertRedirect(route('admin.store.sns'));

        $orders = SocialLink::query()->ordered()->pluck('display_order', 'service_key');
        $this->assertSame(range(1, 7), $orders->sort()->values()->all());
        $this->assertCount(7, $orders->unique());
        $this->assertSame(1, $orders['youtube']);
        $this->assertLessThan($orders['line'], $orders['instagram']);
        $this->assertLessThan($orders['facebook'], $orders['line']);
    }
}
