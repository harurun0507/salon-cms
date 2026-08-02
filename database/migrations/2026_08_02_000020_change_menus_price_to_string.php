<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->string('price_display', 100)->nullable()->after('name');
        });

        foreach (DB::table('menus')->orderBy('id')->get() as $menu) {
            $raw = $menu->price;
            $display = null;

            if ($raw !== null && $raw !== '') {
                $display = is_numeric($raw)
                    ? '¥'.number_format((int) $raw)
                    : (string) $raw;
            }

            DB::table('menus')->where('id', $menu->id)->update([
                'price_display' => $display,
            ]);
        }

        Schema::table('menus', function (Blueprint $table) {
            $table->dropColumn('price');
        });

        Schema::table('menus', function (Blueprint $table) {
            $table->renameColumn('price_display', 'price');
        });
    }

    public function down(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->unsignedInteger('price_int')->nullable()->after('name');
        });

        foreach (DB::table('menus')->orderBy('id')->get() as $menu) {
            $digits = preg_replace('/[^\d]/', '', (string) ($menu->price ?? '')) ?: null;

            DB::table('menus')->where('id', $menu->id)->update([
                'price_int' => $digits !== null ? (int) $digits : null,
            ]);
        }

        Schema::table('menus', function (Blueprint $table) {
            $table->dropColumn('price');
        });

        Schema::table('menus', function (Blueprint $table) {
            $table->renameColumn('price_int', 'price');
        });
    }
};
