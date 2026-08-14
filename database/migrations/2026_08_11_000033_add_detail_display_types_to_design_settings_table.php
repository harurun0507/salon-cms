<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('design_settings', function (Blueprint $table) {
            $table->string('news_detail_display', 16)->default('page')->after('scroll_display_type');
            $table->string('blog_detail_display', 16)->default('page')->after('news_detail_display');
            $table->string('gallery_detail_display', 16)->default('page')->after('blog_detail_display');
        });
    }

    public function down(): void
    {
        Schema::table('design_settings', function (Blueprint $table) {
            $table->dropColumn([
                'news_detail_display',
                'blog_detail_display',
                'gallery_detail_display',
            ]);
        });
    }
};
