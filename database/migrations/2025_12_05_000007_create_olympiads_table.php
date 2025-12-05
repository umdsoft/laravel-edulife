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
        Schema::create('olympiads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Basic Info
            $table->string('title', 255);
            $table->string('slug', 255)->unique();
            $table->text('description')->nullable();
            $table->string('short_description', 500)->nullable();
            
            // Type & Stage
            $table->foreignUuid('olympiad_type_id')->constrained('olympiad_types')->onDelete('cascade');
            $table->foreignUuid('stage_id')->nullable()->constrained('olympiad_stages')->onDelete('set null');
            $table->foreignUuid('series_id')->nullable()->constrained('olympiad_series')->onDelete('set null');
            
            // Linked Olympiads (for stages)
            $table->uuid('previous_olympiad_id')->nullable();
            $table->uuid('next_olympiad_id')->nullable();
            
            // Geographic Scope
            $table->foreignUuid('region_id')->nullable()->constrained('regions')->onDelete('set null');
            $table->foreignUuid('district_id')->nullable()->constrained('districts')->onDelete('set null');
            $table->foreignUuid('school_id')->nullable()->constrained('schools')->onDelete('set null');
            
            // Difficulty
            $table->string('difficulty_level', 20)->default('intermediate');
            // 'beginner', 'intermediate', 'advanced', 'expert'
            $table->json('grade_levels')->nullable();
            // [9, 10, 11]
            
            // Media
            $table->string('banner_image', 500)->nullable();
            $table->string('thumbnail', 500)->nullable();
            $table->string('promo_video_url', 500)->nullable();
            
            // Schedule
            $table->timestamp('registration_start_at')->nullable();
            $table->timestamp('registration_end_at')->nullable();
            $table->timestamp('olympiad_start_at')->nullable();
            $table->timestamp('olympiad_end_at')->nullable();
            $table->timestamp('results_publish_at')->nullable();
            
            // Sections Config (CRITICAL - defines exam structure)
            $table->json('sections_config');
            /*
             * LANGUAGE OLYMPIAD:
             * {
             *   "test": {"enabled": true, "duration_minutes": 30, "questions_count": 30, ...},
             *   "listening": {"enabled": true, "duration_minutes": 25, ...},
             *   "reading": {...},
             *   "writing": {"enabled": true, "requires_manual_grading": true, ...},
             *   "speaking": {...}
             * }
             */
            
            $table->integer('total_duration_minutes');
            $table->integer('total_max_points');
            
            // Entry Fee
            $table->decimal('entry_fee', 12, 2)->default(0);
            $table->integer('entry_fee_coins')->default(0);
            $table->integer('max_participants')->nullable();
            $table->integer('min_participants')->default(10);
            
            // Demo Test Config
            $table->boolean('demo_enabled')->default(true);
            $table->json('demo_config')->nullable();
            /*
             * {
             *   "questions_count": 15,
             *   "duration_minutes": 45,
             *   "price_coins": 100,
             *   "max_attempts": 3,
             *   "free_attempts": 1,
             *   "show_answers": true,
             *   "show_explanations": true
             * }
             */
            $table->timestamp('demo_available_from')->nullable();
            $table->timestamp('demo_available_until')->nullable();
            
            // Ranking
            $table->json('ranking_config')->nullable();
            /*
             * {
             *   "method": "score_then_time",
             *   "time_bonus_enabled": false
             * }
             */
            
            // Advancement to Next Stage
            $table->boolean('advancement_enabled')->default(false);
            $table->json('advancement_config')->nullable();
            /*
             * {
             *   "criteria": {"top_n": 50, "min_score_percent": 60},
             *   "auto_register": true
             * }
             */
            
            // Rewards
            $table->json('reward_config')->nullable();
            /*
             * {
             *   "1": {"discount_percent": 60, "bonus_coins": 500, "badge_id": null},
             *   "2": {...},
             *   "3": {...},
             *   "4-10": {...},
             *   "11+": {"reward_coins_percent": 10}
             * }
             */
            
            // Prize Pool (for National stage)
            $table->decimal('prize_pool', 15, 2)->default(0);
            $table->json('prize_distribution')->nullable();
            /*
             * {
             *   "1": {"cash": 5000000, "coins": 10000},
             *   "2": {"cash": 3000000, "coins": 7000},
             *   ...
             * }
             */
            
            // Results Display
            $table->json('results_config')->nullable();
            /*
             * {
             *   "show_to_participant": true,
             *   "show_correct_answers": true,
             *   "show_explanations": true,
             *   "allow_download": true
             * }
             */
            
            // Anti-Cheat Config
            $table->json('anti_cheat_config')->nullable();
            /*
             * {
             *   "single_device_only": true,
             *   "auto_disqualify_on_multiple_device": true,
             *   "device_lock_enabled": true,
             *   "tab_switch_detection": true,
             *   "max_tab_switches": 3,
             *   "fullscreen_required": true,
             *   "max_fullscreen_exits": 2,
             *   "devtools_detection": true,
             *   "copy_paste_disabled": true,
             *   "right_click_disabled": true,
             *   "heartbeat_interval_seconds": 10,
             *   "missed_heartbeats_limit": 6,
             *   "save_answers_on_disqualify": true,
             *   "allow_appeal": true,
             *   "appeal_deadline_hours": 24
             * }
             */
            
            // Status
            $table->string('status', 30)->default('draft');
            // 'draft', 'upcoming', 'registration_open', 'registration_closed', 
            // 'live', 'grading', 'results_ready', 'completed', 'cancelled'
            $table->string('visibility', 20)->default('public');
            // 'public', 'private', 'invite_only', 'region_only'
            $table->boolean('is_featured')->default(false);
            $table->boolean('live_leaderboard_enabled')->default(true);
            
            // Rules & Terms
            $table->text('rules')->nullable();
            $table->text('terms_conditions')->nullable();
            
            // Certificate
            $table->boolean('certificate_enabled')->default(true);
            $table->foreignUuid('certificate_template_id')->nullable()->constrained('certificate_templates')->onDelete('set null');
            
            // Audit
            $table->foreignUuid('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            // Self-referencing foreign keys
            $table->foreign('previous_olympiad_id')->references('id')->on('olympiads')->onDelete('set null');
            $table->foreign('next_olympiad_id')->references('id')->on('olympiads')->onDelete('set null');
            
            // Indexes
            $table->index('olympiad_type_id');
            $table->index('stage_id');
            $table->index('series_id');
            $table->index('status');
            $table->index(['olympiad_start_at', 'olympiad_end_at']);
            $table->index('visibility');
            $table->index('is_featured');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('olympiads');
    }
};
