<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('english_levels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 5)->unique(); // A1, A2, B1, B2, C1, C2
            $table->string('name', 50);
            $table->string('name_uz', 50);
            $table->text('description')->nullable();
            $table->text('description_uz')->nullable();
            $table->text('cefr_description')->nullable();

            // Can-Do Statements
            $table->json('can_do_statements')->nullable();

            // Learning targets
            $table->integer('vocabulary_target')->default(500);
            $table->integer('grammar_structures')->default(20);
            $table->integer('estimated_hours')->default(100);

            // XP requirements
            $table->integer('xp_required')->default(0);
            $table->integer('xp_to_complete')->default(5000);

            // IELTS mapping
            $table->decimal('ielts_band_min', 2, 1)->nullable();
            $table->decimal('ielts_band_max', 2, 1)->nullable();

            // Visual
            $table->string('color', 20)->nullable();
            $table->string('icon', 50)->nullable();
            $table->string('badge_image')->nullable();

            // Order & Status
            $table->integer('order_number')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('code');
            $table->index('order_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('english_levels');
    }
};
