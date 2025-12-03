<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            
            // Ticket info
            $table->string('ticket_number')->unique(); // TICKET-2024-XXXXX
            $table->string('subject');
            $table->text('description');
            $table->enum('category', ['technical', 'payment', 'course', 'account', 'other'])->default('other');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            
            // Status
            $table->enum('status', ['open', 'in_progress', 'waiting_user', 'resolved', 'closed'])->default('open');
            
            // Assignment
            $table->foreignUuid('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            
            // Timing
            $table->dateTime('first_response_at')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            
            // Rating
            $table->unsignedTinyInteger('satisfaction_rating')->nullable(); // 1-5
            $table->text('feedback')->nullable();
            
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index(['status', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
