<?php

namespace App\Enums;

enum CancelTripReasonType: int
{
    case Rider = 1;
    case Driver = 2;

    public function label(): string
    {
        return match ($this) {
            self::Rider => __('Rider'),
            self::Driver => __('Driver'),
        };
    }

    public function value(): int
    {
        return $this->value;
    }

    public function values(){
        return array_column(self::cases(), 'value');
    }
}
