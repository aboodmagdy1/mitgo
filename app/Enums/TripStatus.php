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
        return match($this) {
            self::SEARCHING => 'البحث',
            self::RIDER_NO_SHOW => 'عدم حضور الراكب',
            self::NO_DRIVER_FOUND => 'لم يتم العثور على سائق',
            self::IN_ROUTE_TO_PICKUP => 'في الطريق لنقطة الانطلاق',
            self::PICKUP_ARRIVED => 'وصل إلى نقطة الانطلاق',
            self::RIDER_NOT_FOUND => 'لم يتم العثور على الراكب',
            self::IN_PROGRESS => 'قيد التنفيذ',
            self::COMPLETED_PENDING_PAYMENT => 'مكتمل في انتظار الدفع',
            self::PAYMENT_FAILED => 'فشل الدفع',
            self::PAID => 'مدفوع',
            self::CANCELLED_BY_DRIVER => 'ملغي من قبل السائق',
            self::CANCELLED_BY_RIDER => 'ملغي من قبل الراكب',
            self::CANCELLED_BY_SYSTEM => 'ملغي من قبل النظام',
            self::TRIP_EXPIRED => 'انتهت صلاحية الرحلة',
            self::SCHEDULED => 'مجدولة',
            self::COMPLETED => 'مكتملة',
        };
    }

    public static function getValues(){
        // return the values with the label
        return array_map(function($status){
            return (object) [
                'id' => $status->value,
                'name' => in_array($status, [self::CANCELLED_BY_DRIVER, self::CANCELLED_BY_RIDER, self::CANCELLED_BY_SYSTEM], true)
                    ? 'ملغية'
                    : $status->label(),
            ];
        }, self::cases());

    }


}
