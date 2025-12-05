<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lab Ratings - Tajriba baholari va sharhlar
     */
    public function up(): void
    {
        Schema::create('lab_ratings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('experiment_id')->constrained('lab_experiments')->cascadeOnDelete();
            $table->foreignUuid('attempt_id')->nullable()->constrained('lab_attempts')->nullOnDelete();
            
            // Rating (1-5 yulduz)
            $table->unsignedTinyInteger('rating'); // 1-5
            
            // Sharh
            $table->text('review_text')->nullable();
            
            // Nima yoqdi/yoqmadi
            $table->json('liked_aspects')->nullable();
            // ['clear_instructions', 'realistic_simulation', 'good_visuals', 'educational']
            
            $table->json('disliked_aspects')->nullable();
            // ['too_long', 'confusing', 'buggy', 'too_easy', 'too_hard']
            
            // Difficulty feedback
            $table->enum('difficulty_rating', ['too_easy', 'just_right', 'too_hard'])->nullable();
            
            // Would recommend
            $table->boolean('would_recommend')->nullable();
            
            // Status
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_hidden')->default(false);
            
            // Helpful votes
            $table->unsignedSmallInteger('helpful_count')->default(0);
            $table->unsignedSmallInteger('not_helpful_count')->default(0);
            
            // Admin response
            $table->text('admin_response')->nullable();
            $table->timestamp('admin_responded_at')->nullable();
            
            $table->timestamps();
            
            // One rating per user per experiment
            $table->unique(['user_id', 'experiment_id']);
            
            // Indexes
            $table->index('experiment_id');
            $table->index('rating');
            $table->index('is_approved');
            $table->index('is_featured');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_ratings');
    }
};
