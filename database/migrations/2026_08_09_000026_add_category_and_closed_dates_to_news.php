<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->string('category', 64)->default('other')->after('body');
        });

        Schema::create('news_closed_dates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_id')->constrained('news')->cascadeOnDelete();
            $table->date('closed_date');
            $table->timestamps();

            $table->unique(['news_id', 'closed_date']);
            $table->index('closed_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_closed_dates');

        Schema::table('news', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
