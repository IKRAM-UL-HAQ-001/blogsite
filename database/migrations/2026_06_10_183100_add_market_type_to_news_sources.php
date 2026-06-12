<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE news_sources MODIFY type ENUM('economic_calendar', 'financial', 'geopolitical', 'commodity', 'market') NOT NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE news_sources MODIFY type ENUM('economic_calendar', 'financial', 'geopolitical', 'commodity') NOT NULL");
    }
};
