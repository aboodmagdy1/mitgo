<?php

namespace App\Filament\Resources\CancelTripReasonResource\Pages;

use App\Filament\Resources\CancelTripReasonResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCancelTripReason extends EditRecord
{
    use EditRecord\Concerns\Translatable;
    protected static string $resource = CancelTripReasonResource::class;

    public function getTitle(): string
    {
            return __('Edit Cancel Trip Reason');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label(__('Delete')),
            Actions\LocaleSwitcher::make(),
        ];
    }
}
