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
        Schema::create('enrollments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('course_id')->constrained()->onDelete('cascade');
            $table->enum('access_type', ['purchase', 'subscription', 'free', 'gift'])->default('purchase');
            $table->enum('status', ['active', 'completed', 'expired', 'refunded'])->default('active');
            $table->decimal('progress', 5, 2)->default(0);
            $table->integer('completed_lessons')->default(0);
            $table->integer('total_lessons')->default(0);
            $table->integer('time_spent')->default(0); // sekundlarda
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            // payment_id foreign key qo'shiladi payments jadvali yaratilganidan keyin
            $table->uuid('payment_id')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'course_id']);
            $table->index('user_id');
            $table->index('course_id');
            $table->index('status');
            $table->index('access_type');
            $table->index('progress');
            $table->index('last_accessed_at');
            $table->index('completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
