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
        Schema::create('olympiad_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('direction_id')->constrained('directions')->onDelete('cascade');
            $table->string('name', 50)->unique();
            // Values: 'math', 'physics'
            $table->string('display_name', 255);
            $table->string('display_name_uz', 255);
            $table->text('description')->nullable();
            $table->json('sections');
            // Math / Physics: ["test"]
            $table->json('section_weights');
            // Math / Physics: {"test": 100}
            $table->string('icon', 50)->nullable();
            $table->string('color', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('direction_id');
            $table->index('is_active');
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('olympiad_types');
    }
};
