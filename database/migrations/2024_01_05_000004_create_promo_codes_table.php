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
        Schema::create('promo_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 50)->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['percentage', 'fixed']);
            $table->decimal('value', 12, 2); // 20 (%) yoki 50000 (UZS)
            $table->decimal('min_purchase', 12, 2)->nullable();
            $table->decimal('max_discount', 12, 2)->nullable();
            $table->enum('applies_to', ['all', 'courses', 'subscriptions', 'specific_course']);
            $table->foreignUuid('course_id')->nullable()->constrained()->onDelete('cascade');
            $table->integer('usage_limit')->nullable(); // null = unlimited
            $table->integer('usage_count')->default(0);
            $table->integer('per_user_limit')->default(1);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignUuid('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index('code');
            $table->index('is_active');
            $table->index('applies_to');
            $table->index(['starts_at', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promo_codes');
    }
};
