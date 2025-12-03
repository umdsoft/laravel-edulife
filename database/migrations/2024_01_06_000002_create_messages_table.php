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
        Schema::create('messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('conversation_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('sender_id')->constrained('users')->onDelete('cascade');
            $table->text('body')->nullable();
            $table->enum('type', ['text', 'image', 'file'])->default('text');
            $table->string('attachment_url')->nullable();
            $table->string('attachment_name')->nullable();
            $table->integer('attachment_size')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('conversation_id');
            $table->index('sender_id');
            $table->index('is_read');
            $table->index('created_at');
        });

        // Add foreign key to conversations table for last_message_id
        Schema::table('conversations', function (Blueprint $table) {
            $table->foreign('last_message_id')->references('id')->on('messages')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropForeign(['last_message_id']);
        });
        
        Schema::dropIfExists('messages');
    }
};
