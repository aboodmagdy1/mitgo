<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Country;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
            $cities= [
                ['name'=> [
                    'en' => 'Jeddah',
                    'ar' => 'جدة',
                ]
            ],
            [
                'name'=> [
                    'en' => 'Riyadh',
                    'ar' => 'الرياض',
                ]
            ],
            [
                'name'=> [
                    'en' => 'Mecca',
                    'ar' => 'مكة',
                ]
            ],
            ];

            foreach ($cities as $city) {
                City::create($city);
            }
    }
}
