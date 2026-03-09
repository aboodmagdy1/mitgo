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
                    ->label('المنطقة')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('base_fare')
                    ->label('الأجرة الأساسية')
                    ->money('SAR')
                    ->sortable(),

                TextColumn::make('fare_per_km')
                    ->label('الأجرة لكل كيلومتر')
                    ->money('SAR')
                    ->sortable(),

                TextColumn::make('fare_per_minute')
                    ->label('الأجرة لكل دقيقة')
                    ->money('SAR')
                    ->sortable(),

                TextColumn::make('cancellation_fee')
                    ->label('رسوم الإلغاء')
                    ->money('SAR')
                    ->sortable(),

                TextColumn::make('waiting_fee')
                    ->label('رسوم الانتظار')
                    ->money('SAR')
                    ->sortable(),

                TextColumn::make('extra_fare')
                    ->label('أجرة إضافية')
                    ->money('SAR')
                    ->sortable(),
            ])
            ->striped()
            ->heading('تسعير نوع المركبة')
            ->description('التسعير حسب المنطقة')
            ->emptyStateHeading('لا توجد تسعيرات متاحة')
            ->emptyStateDescription('يرجى إنشاء التسعير أولاً')
            ->paginated(false);
    }

    public static function canView(): bool
    {
        return true;
    }
}
