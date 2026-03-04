<?php

namespace App\Filament\Resources\DriverWithdrawRequestResource\Pages;

use App\Filament\Resources\DriverWithdrawRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDriverWithdrawRequests extends ListRecords
{
    protected static string $resource = DriverWithdrawRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
