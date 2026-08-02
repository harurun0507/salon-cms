<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image_path');
            $table->string('alt_text')->nullable();
            $table->string('link_url')->nullable();
            $table->string('link_target')->default('_self');
            $table->string('display_location')->default('top');
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamp('published_from')->nullable();
            $table->timestamp('published_until')->nullable();
            $table->timestamps();

            $table->index(['display_location', 'is_published', 'display_order']);
        });

        if (Schema::hasTable('top_page_sections')) {
            $exists = DB::table('top_page_sections')
                ->where('section_key', 'banner')
                ->exists();

            if (! $exists) {
                $now = now();
                DB::table('top_page_sections')->insert([
                    'section_key' => 'banner',
                    'is_visible' => true,
                    'display_order' => 0,
                    'display_count' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('top_page_sections')) {
            DB::table('top_page_sections')->where('section_key', 'banner')->delete();
        }

        Schema::dropIfExists('banners');
    }
};
