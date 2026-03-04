<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->modalHeading(__('Delete User')),
        ];
    }

    protected function afterSave(): void
    {
        // Handle role assignment after saving the user
        if (isset($this->data['role_id'])) {
            $roleId = $this->data['role_id'];
            $role = \Spatie\Permission\Models\Role::find($roleId);
            
            if ($role) {
                // Remove all existing roles and assign the new one
                $this->record->syncRoles([$role->name]);
            }
        }
    }
}
