<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingestion_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_source_id')->nullable()->constrained()->onDelete('set null');
            $table->string('source_type')->index(); // economic_calendar, financial, geopolitical, market
            $table->string('status')->index(); // running, completed, failed, partial
            $table->unsignedInteger('fetched_count')->default(0);
            $table->unsignedInteger('duplicates_skipped')->default(0);
            $table->unsignedInteger('stored_count')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable(); // extra context: source_url, duration_ms, etc.
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingestion_logs');
    }
};
