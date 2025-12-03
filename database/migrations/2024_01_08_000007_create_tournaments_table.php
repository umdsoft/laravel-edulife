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
        Schema::create('tournaments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 200);
            $table->string('slug', 200)->unique();
            $table->text('description')->nullable();
            $table->foreignUuid('direction_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('course_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('type', ['weekly', 'monthly', 'seasonal', 'special'])->default('weekly');
            $table->enum('format', ['single_elimination', 'double_elimination'])->default('single_elimination');
            $table->enum('status', ['draft', 'registration', 'in_progress', 'finished', 'cancelled'])->default('draft');
            
            // Participants
            $table->integer('min_participants')->default(8);
            $table->integer('max_participants')->default(64);
            $table->integer('current_participants')->default(0);
            
            // Requirements
            $table->enum('min_subscription', ['trial', 'standart', 'premium', 'vip'])->default('trial');
            $table->integer('min_elo')->nullable();
            $table->integer('max_elo')->nullable();
            $table->integer('entry_fee_coins')->default(0);
            
            // Prizes
            $table->integer('prize_pool_coins')->default(0);
            $table->json('prizes')->nullable(); // [{"place": 1, "coins": 3000}, ...]
            
            // Timing
            $table->timestamp('registration_starts_at')->nullable();
            $table->timestamp('registration_ends_at')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            
            // Match settings
            $table->integer('questions_per_match')->default(5);
            $table->integer('time_per_question')->default(20);
            
            $table->timestamps();
            
            $table->index('direction_id');
            $table->index('status');
            $table->index('type');
            $table->index('starts_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournaments');
    }
};
