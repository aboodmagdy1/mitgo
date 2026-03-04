<?php

namespace App\Filament\Resources\CityResource\Pages;

use App\Filament\Resources\CityResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCity extends EditRecord
{
    use EditRecord\Concerns\Translatable;
    protected static string $resource = CityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
            Actions\DeleteAction::make()->modalHeading(__('Delete City')),
        ];
    }

    public function getTitle(): string
    {
        return __('Edit City');
    }
}
