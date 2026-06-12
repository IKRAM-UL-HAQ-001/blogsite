<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pipeline_runs', function (Blueprint $table) {
            $table->id();
            $table->string('pipeline');
            $table->uuid('batch_uuid')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->enum('status', ['running', 'completed', 'partial', 'failed'])->default('running');
            $table->unsignedInteger('items_received')->default(0);
            $table->unsignedInteger('items_processed')->default(0);
            $table->unsignedInteger('items_failed')->default(0);
            $table->json('metadata_json')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['pipeline', 'started_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pipeline_runs');
    }
};
