<?php

namespace App\Filament\Resources\ZonePricingModifierResource\Pages;

use App\Filament\Resources\ZonePricingModifierResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListZonePricingModifiers extends ListRecords
{
    protected static string $resource = ZonePricingModifierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
