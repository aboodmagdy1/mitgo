<?php

namespace App\Filament\Resources\CancelTripReasonResource\Pages;

use App\Filament\Resources\CancelTripReasonResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCancelTripReason extends CreateRecord
{
    use CreateRecord\Concerns\Translatable;
    protected static string $resource = CancelTripReasonResource::class;

    public function getTitle(): string
    {
        return __('Create Cancel Trip Reason');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
        ];
    }
}
