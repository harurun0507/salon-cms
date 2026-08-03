<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salon_settings', function (Blueprint $table) {
            $table->text('access_directions')->nullable()->after('address');
            $table->text('payment_methods')->nullable()->after('phone');
            $table->string('cut_price')->nullable()->after('payment_methods');
            $table->string('seat_count')->nullable()->after('cut_price');
            $table->string('staff_count')->nullable()->after('seat_count');
            $table->text('parking')->nullable()->after('staff_count');
            $table->text('commitment_conditions')->nullable()->after('parking');
            $table->text('notes')->nullable()->after('commitment_conditions');
            $table->text('other_info')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('salon_settings', function (Blueprint $table) {
            $table->dropColumn([
                'access_directions',
                'payment_methods',
                'cut_price',
                'seat_count',
                'staff_count',
                'parking',
                'commitment_conditions',
                'notes',
                'other_info',
            ]);
        });
    }
};
