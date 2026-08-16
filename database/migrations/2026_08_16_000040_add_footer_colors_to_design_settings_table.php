<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('design_settings', function (Blueprint $table) {
            $table->string('footer_background_color', 7)->default('#322824')->after('calendar_hours_color');
            $table->string('footer_text_color', 7)->default('#f3eee6')->after('footer_background_color');
            $table->string('footer_link_color', 7)->default('#e8e0d4')->after('footer_text_color');
            $table->string('footer_link_hover_color', 7)->default('#ffffff')->after('footer_link_color');
        });
    }

    public function down(): void
    {
        Schema::table('design_settings', function (Blueprint $table) {
            $table->dropColumn([
                'footer_background_color',
                'footer_text_color',
                'footer_link_color',
                'footer_link_hover_color',
            ]);
        });
    }
};
