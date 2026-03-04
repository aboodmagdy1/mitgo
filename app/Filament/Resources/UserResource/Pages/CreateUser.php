<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Enums\UserTypeEnum;
use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    

    protected static string $resource = UserResource::class;


    public function getTitle(): string
    {
        return __("Create User");
    }
    public function getBreadcrumb(): string
    {
        return __("Create User");
    }
    // assign role to user after create
    protected function afterCreate(): void
    {
        if (isset($this->data['role_id'])) {
            $roleId = $this->data['role_id'];
            $role = \Spatie\Permission\Models\Role::find($roleId);
            
            if ($role) {
                $this->record->assignRole($role->name);
            }
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data;
    }
 
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
    // show user after create
}