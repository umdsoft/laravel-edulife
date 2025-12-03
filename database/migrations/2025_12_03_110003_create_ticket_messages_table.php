<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            
            $table->text('content');
            $table->boolean('is_from_support')->default(false);
            $table->boolean('is_internal_note')->default(false); // Only visible to support
            
            // Attachments
            $table->json('attachments')->nullable();
            
            $table->boolean('is_read')->default(false);
            $table->dateTime('read_at')->nullable();
            
            $table->timestamps();
            
            $table->index(['ticket_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_messages');
    }
};
