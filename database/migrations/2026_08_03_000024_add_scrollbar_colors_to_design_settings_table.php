<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('design_settings', function (Blueprint $table) {
            $table->string('scrollbar_thumb_color', 7)->default('#c8c0b2')->after('text_color');
            $table->string('scrollbar_track_color', 7)->default('#f1ece3')->after('scrollbar_thumb_color');
            $table->string('scrollbar_thumb_hover_color', 7)->default('#afa692')->after('scrollbar_track_color');
        });
    }

    public function down(): void
    {
        Schema::table('design_settings', function (Blueprint $table) {
            $table->dropColumn([
                'scrollbar_thumb_color',
                'scrollbar_track_color',
                'scrollbar_thumb_hover_color',
            ]);
        });
    }
};
