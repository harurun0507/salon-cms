<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->unsignedInteger('display_order')->default(0)->after('is_published');
        });

        $rows = DB::table('news')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get(['id']);

        $order = 1;
        foreach ($rows as $row) {
            DB::table('news')->where('id', $row->id)->update(['display_order' => $order]);
            $order++;
        }
    }

    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->dropColumn('display_order');
        });
    }
};
