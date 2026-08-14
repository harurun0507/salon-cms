<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('design_settings', function (Blueprint $table) {
            $table->string('scroll_display_type', 32)
                ->default('colored_scrollbar')
                ->after('scrollbar_thumb_hover_color');
        });
    }

    public function down(): void
    {
        Schema::table('design_settings', function (Blueprint $table) {
            $table->dropColumn('scroll_display_type');
        });
    }
};
