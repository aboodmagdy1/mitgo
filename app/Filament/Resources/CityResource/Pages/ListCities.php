<?php

namespace App\Filament\Resources\CityResource\Pages;

use App\Filament\Resources\CityResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCities extends ListRecords
{
    use ListRecords\Concerns\Translatable;
    protected static string $resource = CityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label(__('Add City')),
            Actions\LocaleSwitcher::make(),
        ];
    }

    public function getTitle(): string
    {
        return __('Cities');
    }
}
