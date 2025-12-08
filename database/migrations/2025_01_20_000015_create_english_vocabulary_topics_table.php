<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('english_vocabulary_topics', function (Blueprint $table) {
            $table->uuid('vocabulary_id');
            $table->uuid('topic_id');
            $table->boolean('is_primary')->default(false);

            $table->primary(['vocabulary_id', 'topic_id']);
            $table->foreign('vocabulary_id')->references('id')->on('english_vocabulary')->onDelete('cascade');
            $table->foreign('topic_id')->references('id')->on('english_topics')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('english_vocabulary_topics');
    }
};
