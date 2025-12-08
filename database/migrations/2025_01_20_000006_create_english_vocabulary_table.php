<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('english_vocabulary', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Word
            $table->string('word', 100);
            $table->string('word_lowercase', 100)->index();

            // Pronunciation
            $table->string('phonetic_uk', 100)->nullable();
            $table->string('phonetic_us', 100)->nullable();
            $table->string('audio_uk')->nullable();
            $table->string('audio_us')->nullable();

            // Part of speech
            $table->enum('part_of_speech', [
                'noun',
                'verb',
                'adjective',
                'adverb',
                'pronoun',
                'preposition',
                'conjunction',
                'interjection',
                'determiner',
                'phrasal_verb',
                'idiom',
                'phrase'
            ]);

            // Definitions
            $table->text('definition');
            $table->text('definition_uz')->nullable();
            $table->text('definition_ru')->nullable();
            $table->text('definition_simple')->nullable();

            // Level
            $table->uuid('level_id');
            $table->integer('frequency_rank')->nullable();

            // Categorization
            $table->json('tags')->nullable();

            // Media
            $table->string('image')->nullable();
            $table->string('image_thumbnail')->nullable();

            // Statistics
            $table->integer('times_practiced')->default(0);
            $table->integer('times_correct')->default(0);
            $table->decimal('difficulty_score', 3, 2)->default(0.50);

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_common')->default(false);

            $table->timestamps();

            $table->foreign('level_id')->references('id')->on('english_levels')->onDelete('cascade');
            $table->unique(['word', 'part_of_speech']);
            $table->index(['level_id', 'part_of_speech']);
            $table->index('is_common');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('english_vocabulary');
    }
};
