<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('market_impacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('raw_article_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('economic_event_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('sentiment', ['bullish', 'bearish', 'neutral']);
            $table->integer('score');
            $table->enum('impact_level', ['low', 'medium', 'high']);
            $table->json('affected_assets');
            $table->text('market_summary');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('market_impacts');
    }
};
