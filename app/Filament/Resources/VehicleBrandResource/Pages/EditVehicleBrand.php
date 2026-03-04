<?php

namespace App\Filament\Resources\VehicleBrandResource\Pages;

use App\Filament\Resources\VehicleBrandResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVehicleBrand extends EditRecord
{
    use EditRecord\Concerns\Translatable;
    
    protected static string $resource = VehicleBrandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
