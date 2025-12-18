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
        Schema::table('english_battles', function (Blueprint $table) {
            $table->string('code', 6)->nullable()->after('topic_focus');
            $table->string('name', 100)->nullable()->after('code');
            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('english_battles', function (Blueprint $table) {
            $table->dropIndex(['code']);
            $table->dropColumn(['code', 'name']);
        });
    }
};
