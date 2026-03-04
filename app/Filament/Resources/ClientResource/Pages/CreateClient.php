<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class CreateClient extends CreateRecord
{
    protected static string $resource = ClientResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate default password if not provided
        if (empty($data['password'])) {
            $data['password'] = bcrypt('12345678'); // Default password
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        // Create the user first
        $user = static::getModel()::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'city_id' => $data['city_id'],
            'is_active' => $data['is_active'] ?? true,
            'password' => $data['password'] ?? bcrypt('12345678'),
        ]);

        // Assign rider role
        $riderRole = Role::where('name', 'rider')->first();
        if ($riderRole) {
            $user->assignRole($riderRole);
        }

        return $user;
    }
}
