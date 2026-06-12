<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_traffic_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('source')->default('direct');
            $table->string('medium')->default('direct');
            $table->string('host')->nullable();
            $table->string('referrer')->nullable();
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->date('date');
            $table->timestamps();

            $table->unique(['article_id', 'source', 'medium', 'host', 'date'], 'article_traffic_source_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_traffic_sources');
    }
};
