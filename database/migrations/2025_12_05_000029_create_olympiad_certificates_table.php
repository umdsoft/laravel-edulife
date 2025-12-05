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
        Schema::create('olympiad_certificates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('olympiad_id')->constrained('olympiads')->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('attempt_id')->nullable()->constrained('olympiad_attempts')->onDelete('set null');
            $table->foreignUuid('template_id')->nullable()->constrained('certificate_templates')->onDelete('set null');
            $table->string('certificate_type', 30);
            // 'participation', 'winner_1', 'winner_2', 'winner_3', 'top_10', 'advancement'
            $table->string('certificate_number', 50)->unique();
            $table->integer('rank')->nullable();
            $table->decimal('score', 10, 2)->nullable();
            $table->string('pdf_path', 500)->nullable();
            $table->string('qr_code', 500)->nullable();
            $table->string('verification_code', 50)->unique();
            $table->json('certificate_data')->nullable();
            // {name, olympiad_title, date, rank, score, etc.}
            $table->boolean('is_verified')->default(true);
            $table->timestamp('issued_at')->nullable();
            $table->integer('downloads_count')->default(0);
            $table->timestamps();
            
            // Indexes
            $table->index('olympiad_id');
            $table->index('user_id');
            $table->index('certificate_type');
            $table->index('verification_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('olympiad_certificates');
    }
};
