<?php

namespace Database\Seeders;

use App\Enums\CancelTripReasonType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CancelTripReasonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //driver reasons
        $driverReasons = [
            [
                'reason' => [
                    'ar' => "مشكلة في السيارة",
                    'en' => "Car problem"
                ],
                'type' => CancelTripReasonType::Driver
            ],
            [
                'reason' => [
                    'ar' => "العميل طلب الإلغاء",
                    'en' => "Customer requested cancellation"
                ],
                'type' => CancelTripReasonType::Driver
            ],
            [
                'reason' => [
                    'ar' => "مشكلة في موقع العميل",
                    'en' => "Problem with customer's location"
                ],
                'type' => CancelTripReasonType::Driver
            ],
            [
                'reason' => [
                    'ar' => "مشكلة بالطريق",
                    'en' => "Problem on the way"
                ],
                'type' => CancelTripReasonType::Driver
            ],
            [
                'reason' => [
                    'ar' => "الطريق مزدحم",
                    'en' => "The road is crowded"
                ],
                'type' => CancelTripReasonType::Driver
            ],
            [
                'reason' => [
                    'ar' => "كنت أقوم بتجربة التطبيق",
                    'en' => "I was testing the app"
                ],
                'type' => CancelTripReasonType::Driver
            ],
            [
                'reason' => [
                    'ar' => "لا يعجبني أسلوب العميل",
                    'en' => "I don't like the customer's attitude"
                ],
                'type' => CancelTripReasonType::Driver
            ],
            [
                'reason' => [
                    'ar' => "اخرى",
                    'en' => "Other"
                ],
                'type' => CancelTripReasonType::Driver
            ]
        ]; 

        //rider reasons
        $riderReasons = [
            [
                'reason' => [
                    'ar' => "لم أعد بحاجة للرحلة",
                    'en' => "I no longer need the trip"
                ],
                'type' => CancelTripReasonType::Rider
            ],
            [
                'reason' => [
                    'ar' => "السعر غير مناسب",
                    'en' => "The price is not suitable"
                ],
                'type' => CancelTripReasonType::Rider
            ],
            [
                'reason' => [
                    'ar' => "أرغب في تعديل الوجهة",
                    'en' => "I want to change the destination"
                ],
                'type' => CancelTripReasonType::Rider
            ],
            [
                'reason' => [
                    'ar' => "طلب مني الكابتن الإلغاء",
                    'en' => "The captain asked me to cancel"
                ],
                'type' => CancelTripReasonType::Rider
            ],
            [
                'reason' => [
                    'ar' => "سأل الكابتن عن معلومات شخصية",
                    'en' => "The captain asked for personal information"
                ],
                'type' => CancelTripReasonType::Rider
            ],
            [
                'reason' => [
                    'ar' => "كنت أقوم بتجربة التطبيق",
                    'en' => "I was testing the app"
                ],
                'type' => CancelTripReasonType::Rider
            ],
            [
                'reason' => [
                    'ar' => "لا تعجبني السيارة",
                    'en' => "I don't like the car"
                ],
                'type' => CancelTripReasonType::Rider
            ],
            [
                'reason' => [
                    'ar' => "لا يعجبني أسلوب الكابتن",
                    'en' => "I don't like the captain's style"
                ],
                'type' => CancelTripReasonType::Rider
            ]
        ]; 

        // Insert driver reasons
        foreach ($driverReasons as $reason) {
            \App\Models\CancelTripReason::create($reason);
        }

        // Insert rider reasons
        foreach ($riderReasons as $reason) {
            \App\Models\CancelTripReason::create($reason);
        }
    }
}
