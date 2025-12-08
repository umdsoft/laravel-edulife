<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('english_lessons', function (Blueprint $table) {
            // Add content column for storing lesson words, exercises, and quiz
            $table->json('content')->nullable()->after('grammar_rule_ids');
        });
    }

    public function down(): void
    {
        Schema::table('english_lessons', function (Blueprint $table) {
            $table->dropColumn('content');
        });
    }
};
