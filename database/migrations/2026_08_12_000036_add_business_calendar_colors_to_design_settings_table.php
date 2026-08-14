<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('design_settings', function (Blueprint $table) {
            $table->string('calendar_holiday_color', 7)->default('#d1d4c8')->after('modal_overlay_color');
            $table->string('calendar_temporary_color', 7)->default('#ded6cd')->after('calendar_holiday_color');
            $table->string('calendar_hours_color', 7)->default('#d1c4b4')->after('calendar_temporary_color');
        });
    }

    public function down(): void
    {
        Schema::table('design_settings', function (Blueprint $table) {
            $table->dropColumn([
                'calendar_holiday_color',
                'calendar_temporary_color',
                'calendar_hours_color',
            ]);
        });
    }
};
