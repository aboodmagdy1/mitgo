<?php

namespace App\Enums;

enum TripType: int
{
    Case immediate = 1;
    Case scheduled = 2;

    public function label(): string
    {
        return match($this){
            self::immediate => __('Immediate'),
            self::scheduled => __('Scheduled'),
        };
    }
}
