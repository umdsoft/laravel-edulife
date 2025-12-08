<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('english_example_sentences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vocabulary_id');

            // Sentence
            $table->text('sentence');
            $table->text('sentence_uz')->nullable();
            $table->text('sentence_ru')->nullable();

            // Highlight the word in sentence
            $table->string('highlight_word')->nullable();
            $table->integer('word_position')->nullable();

            // Audio
            $table->string('audio_url')->nullable();

            // Context
            $table->enum('context', [
                'formal',
                'informal',
                'business',
                'academic',
                'everyday',
                'literary',
                'technical'
            ])->default('everyday');

            // Level appropriateness
            $table->uuid('level_id')->nullable();

            // Order
            $table->integer('order_number')->default(0);
            $table->boolean('is_primary')->default(false);

            $table->timestamps();

            $table->foreign('vocabulary_id')->references('id')->on('english_vocabulary')->onDelete('cascade');
            $table->foreign('level_id')->references('id')->on('english_levels')->onDelete('set null');
            $table->index(['vocabulary_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('english_example_sentences');
    }
};
