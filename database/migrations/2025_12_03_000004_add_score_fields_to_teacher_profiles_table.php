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
        if (!Schema::hasColumn('teacher_profiles', 'score')) {
            Schema::table('teacher_profiles', function (Blueprint $table) {
                $table->decimal('score', 5, 2)->default(0)->after('commission_rate');
                $table->json('score_breakdown')->nullable()->after('score');
                $table->timestamp('score_updated_at')->nullable()->after('score_breakdown');
                $table->timestamp('level_changed_at')->nullable()->after('score_updated_at');
                
                $table->index('score');
                $table->index('level');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_profiles', function (Blueprint $table) {
            $table->dropIndex(['score']);
            $table->dropIndex(['level']);
            $table->dropColumn(['score', 'score_breakdown', 'score_updated_at', 'level_changed_at']);
        });
    }
};
