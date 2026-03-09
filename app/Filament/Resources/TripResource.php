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

    /** Arabic labels for enums (Filament admin only - enums stay unchanged for API). */
    public static function tripStatusLabel(?TripStatus $status): string
    {
        return $status ? match ($status) {
            TripStatus::SEARCHING => 'البحث',
            TripStatus::RIDER_NO_SHOW => 'عدم حضور الراكب',
            TripStatus::NO_DRIVER_FOUND => 'لم يتم العثور على سائق',
            TripStatus::IN_ROUTE_TO_PICKUP => 'في الطريق لنقطة الانطلاق',
            TripStatus::PICKUP_ARRIVED => 'وصل إلى نقطة الانطلاق',
            TripStatus::RIDER_NOT_FOUND => 'لم يتم العثور على الراكب',
            TripStatus::IN_PROGRESS => 'قيد التنفيذ',
            TripStatus::COMPLETED_PENDING_PAYMENT => 'مكتمل في انتظار الدفع',
            TripStatus::PAYMENT_FAILED => 'فشل الدفع',
            TripStatus::PAID => 'مدفوع',
            TripStatus::CANCELLED_BY_DRIVER => 'ملغي من قبل السائق',
            TripStatus::CANCELLED_BY_RIDER => 'ملغي من قبل الراكب',
            TripStatus::CANCELLED_BY_SYSTEM => 'ملغي من قبل النظام',
            TripStatus::TRIP_EXPIRED => 'انتهت صلاحية الرحلة',
            TripStatus::SCHEDULED => 'مجدولة',
            TripStatus::COMPLETED => 'مكتملة',
            default => 'غير معروف',
        } : 'غير معروف';
    }

    public static function tripTypeLabel(?TripType $type): string
    {
        return $type ? match ($type) {
            TripType::immediate => 'فوري',
            TripType::scheduled => 'مجدولة',
            default => 'غير معروف',
        } : 'غير معروف';
    }
    public static  function getNavigationGroup(): ?string
    {
        return 'إدارة الرحلات';
    }

    public static function getPluralModelLabel(): string
    {
        return 'الرحلات';
    }
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الرحلة')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('الراكب')
                            ->relationship('user', 'name', fn($query) => $query->whereNotNull('name'))
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('driver_id')
                            ->label('السائق')
                            ->relationship('driver.user', 'name', fn($query) => $query->whereNotNull('name'))
                            ->searchable()
                            ->nullable(),
                        Forms\Components\Select::make('zone_id')
                            ->label('المنطقة')
                            ->relationship('zone', 'name', fn($query) => $query->whereNotNull('name'))
                            ->searchable()
                            ->nullable(),
                        Forms\Components\Select::make('vehicle_type_id')
                            ->label('نوع المركبة')
                            ->relationship('vehicleType', 'name', fn($query) => $query->whereNotNull('name'))
                            ->searchable()
                            ->nullable(),
                        Forms\Components\Select::make('type')
                            ->label('نوع الرحلة')
                            ->options([
                                TripType::immediate->value => static::tripTypeLabel(TripType::immediate),
                                TripType::scheduled->value => static::tripTypeLabel(TripType::scheduled),
                            ])
                            ->default(TripType::immediate->value)
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options(collect(TripStatus::cases())->mapWithKeys(fn($case) => [
                                $case->value => static::tripStatusLabel($case)
                            ]))
                            ->default(TripStatus::SEARCHING->value)
                            ->required(),
                        Forms\Components\Select::make('payment_method_id')
                            ->label('طريقة الدفع')
                            ->relationship('paymentMethod', 'name', fn($query) => $query->whereNotNull('name'))
                            ->searchable()
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('معلومات نقطة الانطلاق')
                    ->schema([
                        Forms\Components\TextInput::make('pickup_lat')
                            ->label('خط عرض نقطة الانطلاق')
                            ->numeric()
                            ->step(0.00000001)
                            ->rules(['nullable', 'numeric', 'between:-90,90']),
                        Forms\Components\TextInput::make('pickup_long')
                            ->label('خط طول نقطة الانطلاق')
                            ->numeric()
                            ->step(0.00000001)
                            ->rules(['nullable', 'numeric', 'between:-180,180']),
                        Forms\Components\Textarea::make('pickup_address')
                            ->label('عنوان نقطة الانطلاق')
                            ->rows(2),
                    ])->columns(2),

                Forms\Components\Section::make('معلومات نقطة الوصول')
                    ->schema([
                        Forms\Components\TextInput::make('dropoff_lat')
                            ->label('خط عرض نقطة الوصول')
                            ->numeric()
                            ->step(0.00000001)
                            ->rules(['nullable', 'numeric', 'between:-90,90']),
                        Forms\Components\TextInput::make('dropoff_long')
                            ->label('خط طول نقطة الوصول')
                            ->numeric()
                            ->step(0.00000001)
                            ->rules(['nullable', 'numeric', 'between:-180,180']),
                        Forms\Components\Textarea::make('dropoff_address')
                            ->label('عنوان نقطة الوصول')
                            ->rows(2),
                    ])->columns(2),

                Forms\Components\Section::make('تفاصيل الرحلة')
                    ->schema([
                        Forms\Components\TextInput::make('distance')
                            ->label('المسافة (كم)')
                            ->numeric()
                            ->step(0.01)
                            ->suffix('km'),
                        Forms\Components\TextInput::make('estimated_duration')
                            ->label('المدة المقدرة (دقائق)')
                            ->numeric()
                            ->suffix('دقائق'),
                        Forms\Components\TextInput::make('actual_duration')
                            ->label('المدة الفعلية (دقائق)')
                            ->numeric()
                            ->suffix('دقائق'),
                        Forms\Components\TextInput::make('estimated_fare')
                            ->label('الأجرة المقدرة')
                            ->numeric()
                            ->step(0.01)
                            ->prefix('ريال'),
                        Forms\Components\TextInput::make('actual_fare')
                            ->label('الأجرة الفعلية')
                            ->numeric()
                            ->step(0.01)
                            ->prefix('ريال'),
                        Forms\Components\TextInput::make('waiting_fee')
                            ->label('رسوم الانتظار')
                            ->numeric()
                            ->step(0.01)
                            ->default(0)
                            ->prefix('ريال'),
                        Forms\Components\TextInput::make('cancellation_fee')
                            ->label('رسوم الإلغاء')
                            ->numeric()
                            ->step(0.01)
                            ->prefix('ريال'),
                    ])->columns(2),

                Forms\Components\Section::make('الجدولة')
                    ->schema([
                        Forms\Components\Toggle::make('is_scheduled')
                            ->label('رحلة مجدولة'),
                        Forms\Components\DatePicker::make('scheduled_date')
                            ->label('التاريخ المجدول'),
                        Forms\Components\TimePicker::make('scheduled_time')
                            ->label('الوقت المجدول'),
                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->label('مجدولة في'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('رقم الرحلة')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('الراكب')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('driver.user.name')
                    ->label('السائق')
                    ->searchable()
                    ->sortable()
                    ->default('غير معين'),
                BadgeColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn ($state) => static::tripStatusLabel($state))
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
                    ->label('نقطة الانطلاق')
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
                    ->label('نقطة الوصول')
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
                    ->label('المسافة (كم)')
                    ->sortable()
                    ->suffix(' km'),
                TextColumn::make('actual_fare')
                    ->label('الأجرة')
                    ->money('SAR')
                    ->sortable(),
                BadgeColumn::make('paymentMethod.name')
                    ->label('الدفع')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(collect(TripStatus::cases())->mapWithKeys(fn($case) => [
                        $case->value => static::tripStatusLabel($case)
                    ]))
                    ->multiple(),
                SelectFilter::make('payment_method_id')
                    ->label('طريقة الدفع')
                    ->relationship('paymentMethod', 'name', fn($query) => $query->whereNotNull('name'))
                    ->searchable()
                    ->multiple(),
                SelectFilter::make('type')
                    ->label('نوع الرحلة')
                    ->options([
                        TripType::immediate->value => static::tripTypeLabel(TripType::immediate),
                        TripType::scheduled->value => static::tripTypeLabel(TripType::scheduled),
                    ]),
                Filter::make('is_scheduled')
                    ->query(fn (Builder $query): Builder => $query->where('is_scheduled', true))
                    ->label('الرحلات المجدولة'),
                Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('من تاريخ'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('إلى تاريخ'),
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
                            $indicators[] = 'من تاريخ ' . \Carbon\Carbon::parse($data['created_from'])->toFormattedDateString();
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators[] = 'إلى تاريخ ' . \Carbon\Carbon::parse($data['created_until'])->toFormattedDateString();
                        }
                        return $indicators;
                    }),
                Filter::make('fare_range')
                    ->form([
                        Forms\Components\TextInput::make('min_fare')
                            ->label('الحد الأدنى للأجرة')
                            ->numeric()
                            ->prefix('ريال'),
                        Forms\Components\TextInput::make('max_fare')
                            ->label('الحد الأقصى للأجرة')
                            ->numeric()
                            ->prefix('ريال'),
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
                    ->label('السائق')
                    ->relationship('driver.user', 'name', fn($query) => $query->whereNotNull('name'))
                    ->searchable()
                    ->preload(),
                SelectFilter::make('zone_id')
                    ->label('المنطقة')
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
                Section::make('معلومات الرحلة')
                    ->icon('heroicon-o-map')
                    ->schema([
                        TextEntry::make('number')
                            ->label('رقم الرحلة'),
                        TextEntry::make('user.name')
                            ->label('الراكب'),
                        TextEntry::make('user.phone')
                            ->label('هاتف الراكب')
                            ->icon('heroicon-o-phone'),
                        TextEntry::make('driver.user.name')
                            ->label('السائق')
                            ->default('غير معين'),
                        TextEntry::make('driver.user.phone')
                            ->label('هاتف السائق')
                            ->icon('heroicon-o-phone')
                            ->visible(fn ($record) => $record->driver_id !== null),
                        TextEntry::make('status')
                            ->label('الحالة')
                            ->badge()
                            ->formatStateUsing(function ($state) {
                                return static::tripStatusLabel($state);
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
                            ->label('نوع الرحلة')
                            ->badge()
                            ->formatStateUsing(fn ($state) => static::tripTypeLabel($state))
                            ->color(fn ($state) => match($state?->value ?? null) {
                                TripType::immediate->value => 'info',
                                TripType::scheduled->value => 'primary',
                                default => 'gray',
                            }),
                        TextEntry::make('paymentMethod.name')
                            ->label('طريقة الدفع')
                            ->badge()
                            ->color('success'),
                        TextEntry::make('zone.name')
                            ->label('المنطقة')
                            ->default('غير متاح'),
                        TextEntry::make('vehicleType.name')
                            ->label('نوع المركبة')
                            ->default('غير متاح'),
                    ])->columns(2),

                Section::make('تفاصيل الموقع')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        TextEntry::make('pickup_address')
                            ->label('عنوان نقطة الانطلاق')
                            ->columnSpanFull(),
                       
                        TextEntry::make('dropoff_address')
                            ->label('عنوان نقطة الوصول')
                            ->columnSpanFull(),
                       
                    ])->columns(2),

                Section::make('مقاييس الرحلة')
                    ->icon('heroicon-o-chart-bar')
                    ->schema([
                        TextEntry::make('distance')
                            ->label('المسافة')
                            ->formatStateUsing(fn ($state) => $state ? $state . ' km' : 'غير متاح')
                            ->icon('heroicon-o-map'),
                        TextEntry::make('estimated_duration')
                            ->label('المدة المقدرة')
                            ->formatStateUsing(fn ($state) => $state ? $state . ' دقائق' : 'غير متاح')
                            ->icon('heroicon-o-clock'),
                        TextEntry::make('actual_duration')
                            ->label('المدة الفعلية')
                            ->visible(fn($record) => $record->status === TripStatus::COMPLETED)
                            ->formatStateUsing(fn ($state) => $state ? $state . ' دقائق' : 'غير متاح')
                            ->icon('heroicon-o-clock'),
                        TextEntry::make('estimated_fare')
                            ->label('الأجرة المقدرة')
                            ->money('SAR'),
                        TextEntry::make('actual_fare')
                            ->label('الأجرة الفعلية (ما يدفعه العميل)')
                            ->money('SAR')
                            ->getStateUsing(fn ($record) => $record->payment?->final_amount ?? $record->actual_fare)
                            ->visible(fn($record) => $record->status === TripStatus::COMPLETED || $record->payment !== null),
                        TextEntry::make('waiting_fee')
                            ->label('رسوم الانتظار')
                            ->money('SAR')
                            ->visible(fn($record) => $record->status === TripStatus::COMPLETED),
                        TextEntry::make('cancellation_fee')
                            ->label('رسوم الإلغاء')
                            ->money('SAR')
                            ->visible(fn ($record) => $record->cancellation_fee > 0 && $record->status === TripStatus::COMPLETED),
                    ])->columns(3),

                Section::make('التفاصيل المالية')
                    ->icon('heroicon-o-currency-dollar')
                    ->schema([
                        TextEntry::make('gross_amount')
                            ->label('المبلغ الإجمالي (قبل الكوبون)')
                            ->money('SAR')
                            ->getStateUsing(fn ($record) => $record->payment?->total_amount)
                            ->default('غير متاح')
                            ->visible(fn ($record) => $record->payment !== null),
                        TextEntry::make('final_amount')
                            ->label('المبلغ النهائي (بعد الكوبون)')
                            ->money('SAR')
                            ->getStateUsing(fn ($record) => $record->payment?->final_amount ?? $record->payment?->total_amount)
                            ->default('غير متاح')
                            ->visible(fn ($record) => $record->payment !== null),
                        TextEntry::make('coupon_discount')
                            ->label('خصم الكوبون')
                            ->money('SAR')
                            ->getStateUsing(fn ($record) => $record->payment?->coupon_discount ?? 0)
                            ->default(0)
                            ->visible(fn ($record) => $record->payment && ($record->payment->coupon_discount ?? 0) > 0),
                        TextEntry::make('commission_rate')
                            ->label('نسبة العمولة')
                            ->suffix('%')
                            ->getStateUsing(fn ($record) => $record->payment?->commission_rate)
                            ->default('غير متاح')
                            ->visible(fn ($record) => $record->payment !== null),
                        TextEntry::make('commission_amount')
                            ->label('عمولة المنصة')
                            ->money('SAR')
                            ->getStateUsing(fn ($record) => $record->payment?->commission_amount)
                            ->default('غير متاح')
                            ->visible(fn ($record) => $record->payment !== null),
                        TextEntry::make('driver_earning')
                            ->label('أرباح السائق')
                            ->money('SAR')
                            ->getStateUsing(fn ($record) => $record->payment?->driver_earning)
                            ->default('غير متاح')
                            ->visible(fn ($record) => $record->payment !== null),
                        TextEntry::make('additional_fees')
                            ->label('رسوم إضافية')
                            ->money('SAR')
                            ->getStateUsing(fn ($record) => $record->payment?->additional_fees ?? 0)
                            ->default(0)
                            ->visible(fn ($record) => $record->payment && ($record->payment->additional_fees ?? 0) > 0),
                        TextEntry::make('payment_status')
                            ->label('حالة الدفع')
                            ->badge()
                            ->getStateUsing(function ($record) {
                                $status = $record->payment?->status;
                                return match($status) {
                                    0 => 'قيد الانتظار',
                                    1 => 'مكتمل',
                                    2 => 'فشل',
                                    3 => 'مسترد',
                                    default => 'غير متاح',
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

                Section::make('الجدول الزمني للرحلة')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('طلب الرحلة')
                            ->dateTime()
                            ->since(),
                        TextEntry::make('started_at')
                            ->label('بدء الرحلة')
                            ->dateTime()
                            ->since()
                            ->visible(fn ($record) => $record->started_at !== null),
                        TextEntry::make('arrived_at')
                            ->label('وصول السائق')
                            ->dateTime()
                            ->since()
                            ->visible(fn ($record) => $record->arrived_at !== null),
                        TextEntry::make('ended_at')
                            ->label('انتهاء الرحلة')
                            ->dateTime()
                            ->since()
                            ->visible(fn ($record) => $record->ended_at !== null),
                        TextEntry::make('duration')
                            ->label('المدة الإجمالية')
                            ->getStateUsing(function ($record) {
                                if ($record->started_at && $record->ended_at) {
                                    return $record->started_at->diffForHumans($record->ended_at, true);
                                }
                                return 'غير متاح';
                            }),
                    ])->columns(2),

                Section::make('معلومات الجدولة')
                    ->icon('heroicon-o-calendar')
                    ->schema([
                        TextEntry::make('is_scheduled')
                            ->label('رحلة مجدولة')
                            ->badge()
                            ->formatStateUsing(fn (bool $state): string => $state ? 'نعم' : 'لا')
                            ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                        TextEntry::make('scheduled_date')
                            ->label('التاريخ المجدول')
                            ->date(),
                        TextEntry::make('scheduled_time')
                            ->label('الوقت المجدول')
                            ->time(),
                        TextEntry::make('scheduled_at')
                            ->label('التاريخ والوقت المجدول')
                            ->dateTime(),
                    ])->columns(2)
                    ->visible(fn ($record) => $record->is_scheduled),

                Section::make('التواريخ')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('تاريخ الإنشاء')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->label('تاريخ التحديث')
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
        return 'الرحلة';
    }

    public static function getPluralLabel(): string
    {
        return 'الرحلات';
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