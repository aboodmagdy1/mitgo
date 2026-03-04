<?php

namespace App\Filament\Resources\VehicleTypeResource\Widgets;

use App\Models\Zone;
use App\Models\ZoneVehicleTypePricing;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget as BaseTableWidget;
use Illuminate\Database\Eloquent\Builder;

class ZonePricingTable extends BaseTableWidget
{

    
    protected int | string | array $columnSpan = 'full';
    
    public ?int $vehicleTypeId = null;

    protected function getTableQuery(): Builder
    {
        return ZoneVehicleTypePricing::query()
            ->where('vehicle_type_id', $this->vehicleTypeId)
            ->with(['zone']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('zone.name')
                    ->label(__('Zone'))
                    ->translateLabel()
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('base_fare')
                    ->label(__('Base Fare'))
                    ->translateLabel()
                    ->money('SAR')
                    ->sortable(),

                TextColumn::make('fare_per_km')
                    ->label(__('Fare per KM'))
                    ->translateLabel()
                    ->money('SAR')
                    ->sortable(),

                TextColumn::make('fare_per_minute')
                    ->label(__('Fare per Minute'))
                    ->translateLabel()
                    ->money('SAR')
                    ->sortable(),

                TextColumn::make('cancellation_fee')
                    ->label(__('Cancellation Fee'))
                    ->translateLabel()
                    ->money('SAR')
                    ->sortable(),

                TextColumn::make('waiting_fee')
                    ->label(__('Waiting Fee'))
                    ->translateLabel()
                    ->money('SAR')
                    ->sortable(),

                TextColumn::make('extra_fare')
                    ->label(__('Extra Fare'))
                    ->translateLabel()
                    ->money('SAR')
                    ->sortable(),
            ])
            ->striped()
            ->heading(__('Vehicle Type Pricing'))
            ->description(__('Pricing by Zone'))
            ->emptyStateHeading(__('No pricing available'))
            ->emptyStateDescription(__('Please create pricing first'))
            ->paginated(false);
    }

    public static function canView(): bool
    {
        return true;
    }
}
