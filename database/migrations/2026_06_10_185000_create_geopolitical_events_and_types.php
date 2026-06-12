<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ──────────────────────────────────────────────
        // Geopolitical Event Types Registry
        // ──────────────────────────────────────────────
        Schema::create('geopolitical_event_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('category'); // conflict, trade, political, financial, energy
            $table->text('description')->nullable();
            $table->string('default_severity', 20)->default('medium'); // low, medium, high, critical
            $table->json('keywords')->nullable(); // keywords for auto-classification
            $table->json('affected_markets')->nullable(); // default affected market sectors
            $table->json('risk_multipliers')->nullable(); // risk score multipliers per severity
            $table->timestamps();
        });

        // ──────────────────────────────────────────────
        // Geopolitical Events
        // ──────────────────────────────────────────────
        Schema::create('geopolitical_events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('event_type', 50)->nullable(); // FK to geopolitical_event_types.code
            $table->string('severity', 20)->default('medium'); // low, medium, high, critical
            $table->string('status', 30)->default('pending'); // pending, classified, analyzed, resolved, archived

            // Geographic scope
            $table->json('countries')->nullable(); // ISO country codes involved
            $table->string('region', 50)->nullable(); // middle_east, europe, asia_pacific, americas, africa, global
            $table->string('primary_country', 5)->nullable();

            // Temporal tracking
            $table->timestamp('occurred_at')->nullable();
            $table->timestamp('escalated_at')->nullable();
            $table->timestamp('resolved_at')->nullable();

            // Source tracking
            $table->unsignedBigInteger('raw_article_id')->nullable();
            $table->unsignedBigInteger('news_source_id')->nullable();
            $table->string('source_url')->nullable();

            // AI Analysis results
            $table->string('ai_sentiment', 20)->nullable(); // bullish, bearish, neutral
            $table->decimal('ai_confidence_score', 5, 2)->nullable(); // 0-100
            $table->string('ai_impact_level', 20)->nullable(); // low, medium, high
            $table->json('ai_affected_assets')->nullable();
            $table->text('ai_market_summary')->nullable();
            $table->json('ai_risk_factors')->nullable(); // extracted risk factors
            $table->text('ai_geopolitical_analysis')->nullable(); // full AI narrative
            $table->json('ai_timeline_projection')->nullable(); // short/medium/long term projections
            $table->json('ai_historical_parallels')->nullable(); // similar past events

            // Escalation and linkage
            $table->unsignedBigInteger('parent_event_id')->nullable(); // for escalation chains
            $table->json('related_event_ids')->nullable(); // linked economic or geopolitical events
            $table->integer('escalation_level')->default(0); // 0=new, 1=monitoring, 2=escalating, 3=critical

            $table->timestamps();

            // Indexes
            $table->index('event_type');
            $table->index('severity');
            $table->index('status');
            $table->index('region');
            $table->index('occurred_at');
            $table->index('escalation_level');
            $table->index('raw_article_id');
            $table->foreign('raw_article_id')->references('id')->on('raw_articles')->nullOnDelete();
            $table->foreign('news_source_id')->references('id')->on('news_sources')->nullOnDelete();
            $table->foreign('parent_event_id')->references('id')->on('geopolitical_events')->nullOnDelete();
        });

        // ──────────────────────────────────────────────
        // Geopolitical Event-Country Pivot (many-to-many)
        // ──────────────────────────────────────────────
        Schema::create('geopolitical_event_country', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('geopolitical_event_id');
            $table->string('country_code', 5);
            $table->string('involvement', 30)->default('affected'); // initiator, target, affected, mediator
            $table->timestamps();

            $table->foreign('geopolitical_event_id')->references('id')->on('geopolitical_events')->cascadeOnDelete();
            $table->index('country_code');
        });

        // ──────────────────────────────────────────────
        // Market Impact linkage (add geopolitical_event_id to market_impacts)
        // ──────────────────────────────────────────────
        Schema::table('market_impacts', function (Blueprint $table) {
            $table->unsignedBigInteger('geopolitical_event_id')->nullable()->after('economic_event_id');
            $table->foreign('geopolitical_event_id')->references('id')->on('geopolitical_events')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('market_impacts', function (Blueprint $table) {
            $table->dropForeign(['geopolitical_event_id']);
            $table->dropColumn('geopolitical_event_id');
        });

        Schema::dropIfExists('geopolitical_event_country');
        Schema::dropIfExists('geopolitical_events');
        Schema::dropIfExists('geopolitical_event_types');
    }
};
