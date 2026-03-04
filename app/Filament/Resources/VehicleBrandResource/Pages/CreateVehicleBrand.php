<?php

namespace App\Filament\Resources\VehicleBrandResource\Pages;

use App\Filament\Resources\VehicleBrandResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateVehicleBrand extends CreateRecord
{
    use CreateRecord\Concerns\Translatable;
    
    protected static string $resource = VehicleBrandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
        ];
    }
}
