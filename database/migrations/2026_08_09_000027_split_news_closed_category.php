<?php

use App\Models\News;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('news') || ! Schema::hasColumn('news', 'category')) {
            return;
        }

        // Legacy combined type "定休日・臨時休業" → "定休日" (closed dates rows are kept).
        DB::table('news')
            ->where('category', News::CATEGORY_CLOSED)
            ->update(['category' => News::CATEGORY_HOLIDAY]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('news') || ! Schema::hasColumn('news', 'category')) {
            return;
        }

        DB::table('news')
            ->where('category', News::CATEGORY_HOLIDAY)
            ->update(['category' => News::CATEGORY_CLOSED]);
    }
};
