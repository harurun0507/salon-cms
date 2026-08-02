<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salon_settings', function (Blueprint $table) {
            $table->string('site_title')->nullable()->after('hero_image');
            $table->text('meta_description')->nullable()->after('site_title');
            $table->string('meta_keywords')->nullable()->after('meta_description');
            $table->string('og_title')->nullable()->after('meta_keywords');
            $table->text('og_description')->nullable()->after('og_title');
            $table->string('og_image')->nullable()->after('og_description');
            $table->string('twitter_card', 32)->default('summary_large_image')->after('og_image');
            $table->boolean('noindex')->default(false)->after('twitter_card');
        });
    }

    public function down(): void
    {
        Schema::table('salon_settings', function (Blueprint $table) {
            $table->dropColumn([
                'site_title',
                'meta_description',
                'meta_keywords',
                'og_title',
                'og_description',
                'og_image',
                'twitter_card',
                'noindex',
            ]);
        });
    }
};
