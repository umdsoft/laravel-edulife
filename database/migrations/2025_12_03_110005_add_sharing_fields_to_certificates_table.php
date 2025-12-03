<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            // Certificate info
            $table->string('certificate_number')->unique()->after('enrollment_id');
            $table->string('recipient_name')->after('certificate_number'); // Snapshot
            $table->string('course_title')->after('recipient_name'); // Snapshot
            $table->string('teacher_name')->after('course_title'); // Snapshot
            
            // Completion data
            $table->decimal('final_score', 5, 2)->nullable();
            $table->unsignedInteger('completion_hours')->default(0);
            $table->date('completion_date');
            
            // Files
            $table->string('pdf_path')->nullable();
            $table->string('image_path')->nullable();
            $table->string('qr_code_path')->nullable();
            
            // Verification
            $table->string('verification_code')->unique();
            $table->string('verification_url')->nullable();
            
            // Status
            $table->boolean('is_valid')->default(true);
            $table->dateTime('revoked_at')->nullable();
            $table->string('revoke_reason')->nullable();
            
            // Sharing
            $table->boolean('is_public')->default(true);
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('downloads_count')->default(0);
            
            $table->dateTime('issued_at');
            
            $table->index(['user_id', 'issued_at']);
            $table->index('verification_code');
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn([
                'certificate_number', 'recipient_name', 'course_title', 'teacher_name',
                'final_score', 'completion_hours', 'completion_date',
                'pdf_path', 'image_path', 'qr_code_path',
                'verification_code', 'verification_url',
                'is_valid', 'revoked_at', 'revoke_reason',
                'is_public', 'views_count', 'downloads_count',
                'issued_at',
            ]);
        });
    }
};
