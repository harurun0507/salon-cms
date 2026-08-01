<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salon_setting_id')->constrained('salon_settings')->cascadeOnDelete();
            $table->string('image_path');
            $table->string('alt_text')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index(['salon_setting_id', 'sort_order', 'id']);
            $table->unique(['salon_setting_id', 'image_path']);
        });

        $this->migrateLegacyHeroImages();
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_images');
    }

    private function migrateLegacyHeroImages(): void
    {
        if (! Schema::hasColumn('salon_settings', 'hero_image')) {
            return;
        }

        $settings = DB::table('salon_settings')
            ->whereNotNull('hero_image')
            ->where('hero_image', '!=', '')
            ->get(['id', 'hero_image']);

        $now = now();

        foreach ($settings as $setting) {
            $exists = DB::table('hero_images')
                ->where('salon_setting_id', $setting->id)
                ->where('image_path', $setting->hero_image)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('hero_images')->insert([
                'salon_setting_id' => $setting->id,
                'image_path' => $setting->hero_image,
                'alt_text' => null,
                'sort_order' => 1,
                'is_published' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
};
