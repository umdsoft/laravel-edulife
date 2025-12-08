<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('video_watch_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('lesson_id')->constrained()->cascadeOnDelete();
            // video_id foreign key added later via separate migration (videos table created after this)
            $table->uuid('video_id')->nullable();

            $table->unsignedInteger('start_position')->default(0);
            $table->unsignedInteger('end_position')->default(0);
            $table->unsignedInteger('duration')->default(0); // seconds watched
            $table->string('quality')->nullable(); // 360p, 480p, 720p, 1080p
            $table->float('playback_rate')->default(1.0);
            $table->string('device_type')->nullable(); // mobile, tablet, desktop

            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();

            $table->index(['user_id', 'lesson_id']);
            $table->index(['video_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_watch_logs');
    }
};
