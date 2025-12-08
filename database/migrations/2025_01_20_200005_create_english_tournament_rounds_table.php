<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('english_tournament_rounds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tournament_id');

            // Round info
            $table->integer('round_number');
            $table->string('round_name', 100);
            $table->string('round_name_uz', 100)->nullable();

            // Schedule
            $table->timestamp('scheduled_start')->nullable();
            $table->timestamp('scheduled_end')->nullable();
            $table->timestamp('actual_start')->nullable();
            $table->timestamp('actual_end')->nullable();

            // Status
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');

            // Matches count
            $table->integer('total_matches')->default(0);
            $table->integer('completed_matches')->default(0);

            $table->timestamps();

            $table->foreign('tournament_id')->references('id')->on('english_tournaments')->onDelete('cascade');
            $table->unique(['tournament_id', 'round_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('english_tournament_rounds');
    }
};
