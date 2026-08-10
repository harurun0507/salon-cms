<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $menu = DB::table('top_page_sections')->where('section_key', 'menu')->first();
        $gallery = DB::table('top_page_sections')->where('section_key', 'gallery')->first();

        if (! $menu || ! $gallery) {
            return;
        }

        // Put Gallery above Menu on the top page (swap their display orders).
        DB::table('top_page_sections')->where('id', $menu->id)->update([
            'display_order' => $gallery->display_order,
        ]);
        DB::table('top_page_sections')->where('id', $gallery->id)->update([
            'display_order' => $menu->display_order,
        ]);
    }

    public function down(): void
    {
        $menu = DB::table('top_page_sections')->where('section_key', 'menu')->first();
        $gallery = DB::table('top_page_sections')->where('section_key', 'gallery')->first();

        if (! $menu || ! $gallery) {
            return;
        }

        DB::table('top_page_sections')->where('id', $menu->id)->update([
            'display_order' => $gallery->display_order,
        ]);
        DB::table('top_page_sections')->where('id', $gallery->id)->update([
            'display_order' => $menu->display_order,
        ]);
    }
};
