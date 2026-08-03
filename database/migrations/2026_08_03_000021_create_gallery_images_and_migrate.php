<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gallery_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gallery_id')->constrained('galleries')->cascadeOnDelete();
            $table->string('image_path');
            $table->string('alt_text')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->index(['gallery_id', 'display_order']);
        });

        Schema::table('galleries', function (Blueprint $table) {
            $table->foreignId('staff_id')
                ->nullable()
                ->after('caption')
                ->constrained('staff_members')
                ->nullOnDelete();
        });

        $galleries = DB::table('galleries')
            ->select(['id', 'image_path', 'caption', 'created_at', 'updated_at'])
            ->whereNotNull('image_path')
            ->where('image_path', '!=', '')
            ->get();

        $now = now();
        foreach ($galleries as $gallery) {
            DB::table('gallery_images')->insert([
                'gallery_id' => $gallery->id,
                'image_path' => $gallery->image_path,
                'alt_text' => $gallery->caption,
                'display_order' => 1,
                'created_at' => $gallery->created_at ?? $now,
                'updated_at' => $gallery->updated_at ?? $now,
            ]);
        }

        Schema::table('galleries', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }

    public function down(): void
    {
        Schema::table('galleries', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('id');
        });

        $firstImages = DB::table('gallery_images')
            ->select(['gallery_id', 'image_path'])
            ->orderBy('display_order')
            ->orderBy('id')
            ->get()
            ->unique('gallery_id');

        foreach ($firstImages as $image) {
            DB::table('galleries')
                ->where('id', $image->gallery_id)
                ->update(['image_path' => $image->image_path]);
        }

        Schema::table('galleries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('staff_id');
        });

        Schema::dropIfExists('gallery_images');
    }
};
