<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('english_collocations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vocabulary_id');

            // Collocation
            $table->string('collocation', 200);
            $table->enum('collocation_type', [
                'adjective_noun',
                'verb_noun',
                'verb_adverb',
                'adverb_adjective',
                'noun_noun',
                'verb_preposition'
            ]);

            $table->text('definition')->nullable();
            $table->text('definition_uz')->nullable();
            $table->text('example_sentence')->nullable();
            $table->text('example_sentence_uz')->nullable();

            // Frequency
            $table->enum('frequency', ['very_common', 'common', 'less_common'])->default('common');

            $table->timestamps();

            $table->foreign('vocabulary_id')->references('id')->on('english_vocabulary')->onDelete('cascade');
            $table->index('collocation_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('english_collocations');
    }
};
