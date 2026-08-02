<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salon_settings', function (Blueprint $table) {
            $table->string('favicon_path')->nullable()->after('noindex');
            $table->string('ga_measurement_id', 32)->nullable()->after('favicon_path');
        });
    }

    public function down(): void
    {
        Schema::table('salon_settings', function (Blueprint $table) {
            $table->dropColumn(['favicon_path', 'ga_measurement_id']);
        });
    }
};
