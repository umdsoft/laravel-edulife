<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guardian <-> student links are requested by the parent and confirmed by the
 * teacher/admin (there is no SMS provider to verify the child's phone):
 * verified_at (existing) = approved; these columns keep who decided and a
 * rejection, so a rejected request is not silently re-approved.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guardian_student', function (Blueprint $table) {
            $table->foreignUuid('approved_by')->nullable()->after('verified_at')->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable()->after('approved_by');
            $table->string('reject_reason', 255)->nullable()->after('rejected_at');

            $table->index(['verified_at', 'rejected_at']);
        });
    }

    public function down(): void
    {
        Schema::table('guardian_student', function (Blueprint $table) {
            $table->dropIndex(['verified_at', 'rejected_at']);
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['rejected_at', 'reject_reason']);
        });
    }
};
