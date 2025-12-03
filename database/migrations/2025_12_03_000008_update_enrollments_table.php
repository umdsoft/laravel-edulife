<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            // Add new columns
            if (!Schema::hasColumn('enrollments', 'is_completed')) {
                $table->boolean('is_completed')->default(false)->after('total_lessons');
            }
            if (!Schema::hasColumn('enrollments', 'last_lesson_id')) {
                $table->foreignUuid('last_lesson_id')->nullable()->constrained('lessons')->nullOnDelete()->after('last_accessed_at');
            }
            if (!Schema::hasColumn('enrollments', 'paid_amount')) {
                $table->decimal('paid_amount', 12, 2)->default(0)->after('payment_id');
            }
            if (!Schema::hasColumn('enrollments', 'payment_status')) {
                $table->string('payment_status')->default('free')->after('paid_amount');
            }
            if (!Schema::hasColumn('enrollments', 'certificate_issued')) {
                $table->boolean('certificate_issued')->default(false)->after('payment_status');
            }
            if (!Schema::hasColumn('enrollments', 'certificate_id')) {
                $table->foreignUuid('certificate_id')->nullable()->after('certificate_issued');
            }
            if (!Schema::hasColumn('enrollments', 'deleted_at')) {
                $table->softDeletes();
            }
            
            // Rename columns if they exist and target doesn't
            if (Schema::hasColumn('enrollments', 'time_spent') && !Schema::hasColumn('enrollments', 'total_watch_time')) {
                $table->renameColumn('time_spent', 'total_watch_time');
            }
            if (Schema::hasColumn('enrollments', 'last_accessed_at') && !Schema::hasColumn('enrollments', 'last_activity_at')) {
                $table->renameColumn('last_accessed_at', 'last_activity_at');
            }
        });

        // Migrate data
        DB::statement("UPDATE enrollments SET is_completed = 1 WHERE status = 'completed'");
        DB::statement("UPDATE enrollments SET payment_status = 'paid' WHERE access_type = 'purchase'");
        DB::statement("UPDATE enrollments SET payment_status = 'refunded' WHERE status = 'refunded'");
        
        // Change progress column type if needed (decimal to integer) - skipping for now to avoid data loss issues, casting in model is safer.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            if (Schema::hasColumn('enrollments', 'is_completed')) {
                $table->dropColumn('is_completed');
            }
            if (Schema::hasColumn('enrollments', 'last_lesson_id')) {
                $table->dropForeign(['last_lesson_id']);
                $table->dropColumn('last_lesson_id');
            }
            if (Schema::hasColumn('enrollments', 'paid_amount')) {
                $table->dropColumn('paid_amount');
            }
            if (Schema::hasColumn('enrollments', 'payment_status')) {
                $table->dropColumn('payment_status');
            }
            if (Schema::hasColumn('enrollments', 'certificate_issued')) {
                $table->dropColumn('certificate_issued');
            }
            if (Schema::hasColumn('enrollments', 'certificate_id')) {
                $table->dropColumn('certificate_id');
            }
            if (Schema::hasColumn('enrollments', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
            
            if (Schema::hasColumn('enrollments', 'total_watch_time')) {
                $table->renameColumn('total_watch_time', 'time_spent');
            }
            if (Schema::hasColumn('enrollments', 'last_activity_at')) {
                $table->renameColumn('last_activity_at', 'last_accessed_at');
            }
        });
    }
};
