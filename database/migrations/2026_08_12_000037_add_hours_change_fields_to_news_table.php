<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->date('hours_change_date')->nullable()->after('category');
            $table->time('hours_start_time')->nullable()->after('hours_change_date');
            $table->time('hours_end_time')->nullable()->after('hours_start_time');
        });
    }

    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->dropColumn([
                'hours_change_date',
                'hours_start_time',
                'hours_end_time',
            ]);
        });
    }
};
