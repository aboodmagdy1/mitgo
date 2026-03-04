<?php

namespace App\Filament\Resources\VehicleTypeResource\Pages;

use App\Filament\Resources\VehicleTypeResource;
use App\Models\Zone;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Support\Enums\FontWeight;

class ViewVehicleType extends ViewRecord
{

    protected static string $resource = VehicleTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make(__('Vehicle Type Information'))
                    ->schema([
                        SpatieMediaLibraryImageEntry::make('icon')
                            ->label(__('Icon'))
                            ->collection('icon')
                            ->circular()
                            ->size(100),

                        TextEntry::make('name')
                            ->label(__('Name'))
                            ->translateLabel()
                            ->weight(FontWeight::Bold),

                        TextEntry::make('seats')
                            ->label(__('Number of Seats'))
                            ->translateLabel()
                            ->badge()
                            ->color('primary'),

                        IconEntry::make('active')
                            ->label(__('Active'))
                            ->translateLabel()
                            ->boolean(),
                    ])
                    ->columns(2),

                Section::make(__('Default Pricing'))
                    ->description(__('Default pricing configuration used when no zone-specific pricing is available'))
                    ->schema([
                        TextEntry::make('defaultPricing.base_fare')
                            ->label(__('Base Fare'))
                            ->translateLabel()
                            ->money('SAR'),

                        TextEntry::make('defaultPricing.fare_per_km')
                            ->label(__('Fare per KM'))
                            ->translateLabel()
                            ->money('SAR'),

                        TextEntry::make('defaultPricing.fare_per_minute')
                            ->label(__('Fare per Minute'))
                            ->translateLabel()
                            ->money('SAR'),

                        TextEntry::make('defaultPricing.cancellation_fee')
                            ->label(__('Cancellation Fee'))
                            ->translateLabel()
                            ->money('SAR'),

                        TextEntry::make('defaultPricing.waiting_fee')
                            ->label(__('Waiting Fee'))
                            ->translateLabel()
                            ->money('SAR'),
                    ])
                    ->columns(3)
                    ->collapsed(),
            ]);
    }

    protected function getFooterWidgets(): array
    {
        return [
            VehicleTypeResource\Widgets\ZonePricingTable::make([
                'vehicleTypeId' => $this->record->id,
            ]),
        ];
    }
}
