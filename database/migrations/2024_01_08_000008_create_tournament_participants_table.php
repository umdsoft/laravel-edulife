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
        Schema::create('tournament_participants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tournament_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->integer('seed')->nullable(); // Seeding/bracket pozitsiyasi
            $table->enum('status', ['registered', 'checked_in', 'active', 'eliminated', 'winner', 'disqualified'])->default('registered');
            $table->integer('current_round')->default(0);
            $table->integer('wins')->default(0);
            $table->integer('losses')->default(0);
            $table->integer('coins_won')->default(0);
            $table->integer('xp_won')->default(0);
            $table->integer('final_place')->nullable();
            $table->timestamp('registered_at');
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('eliminated_at')->nullable();
            $table->timestamps();
            
            $table->unique(['tournament_id', 'user_id']);
            $table->index('tournament_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('seed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournament_participants');
    }
};
