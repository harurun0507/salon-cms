<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salon_settings', function (Blueprint $table) {
            $table->text('hero_title')->nullable()->after('shop_name');
            $table->string('concept_title')->nullable()->after('hero_title');
        });
    }

    public function down(): void
    {
        Schema::table('salon_settings', function (Blueprint $table) {
            $table->dropColumn(['hero_title', 'concept_title']);
        });
    }
};
