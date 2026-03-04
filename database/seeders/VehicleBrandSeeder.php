<?php

namespace Database\Seeders;

use App\Models\VehicleBrand;
use Illuminate\Database\Seeder;

class VehicleBrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vehicleBrands = [
            [
                'name' => [
                    'en' => 'Toyota',
                    'ar' => 'تويوتا'
                ],
                'active' => true,
            ],
            [
                'name' => [
                    'en' => 'Hyundai',
                    'ar' => 'هيونداي'
                ],
                'active' => true,
            ],
            [
                'name' => [
                    'en' => 'Nissan',
                    'ar' => 'نيسان'
                ],
                'active' => true,
            ],
            [
                'name' => [
                    'en' => 'Honda',
                    'ar' => 'هوندا'
                ],
                'active' => true,
            ],
            [
                'name' => [
                    'en' => 'Chevrolet',
                    'ar' => 'شيفروليه'
                ],
                'active' => true,
            ],
            [
                'name' => [
                    'en' => 'Ford',
                    'ar' => 'فورد'
                ],
                'active' => true,
            ],
            [
                'name' => [
                    'en' => 'Kia',
                    'ar' => 'كيا'
                ],
                'active' => true,
            ],
            [
                'name' => [
                    'en' => 'Mitsubishi',
                    'ar' => 'ميتسوبيشي'
                ],
                'active' => true,
            ],
            [
                'name' => [
                    'en' => 'Mercedes-Benz',
                    'ar' => 'مرسيدس بنز'
                ],
                'active' => true,
            ],
            [
                'name' => [
                    'en' => 'BMW',
                    'ar' => 'بي ام دبليو'
                ],
                'active' => true,
            ],
            [
                'name' => [
                    'en' => 'Lexus',
                    'ar' => 'لكزس'
                ],
                'active' => true,
            ],
            [
                'name' => [
                    'en' => 'Audi',
                    'ar' => 'أودي'
                ],
                'active' => true,
            ],
        ];

        foreach ($vehicleBrands as $vehicleBrand) {
            $brand = VehicleBrand::create($vehicleBrand);
            // Attach a local default icon
            $brand->addMedia(public_path('images/car.png'))
                ->preservingOriginal()
                ->toMediaCollection('icon');
        }
    }
}
