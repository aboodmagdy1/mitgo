<?php

namespace App\Filament\Resources\VehicleTypeResource\Pages;

use App\Filament\Resources\VehicleTypeResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Actions;

class CreateVehicleType extends CreateRecord
{
    use CreateRecord\Concerns\Translatable;
    protected static string $resource = VehicleTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['defaultPricing']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $defaultPricing = $this->data['defaultPricing'] ?? null;

        if ($defaultPricing) {
            $this->record->defaultPricing()->create($defaultPricing);
        }
    }
}
