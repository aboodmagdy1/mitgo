<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TripResource\Pages;
use App\Models\Trip;
use App\Models\Driver;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Enums\TripPaymentType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class TripResource extends Resource
{
    protected static ?string $model = Trip::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';
    public static  function getNavigationGroup(): ?string
    {
        return __('Trips Management');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Trips');
    }
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Trip Information'))
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label(__('Rider'))
                            ->relationship('user', 'name', fn($query) => $query->whereNotNull('name'))
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('driver_id')
                            ->label(__('Driver'))
                            ->relationship('driver.user', 'name', fn($query) => $query->whereNotNull('name'))
                            ->searchable()
                            ->nullable(),
                        Forms\Components\Select::make('zone_id')
                            ->label(__('Zone'))
                            ->relationship('zone', 'name', fn($query) => $query->whereNotNull('name'))
                            ->searchable()
                            ->nullable(),
                        Forms\Components\Select::make('vehicle_type_id')
                            ->label(__('Vehicle Type'))
                            ->relationship('vehicleType', 'name', fn($query) => $query->whereNotNull('name'))
                            ->searchable()
                            ->nullable(),
                        Forms\Components\Select::make('type')
                            ->label(__('Trip Type'))
                            ->options([
                                TripType::immediate->value => TripType::immediate->label(),
                                TripType::scheduled->value => TripType::scheduled->label(),
                            ])
                            ->default(TripType::immediate->value)
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->label(__('Status'))
                            ->options(collect(TripStatus::cases())->mapWithKeys(fn($case) => [
                                $case->value => $case->label()
                            ]))
                            ->default(TripStatus::SEARCHING->value)
                            ->required(),
                        Forms\Components\Select::make('payment_method_id')
                            ->label(__('Payment Method'))
                            ->relationship('paymentMethod', 'name', fn($query) => $query->whereNotNull('name'))
                            ->searchable()
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make(__('Pickup Information'))
                    ->schema([
                        Forms\Components\TextInput::make('pickup_lat')
                            ->label(__('Pickup Latitude'))
                            ->numeric()
                            ->step(0.00000001)
                            ->rules(['nullable', 'numeric', 'between:-90,90']),
                        Forms\Components\TextInput::make('pickup_long')
                            ->label(__('Pickup Longitude'))
                            ->numeric()
                            ->step(0.00000001)
                            ->rules(['nullable', 'numeric', 'between:-180,180']),
                        Forms\Components\Textarea::make('pickup_address')
                            ->label(__('Pickup Address'))
                            ->rows(2),
                    ])->columns(2),

                Forms\Components\Section::make(__('Dropoff Information'))
                    ->schema([
                        Forms\Components\TextInput::make('dropoff_lat')
                            ->label(__('Dropoff Latitude'))
                            ->numeric()
                            ->step(0.00000001)
                            ->rules(['nullable', 'numeric', 'between:-90,90']),
                        Forms\Components\TextInput::make('dropoff_long')
                            ->label(__('Dropoff Longitude'))
                            ->numeric()
                            ->step(0.00000001)
                            ->rules(['nullable', 'numeric', 'between:-180,180']),
                        Forms\Components\Textarea::make('dropoff_address')
                            ->label(__('Dropoff Address'))
                            ->rows(2),
                    ])->columns(2),

                Forms\Components\Section::make(__('Trip Details'))
                    ->schema([
                        Forms\Components\TextInput::make('distance')
                            ->label(__('Distance (km)'))
                            ->numeric()
                            ->step(0.01)
                            ->suffix('km'),
                        Forms\Components\TextInput::make('estimated_duration')
                            ->label(__('Estimated Duration (minutes)'))
                            ->numeric()
                            ->suffix(__('minutes')),
                        Forms\Components\TextInput::make('actual_duration')
                            ->label(__('Actual Duration (minutes)'))
                            ->numeric()
                            ->suffix(__('minutes')),
                        Forms\Components\TextInput::make('estimated_fare')
                            ->label(__('Estimated Fare'))
                            ->numeric()
                            ->step(0.01)
                            ->prefix(__('SAR')),
                        Forms\Components\TextInput::make('actual_fare')
                            ->label(__('Actual Fare'))
                            ->numeric()
                            ->step(0.01)
                            ->prefix(__('SAR')),
                        Forms\Components\TextInput::make('waiting_fee')
                            ->label(__('Waiting Fee'))
                            ->numeric()
                            ->step(0.01)
                            ->default(0)
                            ->prefix(__('SAR')),
                        Forms\Components\TextInput::make('cancellation_fee')
                            ->label(__('Cancellation Fee'))
                            ->numeric()
                            ->step(0.01)
                            ->prefix(__('SAR')),
                    ])->columns(2),

                Forms\Components\Section::make(__('Scheduling'))
                    ->schema([
                        Forms\Components\Toggle::make('is_scheduled')
                            ->label(__('Is Scheduled Trip')),
                        Forms\Components\DatePicker::make('scheduled_date')
                            ->label(__('Scheduled Date')),
                        Forms\Components\TimePicker::make('scheduled_time')
                            ->label(__('Scheduled Time')),
                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->label(__('Scheduled At')),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label(__('Trip ID'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label(__('Rider'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('driver.user.name')
                    ->label(__('Driver'))
                    ->searchable()
                    ->sortable()
                    ->default(__('Not Assigned')),
                BadgeColumn::make('status')
                    ->label(__('Status'))
                    ->formatStateUsing(fn ($state) => $state?->label() ?? __('Unknown'))
                    ->colors([
                        'warning' => TripStatus::SEARCHING->value,
                        'info' => TripStatus::IN_ROUTE_TO_PICKUP->value,
                        'primary' => TripStatus::PICKUP_ARRIVED->value,
                        'secondary' => TripStatus::IN_PROGRESS->value,
                        'success' => TripStatus::PAID->value,
                        'danger' => [
                            TripStatus::CANCELLED_BY_DRIVER->value,
                            TripStatus::CANCELLED_BY_RIDER->value,
                            TripStatus::CANCELLED_BY_SYSTEM->value,
                        ],
                    ]),
                TextColumn::make('pickup_address')
                    ->label(__('Pickup'))
                    ->limit(30)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 30) {
                            return null;
                        }
                        return $state;
                    })
                    ->searchable(),
                TextColumn::make('dropoff_address')
                    ->label(__('Dropoff'))
                    ->limit(30)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 30) {
                            return null;
                        }
                        return $state;
                    })
                    ->searchable(),
                TextColumn::make('distance')
                    ->label(__('Distance (km)'))
                    ->sortable()
                    ->suffix(' km'),
                TextColumn::make('actual_fare')
                    ->label(__('Fare'))
                    ->money('SAR')
                    ->sortable(),
                BadgeColumn::make('paymentMethod.name')
                    ->label(__('Payment'))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options(collect(TripStatus::cases())->mapWithKeys(fn($case) => [
                        $case->value => $case->label()
                    ]))
                    ->multiple(),
                SelectFilter::make('payment_method_id')
                    ->label(__('Payment Method'))
                    ->relationship('paymentMethod', 'name', fn($query) => $query->whereNotNull('name'))
                    ->searchable()
                    ->multiple(),
                SelectFilter::make('type')
                    ->label(__('Trip Type'))
                    ->options([
                        TripType::immediate->value => TripType::immediate->label(),
                        TripType::scheduled->value => TripType::scheduled->label(),
                    ]),
                Filter::make('is_scheduled')
                    ->query(fn (Builder $query): Builder => $query->where('is_scheduled', true))
                    ->label(__('Scheduled Trips')),
                Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label(__('Created From')),
                        Forms\Components\DatePicker::make('created_until')
                            ->label(__('Created Until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['created_from'] ?? null) {
                            $indicators[] = 'Created from ' . \Carbon\Carbon::parse($data['created_from'])->toFormattedDateString();
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators[] = 'Created until ' . \Carbon\Carbon::parse($data['created_until'])->toFormattedDateString();
                        }
                        return $indicators;
                    }),
                Filter::make('fare_range')
                    ->form([
                        Forms\Components\TextInput::make('min_fare')
                            ->label(__('Min Fare'))
                            ->numeric()
                            ->prefix(__('SAR')),
                        Forms\Components\TextInput::make('max_fare')
                            ->label(__('Max Fare'))
                            ->numeric()
                            ->prefix(__('SAR')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_fare'],
                                fn (Builder $query, $amount): Builder => $query->where('actual_fare', '>=', $amount),
                            )
                            ->when(
                                $data['max_fare'],
                                fn (Builder $query, $amount): Builder => $query->where('actual_fare', '<=', $amount),
                            );
                    }),
                SelectFilter::make('driver_id')
                    ->label(__('Driver'))
                    ->relationship('driver.user', 'name', fn($query) => $query->whereNotNull('name'))
                    ->searchable()
                    ->preload(),
                SelectFilter::make('zone_id')
                    ->label(__('Zone'))
                    ->relationship('zone', 'name', fn($query) => $query->whereNotNull('name'))
                    ->searchable()
                    ->multiple(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                // Tables\Actions\EditAction::make()
                //     ->visible(fn ($record) => !in_array($record->status, [
                //         TripStatus::PAID,
                //         TripStatus::CANCELLED_BY_DRIVER,
                //         TripStatus::CANCELLED_BY_RIDER,
                //         TripStatus::CANCELLED_BY_SYSTEM,
                //     ])),
                // Tables\Actions\Action::make('assign_driver')
                //     ->label(__('Assign Driver'))
                //     ->icon('heroicon-o-user-plus')
                //     ->color('info')
                //     ->visible(fn ($record) => $record->driver_id === null && !in_array($record->status, [
                //         TripStatus::CANCELLED_BY_DRIVER,
                //         TripStatus::CANCELLED_BY_RIDER,
                //         TripStatus::CANCELLED_BY_SYSTEM,
                //         TripStatus::PAID,
                //         TripStatus::COMPLETED,
                //     ]))
                //     ->form([
                //         Forms\Components\Select::make('driver_id')
                //             ->label(__('Select Driver'))
                //             ->options(function () {
                //                 return Driver::whereHas('user', function ($query) {
                //                     $query->where('is_active', true)
                //                         ->whereNotNull('name');
                //                 })
                //                 ->where('is_approved', true)
                //                 ->where('is_online', true)
                //                 ->with('user')
                //                 ->get()
                //                 ->filter(fn($driver) => $driver->user && $driver->user->name)
                //                 ->pluck('user.name', 'id');
                //             })
                //             ->searchable()
                //             ->required()
                //             ->placeholder(__('Select Driver'))
                //             ->helperText(__('Only active and online drivers are shown')),
                //     ])
                //     ->action(function ($record, array $data) {
                //         $record->update([
                //             'driver_id' => $data['driver_id'],
                //             'status' => TripStatus::IN_ROUTE_TO_PICKUP,
                //         ]);
                        
                //         \Filament\Notifications\Notification::make()
                //             ->title(__('Driver assigned successfully'))
                //             ->success()
                //             ->send();
                //     })
                //     ->requiresConfirmation()
                //     ->modalHeading(__('Assign Driver'))
                //     ->modalDescription(__('Are you sure you want to assign a driver to this trip?'))
                //     ->modalSubmitActionLabel(__('Assign Driver')),
                    
                // Tables\Actions\Action::make('cancel_trip')
                //     ->label(__('Cancel Trip'))
                //     ->icon('heroicon-o-x-circle')
                //     ->color('danger')
                //     ->visible(fn ($record) => !in_array($record->status, [
                //         TripStatus::CANCELLED_BY_DRIVER,
                //         TripStatus::CANCELLED_BY_RIDER, 
                //         TripStatus::CANCELLED_BY_SYSTEM,
                //         TripStatus::PAID,
                //         TripStatus::COMPLETED,
                //     ]))
                //     ->form([
                //         Forms\Components\Textarea::make('cancellation_reason')
                //             ->label(__('Cancellation Reason'))
                //             ->required()
                //             ->rows(3),
                //     ])
                //     ->action(function ($record, array $data) {
                //         $record->update([
                //             'status' => TripStatus::CANCELLED_BY_SYSTEM,
                //         ]);
                        
                //         \Filament\Notifications\Notification::make()
                //             ->title(__('Trip cancelled successfully'))
                //             ->success()
                //             ->send();
                //     })
                //     ->requiresConfirmation()
                //     ->modalHeading(__('Cancel Trip'))
                //     ->modalDescription(__('Are you sure you want to cancel this trip?'))
                //     ->modalSubmitActionLabel(__('Cancel Trip')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make(__('Trip Information'))
                    ->icon('heroicon-o-map')
                    ->schema([
                        TextEntry::make('number')
                            ->label(__('Trip ID')),
                        TextEntry::make('user.name')
                            ->label(__('Rider')),
                        TextEntry::make('user.phone')
                            ->label(__('Rider Phone'))
                            ->icon('heroicon-o-phone'),
                        TextEntry::make('driver.user.name')
                            ->label(__('Driver'))
                            ->default(__('Not Assigned')),
                        TextEntry::make('driver.user.phone')
                            ->label(__('Driver Phone'))
                            ->icon('heroicon-o-phone')
                            ->visible(fn ($record) => $record->driver_id !== null),
                        TextEntry::make('status')
                            ->label(__('Status'))
                            ->badge()
                            ->formatStateUsing(function ($state) {
                                return $state?->label() ?? __('Unknown');
                            })
                            ->color(fn ($state) => match($state?->value ?? null) {
                                TripStatus::SEARCHING->value => 'warning',
                                TripStatus::IN_ROUTE_TO_PICKUP->value => 'info',
                                TripStatus::PICKUP_ARRIVED->value => 'primary',
                                TripStatus::IN_PROGRESS->value => 'secondary',
                                TripStatus::PAID->value, TripStatus::COMPLETED->value => 'success',
                                TripStatus::CANCELLED_BY_DRIVER->value,
                                TripStatus::CANCELLED_BY_RIDER->value,
                                TripStatus::CANCELLED_BY_SYSTEM->value => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('type')
                            ->label(__('Trip Type'))
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state?->label() ?? __('Unknown'))
                            ->color(fn ($state) => match($state?->value ?? null) {
                                TripType::immediate->value => 'info',
                                TripType::scheduled->value => 'primary',
                                default => 'gray',
                            }),
                        TextEntry::make('paymentMethod.name')
                            ->label(__('Payment Method'))
                            ->badge()
                            ->color('success'),
                        TextEntry::make('zone.name')
                            ->label(__('Zone'))
                            ->default(__('N/A')),
                        TextEntry::make('vehicleType.name')
                            ->label(__('Vehicle Type'))
                            ->default(__('N/A')),
                    ])->columns(2),

                Section::make(__('Location Details'))
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        TextEntry::make('pickup_address')
                            ->label(__('Pickup Address'))
                            ->columnSpanFull(),
                       
                        TextEntry::make('dropoff_address')
                            ->label(__('Dropoff Address'))
                            ->columnSpanFull(),
                       
                    ])->columns(2),

                Section::make(__('Trip Metrics'))
                    ->icon('heroicon-o-chart-bar')
                    ->schema([
                        TextEntry::make('distance')
                            ->label(__('Distance'))
                            ->formatStateUsing(fn ($state) => $state ? $state . ' km' : __('N/A'))
                            ->icon('heroicon-o-map'),
                        TextEntry::make('estimated_duration')
                            ->label(__('Estimated Duration'))
                            ->formatStateUsing(fn ($state) => $state ? $state . ' ' . __('minutes') : __('N/A'))
                            ->icon('heroicon-o-clock'),
                        TextEntry::make('actual_duration')
                            ->label(__('Actual Duration'))
                            ->visible(fn($record) => $record->status === TripStatus::COMPLETED)
                            ->formatStateUsing(fn ($state) => $state ? $state . ' ' . __('minutes') : __('N/A'))
                            ->icon('heroicon-o-clock'),
                        TextEntry::make('estimated_fare')
                            ->label(__('Estimated Fare'))
                            ->money('SAR'),
                        TextEntry::make('actual_fare')
                            ->label(__('Actual Fare (Customer Pays)'))
                            ->money('SAR')
                            ->getStateUsing(fn ($record) => $record->payment?->final_amount ?? $record->actual_fare)
                            ->visible(fn($record) => $record->status === TripStatus::COMPLETED || $record->payment !== null),
                        TextEntry::make('waiting_fee')
                            ->label(__('Waiting Fee'))
                            ->money('SAR')
                            ->visible(fn($record) => $record->status === TripStatus::COMPLETED),
                        TextEntry::make('cancellation_fee')
                            ->label(__('Cancellation Fee'))
                            ->money('SAR')
                            ->visible(fn ($record) => $record->cancellation_fee > 0 && $record->status === TripStatus::COMPLETED),
                    ])->columns(3),

                Section::make(__('Financial Details'))
                    ->icon('heroicon-o-currency-dollar')
                    ->schema([
                        TextEntry::make('gross_amount')
                            ->label(__('Gross Amount (Before Coupon)'))
                            ->money('SAR')
                            ->getStateUsing(fn ($record) => $record->payment?->total_amount)
                            ->default(__('N/A'))
                            ->visible(fn ($record) => $record->payment !== null),
                        TextEntry::make('final_amount')
                            ->label(__('Final Amount (After Coupon)'))
                            ->money('SAR')
                            ->getStateUsing(fn ($record) => $record->payment?->final_amount ?? $record->payment?->total_amount)
                            ->default(__('N/A'))
                            ->visible(fn ($record) => $record->payment !== null),
                        TextEntry::make('coupon_discount')
                            ->label(__('Coupon Discount'))
                            ->money('SAR')
                            ->getStateUsing(fn ($record) => $record->payment?->coupon_discount ?? 0)
                            ->default(0)
                            ->visible(fn ($record) => $record->payment && ($record->payment->coupon_discount ?? 0) > 0),
                        TextEntry::make('commission_rate')
                            ->label(__('Commission Rate'))
                            ->suffix('%')
                            ->getStateUsing(fn ($record) => $record->payment?->commission_rate)
                            ->default(__('N/A'))
                            ->visible(fn ($record) => $record->payment !== null),
                        TextEntry::make('commission_amount')
                            ->label(__('Platform Commission'))
                            ->money('SAR')
                            ->getStateUsing(fn ($record) => $record->payment?->commission_amount)
                            ->default(__('N/A'))
                            ->visible(fn ($record) => $record->payment !== null),
                        TextEntry::make('driver_earning')
                            ->label(__('Driver Earnings'))
                            ->money('SAR')
                            ->getStateUsing(fn ($record) => $record->payment?->driver_earning)
                            ->default(__('N/A'))
                            ->visible(fn ($record) => $record->payment !== null),
                        TextEntry::make('additional_fees')
                            ->label(__('Additional Fees'))
                            ->money('SAR')
                            ->getStateUsing(fn ($record) => $record->payment?->additional_fees ?? 0)
                            ->default(0)
                            ->visible(fn ($record) => $record->payment && ($record->payment->additional_fees ?? 0) > 0),
                        TextEntry::make('payment_status')
                            ->label(__('Payment Status'))
                            ->badge()
                            ->getStateUsing(function ($record) {
                                $status = $record->payment?->status;
                                return match($status) {
                                    0 => __('Pending'),
                                    1 => __('Completed'),
                                    2 => __('Failed'),
                                    3 => __('Refunded'),
                                    default => __('N/A'),
                                };
                            })
                            ->color(function ($record) {
                                $status = $record->payment?->status;
                                return match($status) {
                                    0 => 'warning',
                                    1 => 'success',
                                    2 => 'danger',
                                    3 => 'info',
                                    default => 'gray',
                                };
                            })
                            ->visible(fn ($record) => $record->payment !== null),
                    ])->columns(3)
                    ->visible(fn ($record) => $record->payment !== null),

                Section::make(__('Trip Timeline'))
                    ->icon('heroicon-o-clock')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label(__('Trip Requested'))
                            ->dateTime()
                            ->since(),
                        TextEntry::make('started_at')
                            ->label(__('Trip Started'))
                            ->dateTime()
                            ->since()
                            ->visible(fn ($record) => $record->started_at !== null),
                        TextEntry::make('arrived_at')
                            ->label(__('Driver Arrived'))
                            ->dateTime()
                            ->since()
                            ->visible(fn ($record) => $record->arrived_at !== null),
                        TextEntry::make('ended_at')
                            ->label(__('Trip Ended'))
                            ->dateTime()
                            ->since()
                            ->visible(fn ($record) => $record->ended_at !== null),
                        TextEntry::make('duration')
                            ->label(__('Total Duration'))
                            ->getStateUsing(function ($record) {
                                if ($record->started_at && $record->ended_at) {
                                    return $record->started_at->diffForHumans($record->ended_at, true);
                                }
                                return __('N/A');
                            }),
                    ])->columns(2),

                Section::make(__('Scheduling Information'))
                    ->icon('heroicon-o-calendar')
                    ->schema([
                        TextEntry::make('is_scheduled')
                            ->label(__('Scheduled Trip'))
                            ->badge()
                            ->formatStateUsing(fn (bool $state): string => $state ? __('Yes') : __('No'))
                            ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                        TextEntry::make('scheduled_date')
                            ->label(__('Scheduled Date'))
                            ->date(),
                        TextEntry::make('scheduled_time')
                            ->label(__('Scheduled Time'))
                            ->time(),
                        TextEntry::make('scheduled_at')
                            ->label(__('Scheduled DateTime'))
                            ->dateTime(),
                    ])->columns(2)
                    ->visible(fn ($record) => $record->is_scheduled),

                Section::make(__('Timestamps'))
                    ->icon('heroicon-o-clock')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label(__('Created At'))
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->label(__('Updated At'))
                            ->dateTime(),
                    ])->columns(2)
                    ->collapsed(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrips::route('/'),
            // 'create' => Pages\CreateTrip::route('/create'),
            'view' => Pages\ViewTrip::route('/{record}'),
            // 'edit' => Pages\EditTrip::route('/{record}/edit'),
        ];
    }

    public static function getLabel(): string
    {
        return __('Trip');
    }

    public static function getPluralLabel(): string
    {
        return __('Trips');
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'user:id,name,phone',
                'driver.user:id,name,phone',
                'paymentMethod:id,name',
                'zone:id,name',
                'vehicleType:id,name',
                'payment'
            ]);
    }
}