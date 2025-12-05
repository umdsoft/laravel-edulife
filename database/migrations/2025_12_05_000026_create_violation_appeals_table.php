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
        Schema::create('violation_appeals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('violation_id')->constrained('security_violations')->onDelete('cascade');
            $table->foreignUuid('olympiad_id')->constrained('olympiads')->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->text('appeal_reason');
            $table->json('supporting_evidence')->nullable();
            // Uploaded screenshots, documents, etc.
            $table->string('status', 20)->default('pending');
            // 'pending', 'under_review', 'approved', 'rejected'
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('deadline_at')->nullable();
            // 24 hours after disqualification
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignUuid('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('review_notes')->nullable();
            $table->string('resolution', 30)->nullable();
            // 'reinstated', 'warning_reduced', 'upheld', 'partial'
            $table->timestamps();
            
            // Indexes
            $table->index('violation_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('deadline_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('violation_appeals');
    }
};
