<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lab Components - Asboblar kutubxonasi
     * 
     * Elektr komponentlari, o'lchov asboblari, mexanik qismlar
     */
    public function up(): void
    {
        Schema::create('lab_components', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Identifikatsiya
            $table->string('component_id', 50)->unique();
            // battery, resistor, pendulum_bob, lens_converging, stopwatch, etc.
            
            // Nomi (ko'p tilli)
            $table->string('name', 100);
            $table->string('name_uz', 100);
            $table->string('name_ru', 100)->nullable();
            
            // Tavsif
            $table->text('description')->nullable();
            $table->text('description_uz')->nullable();
            
            // Kategoriya
            $table->string('category', 50);
            /*
             * electrical_source    - Tok manbalari (batareya, generator)
             * electrical_passive   - Passiv elementlar (rezistor, kondensator)
             * electrical_output    - Chiqish (lampochka, motor)
             * electrical_control   - Boshqaruv (kalit, potentsiometr)
             * electrical_measure   - O'lchov (ampermetr, voltmetr)
             * mechanical_body      - Jismlar (sharcha, blok, prujina)
             * mechanical_support   - Tayanch (ip, tayanchlar)
             * optical_source       - Yorug'lik manbai
             * optical_element      - Optik elementlar (linza, ko'zgu)
             * optical_target       - Maqsad (ekran)
             * measurement_tool     - O'lchov asboblari (lineyka, sekundomer)
             * thermal_source       - Issiqlik manbai
             * thermal_container    - Idishlar
             */
            
            // Fizik xususiyatlar
            $table->json('properties');
            /*
             * {
             *   "voltage": {"type": "range", "min": 1.5, "max": 24, "default": 9, "unit": "V"},
             *   "resistance": {"type": "fixed", "value": 100, "unit": "Ω"},
             *   "mass": {"type": "range", "min": 0.1, "max": 2, "default": 0.5, "unit": "kg"}
             * }
             */
            
            // Vizual
            $table->string('icon', 100)->nullable();
            $table->text('svg_content')->nullable();
            $table->string('sprite_url', 500)->nullable();
            $table->unsignedTinyInteger('sprite_frames')->default(1);
            $table->string('color', 20)->nullable();
            
            // Simulyatsiya uchun fizika
            $table->string('physics_body_type', 30)->nullable();
            // static, dynamic, kinematic, sensor
            
            $table->json('physics_config')->nullable();
            /*
             * {
             *   "shape": "circle",
             *   "radius": 20,
             *   "mass": 0.5,
             *   "friction": 0.1,
             *   "restitution": 0.8,
             *   "collision_group": 1
             * }
             */
            
            // Ulanish nuqtalari
            $table->json('connection_points')->nullable();
            /*
             * [
             *   {"id": "positive", "position": {"x": 0, "y": -10}, "type": "electrical"},
             *   {"id": "negative", "position": {"x": 0, "y": 10}, "type": "electrical"}
             * ]
             */
            
            // Interaktivlik
            $table->boolean('is_draggable')->default(true);
            $table->boolean('is_rotatable')->default(false);
            $table->boolean('is_resizable')->default(false);
            $table->boolean('is_connectable')->default(true);
            $table->boolean('is_configurable')->default(true);
            
            // Audio
            $table->json('sounds')->nullable();
            /*
             * {"place": "click.mp3", "activate": "switch_on.mp3", "warning": "buzz.mp3"}
             */
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_premium')->default(false);
            
            // Tartib
            $table->unsignedSmallInteger('order_number')->default(0);
            
            $table->timestamps();
            
            // Indexes
            $table->index('component_id');
            $table->index('category');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_components');
    }
};
