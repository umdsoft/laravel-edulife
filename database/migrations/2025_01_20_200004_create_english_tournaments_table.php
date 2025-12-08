<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('english_tournaments', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Tournament info
            $table->string('name', 200);
            $table->string('name_uz', 200);
            $table->text('description')->nullable();
            $table->text('description_uz')->nullable();
            $table->string('slug', 100)->unique();

            // Type
            $table->enum('tournament_type', [
                'single_elimination',
                'double_elimination',
                'round_robin',
                'swiss',
                'league'
            ])->default('single_elimination');

            // Level requirement
            $table->uuid('level_id')->nullable();
            $table->integer('min_elo')->default(0);
            $table->integer('max_elo')->nullable();

            // Participants
            $table->integer('min_participants')->default(8);
            $table->integer('max_participants')->default(64);
            $table->integer('current_participants')->default(0);

            // Schedule
            $table->timestamp('registration_start')->nullable();
            $table->timestamp('registration_end')->nullable();
            $table->timestamp('tournament_start')->nullable();
            $table->timestamp('tournament_end')->nullable();

            // Entry
            $table->integer('entry_fee_coins')->default(0);
            $table->boolean('is_free')->default(true);

            // Prizes
            $table->json('prizes');

            // Settings
            $table->json('settings')->nullable();

            // Results
            $table->foreignUuid('winner_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignUuid('runner_up_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignUuid('third_place_id')->nullable()->constrained('users')->onDelete('set null');

            // Visual
            $table->string('banner_image')->nullable();
            $table->string('icon')->nullable();
            $table->string('color', 20)->nullable();

            // Status
            $table->enum('status', ['draft', 'registration', 'ready', 'in_progress', 'completed', 'cancelled'])->default('draft');

            $table->boolean('is_featured')->default(false);
            $table->timestamps();

            $table->foreign('level_id')->references('id')->on('english_levels')->onDelete('set null');

            $table->index(['status', 'tournament_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('english_tournaments');
    }
};
