<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // MySQL requires dropping and recreating enum to add values
        DB::statement("ALTER TABLE english_battles MODIFY COLUMN battle_type ENUM('ranked', 'friendly', 'practice', 'tournament', 'quick', 'casual') DEFAULT 'ranked'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE english_battles MODIFY COLUMN battle_type ENUM('ranked', 'friendly', 'practice', 'tournament') DEFAULT 'ranked'");
    }
};
