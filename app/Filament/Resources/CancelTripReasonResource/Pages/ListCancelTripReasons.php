<?php

namespace App\Filament\Resources\CancelTripReasonResource\Pages;

use App\Filament\Resources\CancelTripReasonResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCancelTripReasons extends ListRecords
{
    use ListRecords\Concerns\Translatable;
    protected static string $resource = CancelTripReasonResource::class;

    public function getTitle(): string
    {
        return 'أسباب إلغاء الرحلة';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إنشاء')
                ->modalHeading('إنشاء سبب إلغاء الرحلة')
                ->modalWidth('md')
                ->modalSubmitActionLabel('إنشاء')
                ->modalCancelActionLabel('إلغاء'),
            Actions\LocaleSwitcher::make(),
        ];
    }
}
