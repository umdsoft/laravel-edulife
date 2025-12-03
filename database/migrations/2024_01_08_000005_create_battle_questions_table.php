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
        Schema::create('battle_questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('battle_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('question_id')->constrained()->onDelete('cascade');
            $table->integer('order_index');
            $table->integer('time_limit')->default(20); // sekundlarda
            $table->integer('points')->default(100);
            $table->timestamp('shown_at')->nullable();
            $table->timestamps();
            
            $table->index('battle_id');
            $table->index(['battle_id', 'order_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('battle_questions');
    }
};
