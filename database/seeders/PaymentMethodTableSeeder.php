<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PaymentMethod;

class PaymentMethodTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
        $methods = [
            [
            'name' => [
                        'ar' => 'الدفع نقداً',
                        'en' => 'Cash on delivery',
                    ],
                    'image' => 'cach.png',
            ],
            [
                'name' => [
                    'ar' => 'محفطتي',
                    'en' => 'My wallet',
                ],
                'image' => 'wallet.png',
            ],
            [
                'name' => [
                    'ar' => 'فيزا',
                    'en' => 'Visa',
                ],
                'image' => 'visa.png',
            ],
            
            [
                'name' => [
                    'ar' => 'ابل باي',
                    'en' => 'Apple pay',
                ],
                'image' => 'apple-pay.png',
            ],
            [
                'name' => [
                    'ar' => 'مدى',
                    'en' => 'Mada',
                ],
                'image' => 'mada.png',
            ],
            
            [
                'name' => [
                    'ar' => 'تابي',
                    'en' => 'Tabby',
                ],
                'image' => 'tabby.png',
            ],
            [
                'name' => [
                    'ar' => 'تمارا',
                    'en' => 'Tamara',
                ],
                'image' => 'tamara.png',
            ],
        ];

        foreach ($methods as $methodData) {
           $paymentMethod = PaymentMethod::create([
            'name' => $methodData['name'],
           ]);
           $paymentMethod->addMedia(public_path('images/payment_methods/' . $methodData['image']))
               ->preservingOriginal()
               ->toMediaCollection('image');
        }
    }
}
