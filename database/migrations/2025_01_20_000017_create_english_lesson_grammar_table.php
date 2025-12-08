<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('english_lesson_grammar', function (Blueprint $table) {
            $table->uuid('lesson_id');
            $table->uuid('grammar_rule_id');
            $table->integer('order_number')->default(0);
            $table->boolean('is_main_focus')->default(false);

            $table->primary(['lesson_id', 'grammar_rule_id']);
            $table->foreign('lesson_id')->references('id')->on('english_lessons')->onDelete('cascade');
            $table->foreign('grammar_rule_id')->references('id')->on('english_grammar_rules')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('english_lesson_grammar');
    }
};
