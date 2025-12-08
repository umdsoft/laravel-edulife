<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('english_lesson_vocabulary', function (Blueprint $table) {
            $table->uuid('lesson_id');
            $table->uuid('vocabulary_id');
            $table->integer('order_number')->default(0);
            $table->boolean('is_key_word')->default(false);

            $table->primary(['lesson_id', 'vocabulary_id']);
            $table->foreign('lesson_id')->references('id')->on('english_lessons')->onDelete('cascade');
            $table->foreign('vocabulary_id')->references('id')->on('english_vocabulary')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('english_lesson_vocabulary');
    }
};
