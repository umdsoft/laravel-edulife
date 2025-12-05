<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RegionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $regions = [
            ['code' => 'TAS', 'name' => 'Tashkent City', 'name_uz' => 'Toshkent shahri'],
            ['code' => 'TOS', 'name' => 'Tashkent Region', 'name_uz' => 'Toshkent viloyati'],
            ['code' => 'AND', 'name' => 'Andijan Region', 'name_uz' => 'Andijon viloyati'],
            ['code' => 'BUX', 'name' => 'Bukhara Region', 'name_uz' => 'Buxoro viloyati'],
            ['code' => 'JIZ', 'name' => 'Jizzakh Region', 'name_uz' => 'Jizzax viloyati'],
            ['code' => 'QQR', 'name' => 'Karakalpakstan', 'name_uz' => 'Qoraqalpog\'iston Respublikasi'],
            ['code' => 'QAS', 'name' => 'Kashkadarya Region', 'name_uz' => 'Qashqadaryo viloyati'],
            ['code' => 'NAV', 'name' => 'Navoi Region', 'name_uz' => 'Navoiy viloyati'],
            ['code' => 'NAM', 'name' => 'Namangan Region', 'name_uz' => 'Namangan viloyati'],
            ['code' => 'SAM', 'name' => 'Samarkand Region', 'name_uz' => 'Samarqand viloyati'],
            ['code' => 'SUR', 'name' => 'Surkhandarya Region', 'name_uz' => 'Surxondaryo viloyati'],
            ['code' => 'SIR', 'name' => 'Syrdarya Region', 'name_uz' => 'Sirdaryo viloyati'],
            ['code' => 'FAR', 'name' => 'Fergana Region', 'name_uz' => 'Farg\'ona viloyati'],
            ['code' => 'XOR', 'name' => 'Khorezm Region', 'name_uz' => 'Xorazm viloyati'],
        ];

        foreach ($regions as $regionData) {
            Region::updateOrCreate(
                ['code' => $regionData['code']],
                array_merge($regionData, ['is_active' => true])
            );
        }
    }
}
