<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('article_analytics', function (Blueprint $table) {
            if (!Schema::hasColumn('article_analytics', 'impressions')) {
                $table->unsignedInteger('impressions')->default(0)->after('views');
            }
            if (!Schema::hasColumn('article_analytics', 'clicks')) {
                $table->unsignedInteger('clicks')->default(0)->after('impressions');
            }
        });
    }

    public function down(): void
    {
        Schema::table('article_analytics', function (Blueprint $table) {
            $table->dropColumn(['impressions', 'clicks']);
        });
    }
};
