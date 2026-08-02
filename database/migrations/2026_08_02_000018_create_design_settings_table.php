<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('design_settings', function (Blueprint $table) {
            $table->id();
            $table->string('primary_color', 7)->default('#5f6f52');
            $table->string('secondary_color', 7)->default('#7c8a6a');
            $table->string('background_color', 7)->default('#faf7f1');
            $table->string('text_color', 7)->default('#3a332e');
            $table->string('heading_font', 16)->default('serif');
            $table->string('body_font', 16)->default('sans');
            $table->string('button_radius', 16)->default('large');
            $table->string('card_radius', 16)->default('medium');
            $table->string('layout_density', 16)->default('standard');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('design_settings');
    }
};
