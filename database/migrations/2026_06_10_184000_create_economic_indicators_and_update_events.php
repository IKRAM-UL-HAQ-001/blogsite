<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Create economic_indicators registry table
        Schema::create('economic_indicators', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // cpi, core_cpi, nfp, etc.
            $table->string('name'); // Consumer Price Index, etc.
            $table->string('category'); // inflation, employment, growth, monetary_policy, spending
            $table->string('unit')->default('%'); // %, K, M
            $table->string('frequency'); // monthly, quarterly, weekly
            $table->text('description')->nullable();
            $table->string('default_country', 10)->default('USD');
            $table->string('default_importance', 10)->default('high');
            $table->json('keywords')->nullable(); // keywords used to classify events
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Add indicator_type, surprise, surprise_direction to economic_events
        Schema::table('economic_events', function (Blueprint $table) {
            $table->string('indicator_type')->nullable()->after('event_name')->index();
            $table->decimal('surprise', 10, 4)->nullable()->after('previous');
            $table->string('surprise_direction', 10)->nullable()->after('surprise'); // beat, miss, inline
        });

        // For MySQL: add indicator_type enum values via ALTER
        if (DB::getDriverName() !== 'sqlite') {
            // Nothing extra needed - we use string type for flexibility
        }
    }

    public function down(): void
    {
        Schema::table('economic_events', function (Blueprint $table) {
            $table->dropColumn(['indicator_type', 'surprise', 'surprise_direction']);
        });

        Schema::dropIfExists('economic_indicators');
    }
};
