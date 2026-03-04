<?php

namespace App\Filament\Resources\VehicleBrandModelResource\Pages;

use App\Filament\Resources\VehicleBrandModelResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateVehicleBrandModel extends CreateRecord
{
    use CreateRecord\Concerns\Translatable;
    
    protected static string $resource = VehicleBrandModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
        ];
    }
}
