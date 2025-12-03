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
        Schema::create('battle_answers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('battle_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('battle_question_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->json('answer')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->integer('time_ms')->nullable(); // javob berish vaqti millisekund
            $table->integer('points_earned')->default(0);
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();
            
            $table->unique(['battle_question_id', 'user_id']);
            $table->index('battle_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('battle_answers');
    }
};
