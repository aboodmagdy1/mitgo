<?php

namespace App\Filament\Resources\VehicleBrandModelResource\Pages;

use App\Filament\Resources\VehicleBrandModelResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVehicleBrandModels extends ListRecords
{
    use ListRecords\Concerns\Translatable;
    
    protected static string $resource = VehicleBrandModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
            Actions\CreateAction::make(),
        ];
    }
}
