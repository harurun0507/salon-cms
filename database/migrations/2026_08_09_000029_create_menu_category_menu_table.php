<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_category_menu', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('menus')->cascadeOnDelete();
            $table->foreignId('menu_category_id')->constrained('menu_categories')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['menu_id', 'menu_category_id']);
            $table->index(['menu_category_id', 'sort_order']);
        });

        $now = now();
        $rows = DB::table('menus')
            ->whereNotNull('menu_category_id')
            ->get(['id', 'menu_category_id', 'sort_order']);

        foreach ($rows as $row) {
            DB::table('menu_category_menu')->insert([
                'menu_id' => $row->id,
                'menu_category_id' => $row->menu_category_id,
                'sort_order' => (int) $row->sort_order,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Schema::table('menus', function (Blueprint $table) {
            $table->dropConstrainedForeignId('menu_category_id');
        });
    }

    public function down(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->foreignId('menu_category_id')
                ->nullable()
                ->after('id')
                ->constrained('menu_categories')
                ->cascadeOnDelete();
        });

        $pivots = DB::table('menu_category_menu')
            ->orderBy('menu_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $assigned = [];
        foreach ($pivots as $pivot) {
            if (isset($assigned[$pivot->menu_id])) {
                continue;
            }

            DB::table('menus')->where('id', $pivot->menu_id)->update([
                'menu_category_id' => $pivot->menu_category_id,
                'sort_order' => $pivot->sort_order,
            ]);
            $assigned[$pivot->menu_id] = true;
        }

        Schema::dropIfExists('menu_category_menu');
    }
};
