<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salon_settings', function (Blueprint $table) {
            $table->string('shop_name_display_type', 20)->default('text')->after('shop_name');
            $table->string('logo_image')->nullable()->after('shop_name_display_type');
            $table->string('logo_alt_text')->nullable()->after('logo_image');
        });
    }

    public function down(): void
    {
        Schema::table('salon_settings', function (Blueprint $table) {
            $table->dropColumn(['shop_name_display_type', 'logo_image', 'logo_alt_text']);
        });
    }
};
