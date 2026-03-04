<?php

namespace Modules\Chat\Enums;

enum MessageType: int
{
    case Text = 1;
    case File = 2;
    case Offer = 3;

    public function label(): string
    {
        return match($this){
            self::Text => __('Text'),
            self::File => __('File'),
            self::Offer => __('Offer'),
        };
    }
    public function value(): int{
        return $this->value;
    }
    public static function values(){
        return array_column(self::cases(), 'value');
    }
}