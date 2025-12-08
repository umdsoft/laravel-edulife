<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('english_achievement_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('slug', 50)->unique();
            $table->string('name', 100);
            $table->string('name_uz', 100);
            $table->text('description')->nullable();
            $table->text('description_uz')->nullable();

            $table->string('icon', 50)->nullable();
            $table->string('color', 20)->nullable();

            $table->integer('order_number')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('english_achievement_categories');
    }
};
