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
        Schema::create('raw_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_source_id')->nullable()->constrained()->onDelete('set null');
            $table->string('title');
            $table->string('url')->unique();
            $table->text('body');
            $table->timestamp('published_at');
            $table->enum('status', ['pending', 'analyzed', 'failed'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raw_articles');
    }
};
