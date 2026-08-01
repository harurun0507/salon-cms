<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salon_settings', function (Blueprint $table) {
            $table->id();
            $table->string('shop_name')->default('Salon Name');
            $table->text('concept')->nullable();
            $table->string('address')->nullable();
            $table->text('business_hours')->nullable();
            $table->string('closed_days')->nullable();
            $table->string('phone')->nullable();
            $table->string('google_map_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('hot_pepper_url')->nullable();
            $table->string('hero_image')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salon_settings');
    }
};
