<?php

namespace App\Filament\Resources\CustomerContactResource\Pages;

use App\Filament\Resources\CustomerContactResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCustomerContacts extends ListRecords
{
    protected static string $resource = CustomerContactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
