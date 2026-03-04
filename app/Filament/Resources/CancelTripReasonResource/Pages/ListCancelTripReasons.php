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
        return __('Cancel Trip Reasons');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('Create'))
                ->modalHeading(__('Create Cancel Trip Reason'))
                ->modalWidth('md')
                ->modalSubmitActionLabel(__('Create'))
                ->modalCancelActionLabel(__('Cancel')),
            Actions\LocaleSwitcher::make(),
        ];
    }
}
