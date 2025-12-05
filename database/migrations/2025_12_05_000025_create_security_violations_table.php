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
        Schema::create('security_violations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('olympiad_id')->constrained('olympiads')->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('attempt_id')->nullable()->constrained('olympiad_attempts')->onDelete('set null');
            $table->foreignUuid('device_id')->nullable()->constrained('user_devices')->onDelete('set null');
            $table->string('violation_type', 50);
            // 'multiple_device', 'tab_switch', 'fullscreen_exit', 'devtools_open', 
            // 'copy_paste', 'right_click', 'screenshot', 'heartbeat_miss', 
            // 'suspicious_answer_pattern', 'vpn_detected', 'ip_mismatch'
            $table->integer('occurrence_count')->default(1);
            $table->string('severity', 20)->default('warning');
            // 'warning', 'medium', 'high', 'critical'
            $table->string('action_taken', 30)->nullable();
            // 'warning_sent', 'exam_paused', 'disqualified', 'ignored'
            $table->text('details')->nullable();
            $table->json('evidence')->nullable();
            // {second_device_fingerprint, ip_addresses, timestamps, etc.}
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->foreignUuid('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('resolution_notes')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('olympiad_id');
            $table->index('user_id');
            $table->index('attempt_id');
            $table->index('violation_type');
            $table->index('severity');
            $table->index('is_resolved');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_violations');
    }
};
