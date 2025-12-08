<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * This migration adds foreign key constraint for video_id
 * to video_watch_logs table after videos table is created.
 */
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('video_watch_logs') && Schema::hasTable('videos')) {
            Schema::table('video_watch_logs', function (Blueprint $table) {
                $table->foreign('video_id')
                    ->references('id')
                    ->on('videos')
                    ->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('video_watch_logs')) {
            Schema::table('video_watch_logs', function (Blueprint $table) {
                $table->dropForeign(['video_id']);
            });
        }
    }
};
