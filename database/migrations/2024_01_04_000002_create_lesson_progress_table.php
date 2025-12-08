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
        Schema::create('lesson_progress', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('lesson_id')->constrained()->onDelete('cascade');
            // enrollment_id foreign key added later via separate migration (enrollments table created after this)
            $table->uuid('enrollment_id');
            $table->decimal('progress', 5, 2)->default(0);
            $table->decimal('video_progress', 5, 2)->default(0);
            $table->integer('last_position')->default(0); // video sekundlarda
            $table->integer('watched_duration')->default(0); // unique tomosha vaqti
            $table->json('watched_intervals')->nullable(); // [[0,120], [200,300]]
            $table->integer('time_spent')->default(0);
            $table->boolean('is_completed')->default(false);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'lesson_id']);
            $table->index('user_id');
            $table->index('lesson_id');
            $table->index('enrollment_id');
            $table->index('is_completed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_progress');
    }
};
