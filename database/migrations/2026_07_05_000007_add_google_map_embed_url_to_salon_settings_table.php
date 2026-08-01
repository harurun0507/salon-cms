<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salon_settings', function (Blueprint $table) {
            $table->text('google_map_embed_url')->nullable()->after('google_map_url');
        });
    }

    public function down(): void
    {
        Schema::table('salon_settings', function (Blueprint $table) {
            $table->dropColumn('google_map_embed_url');
        });
    }
};
