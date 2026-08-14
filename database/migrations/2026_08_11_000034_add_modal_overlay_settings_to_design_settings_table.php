<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('design_settings', function (Blueprint $table) {
            $table->string('modal_overlay_style', 16)->default('blur')->after('gallery_detail_display');
            $table->string('modal_overlay_color', 7)->default('#1e1a16')->after('modal_overlay_style');
        });
    }

    public function down(): void
    {
        Schema::table('design_settings', function (Blueprint $table) {
            $table->dropColumn([
                'modal_overlay_style',
                'modal_overlay_color',
            ]);
        });
    }
};
