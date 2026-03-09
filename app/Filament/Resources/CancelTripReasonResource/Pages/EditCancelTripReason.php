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
            return 'تعديل سبب إلغاء الرحلة';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('حذف'),
            Actions\LocaleSwitcher::make(),
        ];
    }
}
