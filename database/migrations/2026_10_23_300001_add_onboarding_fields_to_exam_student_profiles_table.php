<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stage 7 onboarding: who filled the exam profile, the parent's phone and the
 * parent's consent to processing the child's data (spec section 11), and when
 * the student agreed to show their name on public leaderboards.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_student_profiles', function (Blueprint $table) {
            $table->string('filled_by', 10)->nullable()->after('user_id');          // parent | student
            $table->string('parent_phone', 20)->nullable()->after('school_name');
            $table->timestamp('parent_consent_at')->nullable()->after('parent_phone');
            $table->string('parent_consent_source', 20)->nullable()->after('parent_consent_at'); // web_parent | web_student | telegram
            $table->timestamp('public_name_consent_at')->nullable()->after('show_name_publicly');
            $table->timestamp('onboarded_at')->nullable()->after('placement_completed_at');

            $table->index('parent_phone');
        });
    }

    public function down(): void
    {
        Schema::table('exam_student_profiles', function (Blueprint $table) {
            $table->dropIndex(['parent_phone']);
            $table->dropColumn([
                'filled_by', 'parent_phone', 'parent_consent_at', 'parent_consent_source',
                'public_name_consent_at', 'onboarded_at',
            ]);
        });
    }
};
