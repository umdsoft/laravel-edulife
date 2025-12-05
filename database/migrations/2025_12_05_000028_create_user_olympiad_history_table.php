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
        Schema::create('user_olympiad_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('olympiad_id')->constrained('olympiads')->onDelete('cascade');
            $table->foreignUuid('olympiad_type_id')->constrained('olympiad_types')->onDelete('cascade');
            $table->foreignUuid('stage_id')->nullable()->constrained('olympiad_stages')->onDelete('set null');
            $table->foreignUuid('series_id')->nullable()->constrained('olympiad_series')->onDelete('set null');
            $table->foreignUuid('attempt_id')->nullable()->constrained('olympiad_attempts')->onDelete('set null');
            $table->string('status', 30);
            // 'participated', 'completed', 'disqualified', 'no_show', 'advanced'
            $table->integer('rank')->nullable();
            $table->integer('total_participants')->nullable();
            $table->integer('percentile')->nullable();
            $table->decimal('score', 10, 2)->nullable();
            $table->decimal('max_score', 10, 2)->nullable();
            $table->decimal('score_percent', 5, 2)->nullable();
            $table->json('section_scores')->nullable();
            $table->integer('badges_earned')->default(0);
            $table->integer('coins_earned')->default(0);
            $table->decimal('cash_prize', 15, 2)->default(0);
            $table->boolean('certificate_issued')->default(false);
            $table->boolean('advanced_to_next_stage')->default(false);
            $table->foreignUuid('next_olympiad_id')->nullable()->constrained('olympiads')->onDelete('set null');
            $table->timestamps();
            
            // Unique constraint
            $table->unique(['user_id', 'olympiad_id']);
            
            // Indexes
            $table->index('user_id');
            $table->index('olympiad_id');
            $table->index('olympiad_type_id');
            $table->index('rank');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_olympiad_history');
    }
};
