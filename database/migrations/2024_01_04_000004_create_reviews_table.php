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
        Schema::create('reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('course_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('enrollment_id')->constrained()->onDelete('cascade');
            $table->integer('rating'); // 1-5
            $table->string('title', 150)->nullable();
            $table->text('review_text');
            $table->json('pros')->nullable(); // ["Yaxshi tushuntirilgan", "Amaliy"]
            $table->json('cons')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->integer('helpful_count')->default(0);
            $table->text('admin_reply')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['user_id', 'course_id']);
            $table->index('user_id');
            $table->index('course_id');
            $table->index('rating');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
