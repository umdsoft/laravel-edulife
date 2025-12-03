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
        Schema::table('certificates', function (Blueprint $table) {
            $table->string('certificate_code', 20)->unique()->after('id');
            $table->string('verification_url')->nullable()->after('certificate_code');
            $table->string('qr_code_path')->nullable()->after('verification_url');
            $table->string('pdf_path')->nullable()->after('qr_code_path');
            $table->foreignUuid('template_id')->nullable()->after('pdf_path')->constrained('certificate_templates')->nullOnDelete();
            $table->json('certificate_data')->nullable()->after('template_id');
            $table->timestamp('verified_at')->nullable();
            $table->unsignedInteger('verification_count')->default(0);

            $table->index('certificate_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropForeign(['template_id']);
            $table->dropColumn([
                'certificate_code',
                'verification_url',
                'qr_code_path',
                'pdf_path',
                'template_id',
                'certificate_data',
                'verified_at',
                'verification_count'
            ]);
        });
    }
};
