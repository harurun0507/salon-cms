<?php

use App\Models\MenuCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_categories', function (Blueprint $table) {
            $table->boolean('allow_multiple_selection')->default(false)->after('sort_order');
        });

        MenuCategory::query()
            ->where('name', MenuCategory::NAME_COMBINATION)
            ->update(['allow_multiple_selection' => true]);
    }

    public function down(): void
    {
        Schema::table('menu_categories', function (Blueprint $table) {
            $table->dropColumn('allow_multiple_selection');
        });
    }
};
