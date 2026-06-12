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
        // Extend existing market_assets table
        // ──────────────────────────────────────────────
        Schema::table('market_assets', function (Blueprint $table) {
            $table->string('sector', 50)->nullable()->after('asset_class'); // indices, forex, commodity, crypto
            $table->string('data_source', 100)->nullable()->after('exchange'); // API source identifier
            $table->string('data_symbol', 50)->nullable()->after('data_source'); // symbol used by API
            $table->integer('display_order')->default(0)->after('is_active');
            $table->json('metadata')->nullable()->after('display_order'); // extra config (decimals, pip_size, etc.)
        });

        // ──────────────────────────────────────────────
        // Market Asset Price Time-Series
        // ──────────────────────────────────────────────
        Schema::create('market_asset_prices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('market_asset_id');
            $table->decimal('price', 20, 6);
            $table->decimal('change', 20, 6)->nullable(); // absolute change from previous close
            $table->decimal('change_percent', 10, 4)->nullable(); // percentage change
            $table->decimal('open', 20, 6)->nullable();
            $table->decimal('high', 20, 6)->nullable();
            $table->decimal('low', 20, 6)->nullable();
            $table->decimal('close', 20, 6)->nullable();
            $table->decimal('volume', 20, 2)->nullable();
            $table->string('timeframe', 10)->default('1m'); // 1m, 5m, 15m, 1h, 1d
            $table->timestamp('recorded_at'); // the timestamp of this price point
            $table->timestamps();

            $table->foreign('market_asset_id')->references('id')->on('market_assets')->cascadeOnDelete();
            $table->index('recorded_at');
            $table->index('timeframe');
            $table->index(['market_asset_id', 'recorded_at']);
            $table->index(['market_asset_id', 'timeframe', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_asset_prices');

        Schema::table('market_assets', function (Blueprint $table) {
            $table->dropColumn(['sector', 'data_source', 'data_symbol', 'display_order', 'metadata']);
        });
    }
};
