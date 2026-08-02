<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_links', function (Blueprint $table) {
            $table->id();
            $table->string('service_key')->unique();
            $table->string('url', 500)->nullable();
            $table->boolean('is_visible')->default(false);
            $table->unsignedInteger('display_order')->default(1);
            $table->timestamps();
        });

        $now = now();
        $services = config('social_links.services', []);
        $rows = [];

        foreach ($services as $key => $meta) {
            $rows[] = [
                'service_key' => $key,
                'url' => null,
                'is_visible' => false,
                'display_order' => (int) ($meta['default_order'] ?? 1),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows !== []) {
            DB::table('social_links')->insert($rows);
        }

        $legacyUrl = DB::table('salon_settings')->value('instagram_url');
        if (is_string($legacyUrl) && trim($legacyUrl) !== '') {
            DB::table('social_links')
                ->where('service_key', 'instagram')
                ->update([
                    'url' => trim($legacyUrl),
                    'is_visible' => true,
                    'updated_at' => $now,
                ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('social_links');
    }
};
