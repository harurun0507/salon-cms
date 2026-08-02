<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('top_page_sections', function (Blueprint $table) {
            $table->id();
            $table->string('section_key')->unique();
            $table->boolean('is_visible')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->unsignedInteger('display_count')->nullable();
            $table->timestamps();
        });

        $now = now();

        DB::table('top_page_sections')->insert([
            [
                'section_key' => 'news',
                'is_visible' => true,
                'display_order' => 1,
                'display_count' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'section_key' => 'menu',
                'is_visible' => true,
                'display_order' => 2,
                'display_count' => 6,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'section_key' => 'gallery',
                'is_visible' => true,
                'display_order' => 3,
                'display_count' => 6,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'section_key' => 'staff',
                'is_visible' => true,
                'display_order' => 4,
                'display_count' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'section_key' => 'access',
                'is_visible' => true,
                'display_order' => 5,
                'display_count' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('top_page_sections');
    }
};
