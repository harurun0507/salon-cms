<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blogs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('body');
            $table->string('body_format', 16)->default('plain');
            $table->string('eye_catch_image_path')->nullable();
            $table->timestamp('published_at');
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->index(['is_published', 'published_at', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blogs');
    }
};
