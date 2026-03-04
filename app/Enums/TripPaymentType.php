<?php

namespace App\Enums;

enum TripPaymentType: int
{
    Case CASH = 1;
    Case CARD = 2;

    public function label(): string
    {
        return match($this){
            self::CASH => __('Cash'),
            self::CARD => __('Card'),
        };
    }
}
