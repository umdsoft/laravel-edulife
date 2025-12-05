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
        Schema::create('user_devices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->string('device_fingerprint', 64)->unique();
            $table->string('browser_fingerprint', 64)->nullable();
            
            // Device info
            $table->string('device_type', 20)->nullable();
            // 'desktop', 'mobile', 'tablet'
            $table->string('os_name', 50)->nullable();
            $table->string('os_version', 50)->nullable();
            $table->string('browser_name', 50)->nullable();
            $table->string('browser_version', 50)->nullable();
            
            // Fingerprint components (15+)
            $table->string('screen_resolution', 50)->nullable();
            $table->string('color_depth', 10)->nullable();
            $table->string('timezone', 100)->nullable();
            $table->string('timezone_offset', 10)->nullable();
            $table->string('language', 20)->nullable();
            $table->string('platform', 50)->nullable();
            $table->string('hardware_concurrency', 10)->nullable();
            $table->string('device_memory', 20)->nullable();
            $table->string('canvas_fingerprint', 64)->nullable();
            $table->string('webgl_fingerprint', 64)->nullable();
            $table->string('audio_fingerprint', 64)->nullable();
            $table->string('fonts_hash', 64)->nullable();
            $table->string('plugins_hash', 64)->nullable();
            $table->boolean('touch_support')->default(false);
            $table->boolean('cookies_enabled')->default(true);
            $table->boolean('local_storage_enabled')->default(true);
            $table->boolean('session_storage_enabled')->default(true);
            $table->boolean('indexed_db_enabled')->default(true);
            
            // Trust & Security
            $table->decimal('trust_score', 5, 2)->default(100);
            $table->boolean('is_trusted')->default(false);
            $table->boolean('is_blocked')->default(false);
            $table->string('block_reason', 255)->nullable();
            $table->timestamp('blocked_at')->nullable();
            
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('user_id');
            $table->index('device_fingerprint');
            $table->index('trust_score');
            $table->index('is_blocked');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};
