<?php

namespace App\Enums;

enum TripStatus: int
{
    Case SEARCHING = 1;
    Case RIDER_NO_SHOW  = 2;
    Case NO_DRIVER_FOUND = 3;
    Case IN_ROUTE_TO_PICKUP = 4;
    Case PICKUP_ARRIVED = 5;
    Case RIDER_NOT_FOUND = 6;
    Case IN_PROGRESS = 7;

    Case COMPLETED_PENDING_PAYMENT = 8;
    Case PAYMENT_FAILED = 9;
    Case PAID = 10;
    Case CANCELLED_BY_DRIVER = 11;
    Case CANCELLED_BY_RIDER = 12;
    Case CANCELLED_BY_SYSTEM = 13;

    Case TRIP_EXPIRED = 14;

    Case SCHEDULED = 15;
    Case COMPLETED = 16;

    public function label(?string $locale = null): string
    {
        $currentLocale = app()->getLocale();
        $targetLocale = $locale ?? $currentLocale;
        
        // Set locale temporarily if different from current
        if ($targetLocale !== $currentLocale) {
            app()->setLocale($targetLocale);
        }
        
        $label = match($this) {
            self::SEARCHING => __('Searching'),
            self::RIDER_NO_SHOW => __('Rider No Show'),
            self::NO_DRIVER_FOUND => __('No Driver Found'),
            self::IN_ROUTE_TO_PICKUP => __('In Route to Pickup'),
            self::PICKUP_ARRIVED => __('Pickup Arrived'),
            self::RIDER_NOT_FOUND => __('Rider Not Found'),
            self::IN_PROGRESS => __('In Progress'),
            self::COMPLETED_PENDING_PAYMENT => __('Completed Pending Payment'),
            self::PAYMENT_FAILED => __('Payment Failed'),
            self::PAID => __('Paid'),
            self::CANCELLED_BY_DRIVER => __('Cancelled by Driver'),
            self::CANCELLED_BY_RIDER => __('Cancelled by Rider'),
            self::CANCELLED_BY_SYSTEM => __('Cancelled by System'),
            self::TRIP_EXPIRED => __('Trip Expired'),
            self::SCHEDULED => __('Scheduled'),
            self::COMPLETED => __('Completed'),
        };
        
        // Reset locale if it was changed
        if ($targetLocale !== $currentLocale) {
            app()->setLocale($currentLocale);
        }
        
        return $label;
    }

    public static function getValues(){
        // return the values with the label
        return array_map(function($status){
            return (object) [
                'id' => $status->value,
                'name' => in_array($status, [self::CANCELLED_BY_DRIVER, self::CANCELLED_BY_RIDER, self::CANCELLED_BY_SYSTEM], true)
                    ? __('Cancelled')
                    : $status->label(),
            ];
        }, self::cases());

    }


}
