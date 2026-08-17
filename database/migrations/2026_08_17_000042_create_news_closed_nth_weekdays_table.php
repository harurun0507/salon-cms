<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_closed_nth_weekdays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_id')->constrained('news')->cascadeOnDelete();
            $table->unsignedTinyInteger('week_of_month');
            $table->unsignedTinyInteger('weekday');
            $table->timestamps();

            $table->unique(['news_id', 'week_of_month', 'weekday'], 'news_closed_nth_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_closed_nth_weekdays');
    }
};
