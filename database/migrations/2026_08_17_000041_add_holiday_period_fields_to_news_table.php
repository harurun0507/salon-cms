<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->string('holiday_period_type', 20)->nullable()->after('hours_end_time');
            $table->date('holiday_period_from')->nullable()->after('holiday_period_type');
            $table->date('holiday_period_to')->nullable()->after('holiday_period_from');
        });
    }

    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->dropColumn([
                'holiday_period_type',
                'holiday_period_from',
                'holiday_period_to',
            ]);
        });
    }
};
