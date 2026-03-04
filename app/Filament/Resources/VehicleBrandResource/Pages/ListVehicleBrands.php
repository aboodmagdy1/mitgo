<?php

namespace App\Filament\Resources\VehicleBrandResource\Pages;

use App\Filament\Resources\VehicleBrandResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVehicleBrands extends ListRecords
{
    use ListRecords\Concerns\Translatable;
    
    protected static string $resource = VehicleBrandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
            Actions\CreateAction::make(),
        ];
    }
}
