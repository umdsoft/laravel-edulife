<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('english_word_families', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vocabulary_id');

            // Related word
            $table->string('word', 100);
            $table->enum('part_of_speech', [
                'noun',
                'verb',
                'adjective',
                'adverb',
                'other'
            ]);
            $table->enum('relation_type', [
                'noun_form',
                'verb_form',
                'adjective_form',
                'adverb_form',
                'negative',
                'comparative',
                'superlative',
                'past_tense',
                'past_participle'
            ]);

            $table->string('phonetic')->nullable();
            $table->text('definition')->nullable();
            $table->text('definition_uz')->nullable();
            $table->text('example_sentence')->nullable();

            $table->timestamps();

            $table->foreign('vocabulary_id')->references('id')->on('english_vocabulary')->onDelete('cascade');
            $table->index('word');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('english_word_families');
    }
};
