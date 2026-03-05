<?php

namespace App\Filament\Resources\FemaleDriverResource\Pages;

use App\Filament\Resources\FemaleDriverResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditFemaleDriver extends EditRecord
{
    protected static string $resource = FemaleDriverResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $driver = $this->record->load(['user', 'user.city', 'vehicle', 'vehicle.vehicleBrandModel']);

        if ($driver->user) {
            $data['user'] = [
                'name'      => $driver->user->name,
                'email'     => $driver->user->email,
                'phone'     => $driver->user->phone,
                'city_id'   => $driver->user->city_id,
                'is_active' => $driver->user->is_active,
            ];
        }

        if ($driver->vehicle) {
            $vehicle = $driver->vehicle;
            $data['vehicle'] = [
                'color'                  => $vehicle->color,
                'license_number'         => $vehicle->license_number,
                'plate_number'           => $vehicle->plate_number,
                'vehicle_type_id'        => $vehicle->vehicle_type_id,
                'vehicle_brand_model_id' => $vehicle->vehicle_brand_model_id,
                'seats'                  => $vehicle->seats,
            ];

            if ($vehicle->vehicleBrandModel) {
                $data['vehicle']['vehicle_brand_id'] = $vehicle->vehicleBrandModel->vehicle_brand_id;
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['vehicle']['vehicle_brand_id'])) {
            unset($data['vehicle']['vehicle_brand_id']);
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (isset($data['user'])) {
            $record->user->update($data['user']);
            unset($data['user']);
        }

        if (isset($data['vehicle'])) {
            if ($record->vehicle) {
                $record->vehicle->update($data['vehicle']);
            } else {
                $record->vehicle()->create($data['vehicle']);
            }
            unset($data['vehicle']);
        }

        $record->update($data);

        return $record;
    }
}
