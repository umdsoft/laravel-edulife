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
        Schema::create('olympiad_series', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->text('description')->nullable();
            $table->foreignUuid('olympiad_type_id')->constrained('olympiad_types')->onDelete('cascade');
            $table->integer('year');
            $table->string('season', 20)->nullable();
            // 'spring', 'fall', etc.
            $table->json('included_stages')->nullable();
            // Array of stage UUIDs
            $table->decimal('total_prize_pool', 15, 2)->default(0);
            $table->integer('total_participants')->default(0);
            $table->string('banner_image', 500)->nullable();
            $table->string('status', 20)->default('draft');
            // 'draft', 'active', 'completed', 'cancelled'
            $table->foreignUuid('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index('olympiad_type_id');
            $table->index('status');
            $table->index('year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('olympiad_series');
    }
};
