<?php

namespace App\Filament\Resources\DriverWithdrawRequestResource\Pages;

use App\Filament\Resources\DriverWithdrawRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDriverWithdrawRequest extends EditRecord
{
    protected static string $resource = DriverWithdrawRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
