<?php

namespace App\Filament\Resources\DriverResource\Pages;

use App\Filament\Resources\DriverResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class CreateDriver extends CreateRecord
{
    protected static string $resource = DriverResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate default password if not provided
        if (empty($data['password'])) {
            $data['password'] = bcrypt('12345678'); // Default password
        }

        // Remove vehicle_type_id from data as it's not a database field
        // It's only used for the reactive dropdown functionality
        unset($data['vehicle_type_id']);

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        // Create the user first
        $user = \App\Models\User::create([
            'name' => $data['user']['name'],
            'email' => $data['user']['email'],
            'phone' => $data['user']['phone'],
            'city_id' => $data['user']['city_id'],
            'is_active' => $data['user']['is_active'] ?? true,
            'password' => bcrypt('12345678'), // Default password
        ]);

        // Assign driver role
        $driverRole = Role::where('name', 'driver')->first();
        if ($driverRole) {
            $user->assignRole($driverRole);
        }

        // Create driver profile
        $driver = $user->driver()->create([
            'date_of_birth' => $data['date_of_birth'],
            'national_id' => $data['national_id'],
            'absher_phone' => $data['absher_phone'],
            'status' => $data['status'] ?? 0,
        ]);

        // Create driver vehicle if data is provided
        if (!empty($data['vehicle'])) {
            $vehicleData = $data['vehicle'];
            if (!empty($vehicleData['vehicle_brand_model_id']) || !empty($vehicleData['color']) || !empty($vehicleData['plate_number'])) {
                $driver->vehicle()->create([
                    'color' => $vehicleData['color'] ?? null,
                    'license_number' => $vehicleData['license_number'] ?? null,
                    'plate_number' => $vehicleData['plate_number'] ?? null,
                    'vehicle_type_id' => $vehicleData['vehicle_type_id'] ?? null,
                    'vehicle_brand_model_id' => $vehicleData['vehicle_brand_model_id'] ?? null,
                    'seats' => $vehicleData['seats'] ?? 4,
                ]);
            }
        }

        return $driver;
    }
}
