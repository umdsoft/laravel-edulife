<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournaments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            
            // Type
            $table->enum('type', ['weekly', 'monthly', 'seasonal', 'special'])->default('weekly');
            $table->enum('format', ['knockout', 'round_robin', 'swiss'])->default('knockout');
            
            // Settings
            $table->foreignUuid('direction_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('max_participants')->default(32);
            $table->unsignedInteger('min_participants')->default(8);
            $table->unsignedInteger('rounds_per_match')->default(5);
            $table->unsignedInteger('time_per_question')->default(15);
            
            // Requirements
            $table->unsignedInteger('min_level')->default(1);
            $table->unsignedInteger('entry_fee')->default(0); // COIN
            
            // Prizes
            $table->json('prizes'); // {"1": {"xp": 500, "coins": 1000}, "2": {...}}
            
            // Status
            $table->enum('status', ['upcoming', 'registration', 'in_progress', 'completed', 'cancelled'])->default('upcoming');
            $table->unsignedInteger('current_round')->default(0);
            
            // Timing
            $table->dateTime('registration_starts_at');
            $table->dateTime('registration_ends_at');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            
            $table->timestamps();
            
            $table->index(['status', 'starts_at']);
            $table->index(['type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournaments');
    }
};
