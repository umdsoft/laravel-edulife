<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            // Certificate info - check if columns exist before adding
            if (!Schema::hasColumn('certificates', 'certificate_number')) {
                $table->string('certificate_number')->unique()->after('enrollment_id');
            }
            if (!Schema::hasColumn('certificates', 'recipient_name')) {
                $table->string('recipient_name')->nullable()->after('certificate_number');
            }
            if (!Schema::hasColumn('certificates', 'course_title')) {
                $table->string('course_title')->nullable()->after('recipient_name');
            }
            if (!Schema::hasColumn('certificates', 'teacher_name')) {
                $table->string('teacher_name')->nullable()->after('course_title');
            }
            
            // Completion data
            if (!Schema::hasColumn('certificates', 'final_score')) {
                $table->decimal('final_score', 5, 2)->nullable();
            }
            if (!Schema::hasColumn('certificates', 'completion_hours')) {
                $table->unsignedInteger('completion_hours')->default(0);
            }
            if (!Schema::hasColumn('certificates', 'completion_date')) {
                $table->date('completion_date')->nullable();
            }
            
            // Files
            if (!Schema::hasColumn('certificates', 'pdf_path')) {
                $table->string('pdf_path')->nullable();
            }
            if (!Schema::hasColumn('certificates', 'image_path')) {
                $table->string('image_path')->nullable();
            }
            if (!Schema::hasColumn('certificates', 'qr_code_path')) {
                $table->string('qr_code_path')->nullable();
            }
            
            // Verification
            if (!Schema::hasColumn('certificates', 'verification_code')) {
                $table->string('verification_code')->unique()->nullable();
            }
            if (!Schema::hasColumn('certificates', 'verification_url')) {
                $table->string('verification_url')->nullable();
            }
            
            // Status
            if (!Schema::hasColumn('certificates', 'is_valid')) {
                $table->boolean('is_valid')->default(true);
            }
            if (!Schema::hasColumn('certificates', 'revoked_at')) {
                $table->dateTime('revoked_at')->nullable();
            }
            if (!Schema::hasColumn('certificates', 'revoke_reason')) {
                $table->string('revoke_reason')->nullable();
            }
            
            // Sharing
            if (!Schema::hasColumn('certificates', 'is_public')) {
                $table->boolean('is_public')->default(true);
            }
            if (!Schema::hasColumn('certificates', 'views_count')) {
                $table->unsignedInteger('views_count')->default(0);
            }
            if (!Schema::hasColumn('certificates', 'downloads_count')) {
                $table->unsignedInteger('downloads_count')->default(0);
            }
            
            if (!Schema::hasColumn('certificates', 'issued_at')) {
                $table->dateTime('issued_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $columns = [
                'certificate_number', 'recipient_name', 'course_title', 'teacher_name',
                'final_score', 'completion_hours', 'completion_date',
                'pdf_path', 'image_path', 'qr_code_path',
                'verification_code', 'verification_url',
                'is_valid', 'revoked_at', 'revoke_reason',
                'is_public', 'views_count', 'downloads_count',
                'issued_at',
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('certificates', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
