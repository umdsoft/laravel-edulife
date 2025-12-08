<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('english_pronunciation_words', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vocabulary_id')->nullable();
            $table->uuid('level_id');
            $table->string('text', 200);
            $table->enum('type', ['word', 'phrase', 'sentence', 'tongue_twister']);
            $table->string('phonetic_uk', 200)->nullable();
            $table->string('phonetic_us', 200)->nullable();
            $table->string('audio_uk')->nullable();
            $table->string('audio_us')->nullable();
            $table->string('audio_slow')->nullable();
            $table->json('syllables')->nullable();
            $table->integer('syllable_count')->default(1);
            $table->json('stress_pattern')->nullable();
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium');
            $table->json('common_mistakes')->nullable();
            $table->json('focus_sounds')->nullable();
            $table->integer('times_practiced')->default(0);
            $table->decimal('average_score', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('order_number')->default(0);
            $table->timestamps();

            $table->foreign('vocabulary_id')->references('id')->on('english_vocabulary')->onDelete('set null');
            $table->foreign('level_id')->references('id')->on('english_levels')->onDelete('cascade');
            $table->index(['level_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('english_pronunciation_words');
    }
};
