<?php

namespace Database\Seeders;

use App\Models\VehicleBrand;
use App\Models\VehicleBrandModel;
use Illuminate\Database\Seeder;

class VehicleBrandModelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get vehicle brands for reference
        $toyota = VehicleBrand::where('name->en', 'Toyota')->first();
        $hyundai = VehicleBrand::where('name->en', 'Hyundai')->first();
        $nissan = VehicleBrand::where('name->en', 'Nissan')->first();
        $honda = VehicleBrand::where('name->en', 'Honda')->first();
        $chevrolet = VehicleBrand::where('name->en', 'Chevrolet')->first();
        $ford = VehicleBrand::where('name->en', 'Ford')->first();
        $kia = VehicleBrand::where('name->en', 'Kia')->first();
        $mitsubishi = VehicleBrand::where('name->en', 'Mitsubishi')->first();
        $mercedes = VehicleBrand::where('name->en', 'Mercedes-Benz')->first();
        $bmw = VehicleBrand::where('name->en', 'BMW')->first();
        $lexus = VehicleBrand::where('name->en', 'Lexus')->first();
        $audi = VehicleBrand::where('name->en', 'Audi')->first();

        $vehicleModels = [];

        // Toyota Models
        if ($toyota) {
            $vehicleModels = array_merge($vehicleModels, [
                [
                    'vehicle_brand_id' => $toyota->id,
                    'name' => ['en' => 'Camry', 'ar' => 'كامري'],
                    'active' => true,
                ],
                [
                    'vehicle_brand_id' => $toyota->id,
                    'name' => ['en' => 'Corolla', 'ar' => 'كورولا'],
                    'active' => true,
                ],
                [
                    'vehicle_brand_id' => $toyota->id,
                    'name' => ['en' => 'Avalon', 'ar' => 'أفالون'],
                    'active' => true,
                ],
                [
                    'vehicle_brand_id' => $toyota->id,
                    'name' => ['en' => 'RAV4', 'ar' => 'راف 4'],
                    'active' => true,
                ],
                [
                    'vehicle_brand_id' => $toyota->id,
                    'name' => ['en' => 'Highlander', 'ar' => 'هايلاندر'],
                    'active' => true,
                ],
                [
                    'vehicle_brand_id' => $toyota->id,
                    'name' => ['en' => 'Prius', 'ar' => 'بريوس'],
                    'active' => true,
                ],
            ]);
        }

        // Hyundai Models
        if ($hyundai) {
            $vehicleModels = array_merge($vehicleModels, [
                [
                    'vehicle_brand_id' => $hyundai->id,
                    'name' => ['en' => 'Elantra', 'ar' => 'إلانترا'],
                    'active' => true,
                ],
                [
                    'vehicle_brand_id' => $hyundai->id,
                    'name' => ['en' => 'Sonata', 'ar' => 'سوناتا'],
                    'active' => true,
                ],
                [
                    'vehicle_brand_id' => $hyundai->id,
                    'name' => ['en' => 'Tucson', 'ar' => 'توكسون'],
                    'active' => true,
                ],
                [
                    'vehicle_brand_id' => $hyundai->id,
                    'name' => ['en' => 'Santa Fe', 'ar' => 'سانتا في'],
                    'active' => true,
                ],
                [
                    'vehicle_brand_id' => $hyundai->id,
                    'name' => ['en' => 'Accent', 'ar' => 'أكسنت'],
                    'active' => true,
                ],
            ]);
        }

        // Nissan Models
        if ($nissan) {
            $vehicleModels = array_merge($vehicleModels, [
                [
                    'vehicle_brand_id' => $nissan->id,
                    'name' => ['en' => 'Altima', 'ar' => 'التيما'],
                    'active' => true,
                ],
                [
                    'vehicle_brand_id' => $nissan->id,
                    'name' => ['en' => 'Sentra', 'ar' => 'سنترا'],
                    'active' => true,
                ],
                [
                    'vehicle_brand_id' => $nissan->id,
                    'name' => ['en' => 'Maxima', 'ar' => 'ماكسيما'],
                    'active' => true,
                ],
                [
                    'vehicle_brand_id' => $nissan->id,
                    'name' => ['en' => 'Patrol', 'ar' => 'باترول'],
                    'active' => true,
                ],
                [
                    'vehicle_brand_id' => $nissan->id,
                    'name' => ['en' => 'X-Trail', 'ar' => 'اكس تريل'],
                    'active' => true,
                ],
            ]);
        }

        // Honda Models
        if ($honda) {
            $vehicleModels = array_merge($vehicleModels, [
                [
                    'vehicle_brand_id' => $honda->id,
                    'name' => ['en' => 'Civic', 'ar' => 'سيفيك'],
                    'active' => true,
                ],
                [
                    'vehicle_brand_id' => $honda->id,
                    'name' => ['en' => 'Accord', 'ar' => 'أكورد'],
                    'active' => true,
                ],
                [
                    'vehicle_brand_id' => $honda->id,
                    'name' => ['en' => 'CR-V', 'ar' => 'سي آر في'],
                    'active' => true,
                ],
                [
                    'vehicle_brand_id' => $honda->id,
                    'name' => ['en' => 'Pilot', 'ar' => 'بايلوت'],
                    'active' => true,
                ],
            ]);
        }

        // Chevrolet Models
        if ($chevrolet) {
            $vehicleModels = array_merge($vehicleModels, [
                [
                    'vehicle_brand_id' => $chevrolet->id,
                    'name' => ['en' => 'Malibu', 'ar' => 'ماليبو'],
                    'active' => true,
                ],
                [
                    'vehicle_brand_id' => $chevrolet->id,
                    'name' => ['en' => 'Cruze', 'ar' => 'كروز'],
                    'active' => true,
                ],
                [
                    'vehicle_brand_id' => $chevrolet->id,
                    'name' => ['en' => 'Tahoe', 'ar' => 'تاهو'],
                    'active' => true,
                ],
                [
                    'vehicle_brand_id' => $chevrolet->id,
                    'name' => ['en' => 'Suburban', 'ar' => 'سوبربان'],
                    'active' => true,
                ],
            ]);
        }

        // Ford Models
        if ($ford) {
            $vehicleModels = array_merge($vehicleModels, [
                [
                    'vehicle_brand_id' => $ford->id,
                    'name' => ['en' => 'Fusion', 'ar' => 'فيوجن'],
                    'active' => true,
                ],
                [
                    'vehicle_brand_id' => $ford->id,
                    'name' => ['en' => 'Explorer', 'ar' => 'اكسبلورر'],
                    'active' => true,
                ],
                [
                    'vehicle_brand_id' => $ford->id,
                    'name' => ['en' => 'Escape', 'ar' => 'اسكيب'],
                    'active' => true,
                ],
                [
                    'vehicle_brand_id' => $ford->id,
                    'name' => ['en' => 'Expedition', 'ar' => 'اكسبديشن'],
                    'active' => true,
                ],
            ]);
        }

        // Kia Models
        if ($kia) {
            $vehicleModels = array_merge($vehicleModels, [
                [
                    'vehicle_brand_id' => $kia->id,
                    'name' => ['en' => 'Optima', 'ar' => 'أوبتيما'],
                    'active' => true,
                ],
                [
                    'vehicle_brand_id' => $kia->id,
                    'name' => ['en' => 'Forte', 'ar' => 'فورتي'],
                    'active' => true,
                ],
                [
                    'vehicle_brand_id' => $kia->id,
                    'name' => ['en' => 'Sorento', 'ar' => 'سورنتو'],
                    'active' => true,
                ],
                [
                    'vehicle_brand_id' => $kia->id,
                    'name' => ['en' => 'Sportage', 'ar' => 'سبورتاج'],
                    'active' => true,
                ],
            ]);
        }

        // Mercedes Models
        if ($mercedes) {
            $vehicleModels = array_merge($vehicleModels, [
                [
                    'vehicle_brand_id' => $mercedes->id,
                    'name' => ['en' => 'C-Class', 'ar' => 'الفئة سي'],
                    'active' => true,
                ],
                [
                    'vehicle_brand_id' => $mercedes->id,
                    'name' => ['en' => 'E-Class', 'ar' => 'الفئة إي'],
                    'active' => true,
                ],
                [
                    'vehicle_brand_id' => $mercedes->id,
                    'name' => ['en' => 'S-Class', 'ar' => 'الفئة إس'],
                    'active' => true,
                ],
                [
                    'vehicle_brand_id' => $mercedes->id,
                    'name' => ['en' => 'GLC', 'ar' => 'جي إل سي'],
                    'active' => true,
                ],
            ]);
        }

        // BMW Models
        if ($bmw) {
            $vehicleModels = array_merge($vehicleModels, [
                [
                    'vehicle_brand_id' => $bmw->id,
                    'name' => ['en' => '3 Series', 'ar' => 'السلسلة الثالثة'],
                    'active' => true,
                ],
                [
                    'vehicle_brand_id' => $bmw->id,
                    'name' => ['en' => '5 Series', 'ar' => 'السلسلة الخامسة'],
                    'active' => true,
                ],
                [
                    'vehicle_brand_id' => $bmw->id,
                    'name' => ['en' => '7 Series', 'ar' => 'السلسلة السابعة'],
                    'active' => true,
                ],
                [
                    'vehicle_brand_id' => $bmw->id,
                    'name' => ['en' => 'X3', 'ar' => 'اكس 3'],
                    'active' => true,
                ],
                [
                    'vehicle_brand_id' => $bmw->id,
                    'name' => ['en' => 'X5', 'ar' => 'اكس 5'],
                    'active' => true,
                ],
            ]);
        }

        // Lexus Models
        if ($lexus) {
            $vehicleModels = array_merge($vehicleModels, [
                [
                    'vehicle_brand_id' => $lexus->id,
                    'name' => ['en' => 'ES', 'ar' => 'إي إس'],
                    'active' => true,
                ],
                [
                    'vehicle_brand_id' => $lexus->id,
                    'name' => ['en' => 'LS', 'ar' => 'إل إس'],
                    'active' => true,
                ],
                [
                    'vehicle_brand_id' => $lexus->id,
                    'name' => ['en' => 'RX', 'ar' => 'آر إكس'],
                    'active' => true,
                ],
                [
                    'vehicle_brand_id' => $lexus->id,
                    'name' => ['en' => 'LX', 'ar' => 'إل إكس'],
                    'active' => true,
                ],
            ]);
        }

        // Audi Models
        if ($audi) {
            $vehicleModels = array_merge($vehicleModels, [
                [
                    'vehicle_brand_id' => $audi->id,
                    'name' => ['en' => 'A3', 'ar' => 'إيه 3'],
                    'active' => true,
                ],
                [
                    'vehicle_brand_id' => $audi->id,
                    'name' => ['en' => 'A4', 'ar' => 'إيه 4'],
                    'active' => true,
                ],
                [
                    'vehicle_brand_id' => $audi->id,
                    'name' => ['en' => 'A6', 'ar' => 'إيه 6'],
                    'active' => true,
                ],
                [
                    'vehicle_brand_id' => $audi->id,
                    'name' => ['en' => 'Q5', 'ar' => 'كيو 5'],
                    'active' => true,
                ],
                [
                    'vehicle_brand_id' => $audi->id,
                    'name' => ['en' => 'Q7', 'ar' => 'كيو 7'],
                    'active' => true,
                ],
            ]);
        }

        foreach ($vehicleModels as $vehicleModel) {
           $vehicleModel = VehicleBrandModel::create($vehicleModel);
             $vehicleModel->addMedia(public_path('images/car.png'))
                ->preservingOriginal()
                ->toMediaCollection('icon');
        }
    }
}
