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
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('phone', 20);
            $table->string('code', 6);
            $table->enum('purpose', ['registration', 'login', 'password_reset', 'phone_change']);
            $table->integer('attempts_left')->default(3);
            $table->timestamp('expires_at');
            $table->timestamps();
            
            $table->index(['phone', 'purpose', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otp_codes');
    }
};
