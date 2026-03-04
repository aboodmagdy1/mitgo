<?php

namespace App\Filament\Resources\VehicleTypeResource\Pages;

use App\Filament\Resources\VehicleTypeResource;
use App\Models\VehicleType;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVehicleType extends EditRecord
{
    use EditRecord\Concerns\Translatable;
    protected static string $resource = VehicleTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [    
            Actions\DeleteAction::make()->disabled(function (VehicleType $record) {
                return $record->id == 1;
            }   ),
            Actions\LocaleSwitcher::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['defaultPricing'] = $this->record->defaultPricing?->only([
            'base_fare',
            'fare_per_km',
            'fare_per_minute',
            'cancellation_fee',
            'waiting_fee',
        ]);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['defaultPricing']);

        return $data;
    }

    protected function afterSave(): void
    {
        $defaultPricing = $this->data['defaultPricing'] ?? null;

        if ($defaultPricing) {
            $this->record->defaultPricing()->updateOrCreate(
                ['vehicle_type_id' => $this->record->id],
                $defaultPricing,
            );
        }
    }
}
