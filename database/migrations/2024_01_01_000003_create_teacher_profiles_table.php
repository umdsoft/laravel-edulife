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
        Schema::create('teacher_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->string('username', 50)->unique();
            $table->string('headline', 150)->nullable();
            $table->text('bio')->nullable();
            $table->json('specializations')->nullable();
            $table->string('website_url')->nullable();
            $table->string('youtube_url')->nullable();
            $table->string('telegram_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->enum('level', ['new', 'verified', 'featured', 'top'])->default('new');
            $table->decimal('commission_rate', 5, 2)->default(30.00);
            $table->decimal('avg_rating', 3, 2)->default(0);
            $table->integer('total_reviews')->default(0);
            $table->integer('total_students')->default(0);
            $table->integer('total_courses')->default(0);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('username');
            $table->index('level');
            $table->index('is_verified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_profiles');
    }
};
