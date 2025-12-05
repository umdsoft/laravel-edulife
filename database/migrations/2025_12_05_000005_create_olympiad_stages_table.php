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
        Schema::create('olympiad_stages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 50)->unique();
            // Values: 'school', 'district', 'region', 'national'
            $table->string('display_name', 100);
            $table->string('display_name_uz', 100);
            $table->integer('order_level')->unique();
            // 1 = school, 2 = district, 3 = region, 4 = national
            $table->uuid('next_stage_id')->nullable();
            $table->json('advancement_config')->nullable();
            /*
             * {
             *   "top_n": null,
             *   "top_percent": 20,
             *   "min_score_percent": 60,
             *   "auto_register": true
             * }
             */
            $table->json('reward_config')->nullable();
            /*
             * {
             *   "1": {"discount_percent": 60, "bonus_coins": 500, "badge": true},
             *   "2": {"discount_percent": 50, "bonus_coins": 300, "badge": true},
             *   "3": {"discount_percent": 40, "bonus_coins": 200, "badge": true},
             *   "4-10": {"discount_percent": 20, "bonus_coins": 100},
             *   "11+": {"bonus_coins_percent": 10}
             * }
             */
            $table->string('icon', 50)->nullable();
            $table->string('color', 20)->nullable();
            $table->timestamps();
            
            $table->foreign('next_stage_id')
                  ->references('id')
                  ->on('olympiad_stages')
                  ->onDelete('set null');
                  
            $table->index('order_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('olympiad_stages');
    }
};
