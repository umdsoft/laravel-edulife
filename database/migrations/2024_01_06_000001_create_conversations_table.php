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
        Schema::create('conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('participant1_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('participant2_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('course_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignUuid('last_message_id')->nullable();
            $table->integer('participant1_unread')->default(0);
            $table->integer('participant2_unread')->default(0);
            $table->timestamp('participant1_last_read_at')->nullable();
            $table->timestamp('participant2_last_read_at')->nullable();
            $table->boolean('participant1_deleted')->default(false);
            $table->boolean('participant2_deleted')->default(false);
            $table->timestamps();
            
            $table->unique(['participant1_id', 'participant2_id']);
            $table->index('participant1_id');
            $table->index('participant2_id');
            $table->index('course_id');
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
