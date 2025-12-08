<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('english_grammar_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug', 50)->unique();
            $table->string('name', 100);
            $table->string('name_uz', 100);
            $table->text('description')->nullable();
            $table->text('description_uz')->nullable();

            // Parent (for subcategories)
            $table->uuid('parent_id')->nullable();

            // Visual
            $table->string('icon', 50)->nullable();
            $table->string('color', 20)->nullable();

            // Order & Status
            $table->integer('order_number')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('english_grammar_categories')->onDelete('set null');
            $table->index('slug');
            $table->index('order_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('english_grammar_categories');
    }
};
