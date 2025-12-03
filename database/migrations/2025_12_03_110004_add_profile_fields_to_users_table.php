<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('bio')->nullable()->after('email');
            $table->string('username')->unique()->nullable()->after('bio');
            $table->string('location')->nullable();
            $table->string('website')->nullable();
            $table->json('social_links')->nullable(); // {"twitter": "...", "linkedin": "...", "github": "..."}
            
            // Privacy settings
            $table->boolean('is_profile_public')->default(true);
            $table->boolean('show_activity')->default(true);
            $table->boolean('show_courses')->default(true);
            $table->boolean('show_achievements')->default(true);
            $table->boolean('show_stats')->default(true);
            
            // Preferences
            $table->string('timezone')->default('Asia/Tashkent');
            $table->string('language')->default('uz');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'bio', 'username', 'location', 'website', 'social_links',
                'is_profile_public', 'show_activity', 'show_courses', 
                'show_achievements', 'show_stats',
                'timezone', 'language',
            ]);
        });
    }
};
