<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_sources', function (Blueprint $table) {
            $table->string('provider_class')->nullable()->after('type');
            $table->unsignedSmallInteger('poll_interval_minutes')->default(30)->after('provider_class');
            $table->timestamp('last_fetched_at')->nullable()->after('poll_interval_minutes');
            $table->decimal('reliability_score', 5, 2)->nullable()->after('last_fetched_at');
            $table->json('configuration_json')->nullable()->after('reliability_score');
        });
    }

    public function down(): void
    {
        Schema::table('news_sources', function (Blueprint $table) {
            $table->dropColumn([
                'provider_class',
                'poll_interval_minutes',
                'last_fetched_at',
                'reliability_score',
                'configuration_json',
            ]);
        });
    }
};
