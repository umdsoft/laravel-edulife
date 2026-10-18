<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The English learning module was removed from the platform (math & physics only).
 *
 * Fresh installs never create these tables any more; this migration cleans up
 * databases that ran the old English migrations. It DELETES all English data
 * and cannot be reversed — take a backup before running it in production.
 */
return new class extends Migration
{
    private const TABLES = [
        'english_achievement_categories',
        'english_achievements',
        'english_ai_conversations',
        'english_ai_messages',
        'english_ai_scenario_goals',
        'english_ai_scenarios',
        'english_battle_questions',
        'english_battle_rounds',
        'english_battles',
        'english_collocations',
        'english_common_mistakes',
        'english_daily_challenge_tasks',
        'english_daily_challenges',
        'english_example_sentences',
        'english_game_categories',
        'english_game_content',
        'english_game_levels',
        'english_game_rewards',
        'english_games',
        'english_grammar_categories',
        'english_grammar_examples',
        'english_grammar_exercises',
        'english_grammar_rules',
        'english_leaderboard_entries',
        'english_leaderboards',
        'english_lesson_grammar',
        'english_lesson_steps',
        'english_lesson_vocabulary',
        'english_lessons',
        'english_levels',
        'english_pronunciation_words',
        'english_reward_claims',
        'english_rewards',
        'english_streak_rewards',
        'english_topics',
        'english_tournament_participants',
        'english_tournament_rounds',
        'english_tournaments',
        'english_units',
        'english_vocabulary',
        'english_vocabulary_topics',
        'english_word_families',
        'user_daily_challenges',
        'user_english_profiles',
        'user_game_attempts',
        'user_lesson_progress',
        'user_notifications',
        'user_pronunciation_attempts',
        'user_skill_levels',
        'user_study_sessions',
        'user_study_streaks',
        'user_unit_progress',
        'user_vocabulary',
        'user_writing_submissions',
    ];

    public function up(): void
    {
        Schema::withoutForeignKeyConstraints(function () {
            // The old user_achievements table belonged to the English module
            // (FK to english_achievements, English-only columns). Rebuild it in
            // the shape the general achievement system uses.
            if (Schema::hasTable('user_achievements') && Schema::hasColumn('user_achievements', 'required_progress')) {
                Schema::drop('user_achievements');
                Schema::create('user_achievements', function (Blueprint $table) {
                    $table->uuid('id')->primary();
                    $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
                    $table->foreignUuid('achievement_id')->constrained('achievements')->cascadeOnDelete();
                    $table->integer('xp_rewarded')->default(0);
                    $table->integer('coin_rewarded')->default(0);
                    $table->timestamp('earned_at')->nullable();
                    $table->boolean('is_claimed')->default(false);
                    $table->timestamp('claimed_at')->nullable();
                    $table->timestamps();

                    $table->unique(['user_id', 'achievement_id']);
                    $table->index('earned_at');
                });
            }

            foreach (self::TABLES as $table) {
                Schema::dropIfExists($table);
            }
        });

        // English module settings rows
        if (Schema::hasTable('settings')) {
            \Illuminate\Support\Facades\DB::table('settings')->where('group', 'english')->delete();
        }
    }

    public function down(): void
    {
        // Irreversible: the English module and its data were removed on purpose.
    }
};
