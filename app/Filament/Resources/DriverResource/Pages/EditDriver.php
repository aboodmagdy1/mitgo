<?php

namespace App\Filament\Resources\DriverResource\Pages;

use App\Filament\Resources\DriverResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDriver extends EditRecord
{
    protected static string $resource = DriverResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load the driver with user and vehicle relationships
        $driver = $this->record->load(['user', 'user.city', 'vehicle']);
        
        // Populate user information
        if ($driver->user) {
            $data['user'] = [
                'name' => $driver->user->name,
                'email' => $driver->user->email,
                'phone' => $driver->user->phone,
                'city_id' => $driver->user->city_id,
                'is_active' => $driver->user->is_active,
            ];
        }

        // If driver has a vehicle, populate vehicle data
        if ($driver->vehicle) {
            $vehicle = $driver->vehicle;
            
            $data['vehicle'] = [
                'color' => $vehicle->color,
                'license_number' => $vehicle->license_number,
                'plate_number' => $vehicle->plate_number,
                'vehicle_type_id' => $vehicle->vehicle_type_id,
                'vehicle_brand_model_id' => $vehicle->vehicle_brand_model_id,
                'seats' => $vehicle->seats,
            ];

            // If vehicle has a brand model, populate the vehicle_brand_id
            if ($vehicle->vehicleBrandModel) {
                $data['vehicle']['vehicle_brand_id'] = $vehicle->vehicleBrandModel->vehicle_brand_id;
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Remove vehicle_brand_id from data as it's not a database field
        // It's only used for the reactive dropdown functionality
        if (isset($data['vehicle']['vehicle_brand_id'])) {
            unset($data['vehicle']['vehicle_brand_id']);
        }

        return $data;
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        // Update the user information
        if (isset($data['user'])) {
            $record->user->update($data['user']);
            unset($data['user']);
        }

        // Update the vehicle information
        if (isset($data['vehicle'])) {
            if ($record->vehicle) {
                $record->vehicle->update($data['vehicle']);
            } else {
                $record->vehicle()->create($data['vehicle']);
            }
            unset($data['vehicle']);
        }

        // Update the driver information
        $record->update($data);

        return $record;
    }
}
