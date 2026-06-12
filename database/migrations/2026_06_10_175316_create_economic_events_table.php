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
        Schema::create('economic_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_name');
            $table->string('country', 10);
            $table->string('actual')->nullable();
            $table->string('forecast')->nullable();
            $table->string('previous')->nullable();
            $table->enum('importance', ['low', 'medium', 'high'])->default('low');
            $table->timestamp('release_time');
            $table->enum('status', ['pending', 'analyzed', 'failed'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('economic_events');
    }
};
