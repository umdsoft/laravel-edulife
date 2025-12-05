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
        Schema::create('olympiad_device_locks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('olympiad_id')->constrained('olympiads')->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('device_id')->constrained('user_devices')->onDelete('cascade');
            $table->foreignUuid('attempt_id')->nullable()->constrained('olympiad_attempts')->onDelete('set null');
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->ipAddress('locked_ip')->nullable();
            $table->string('lock_reason', 50)->default('exam_start');
            // 'exam_start', 'device_verification', 'manual_lock'
            $table->string('status', 20)->default('active');
            // 'active', 'released', 'violated'
            $table->timestamps();
            
            // Unique constraint - one lock per olympiad/user
            $table->unique(['olympiad_id', 'user_id']);
            
            // Indexes
            $table->index('olympiad_id');
            $table->index('user_id');
            $table->index('device_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('olympiad_device_locks');
    }
};
