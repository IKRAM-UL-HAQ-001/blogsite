<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('raw_articles', function (Blueprint $table) {
            $table->string('external_id')->nullable()->after('news_source_id');
            // content_hash is nullable so existing rows don't violate the unique constraint
            $table->string('content_hash', 64)->nullable()->unique()->after('url');
            $table->text('summary')->nullable()->after('body');
            $table->string('author')->nullable()->after('summary');
            $table->string('language', 10)->default('en')->after('author');
            $table->timestamp('fetched_at')->nullable()->after('published_at');
            $table->json('raw_payload_json')->nullable()->after('status');

            // Useful indexes for pipeline queries
            $table->index('published_at');
            $table->index('status');
        });

        // Widen status enum to include 'processing' (SQLite ignores this; MySQL requires ALTER)
        if (\Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') {
            \Illuminate\Support\Facades\DB::statement(
                "ALTER TABLE raw_articles MODIFY status ENUM('pending','processing','analyzed','failed') NOT NULL DEFAULT 'pending'"
            );
        }
    }

    public function down(): void
    {
        Schema::table('raw_articles', function (Blueprint $table) {
            $table->dropIndex(['published_at']);
            $table->dropIndex(['status']);
            $table->dropColumn([
                'external_id',
                'content_hash',
                'summary',
                'author',
                'language',
                'fetched_at',
                'raw_payload_json',
            ]);
        });
    }
};
