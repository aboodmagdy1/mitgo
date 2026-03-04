<?php

namespace App\Filament\Resources\VehicleBrandModelResource\Pages;

use App\Filament\Resources\VehicleBrandModelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVehicleBrandModel extends EditRecord
{
    use EditRecord\Concerns\Translatable;
    
    protected static string $resource = VehicleBrandModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
