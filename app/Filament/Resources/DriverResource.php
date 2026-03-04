<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DriverResource\Pages;
use App\Models\Driver;
use App\Models\User;
use App\Models\City;
use App\Models\DriverWithdrawRequest;
use App\Models\VehicleType;
use App\Models\VehicleBrand;
use App\Models\VehicleBrandModel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class DriverResource extends Resource
{
    protected static ?string $model = Driver::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('Users');
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('User Information'))
                    ->schema([
                        Forms\Components\TextInput::make('user.name')
                            ->label(__('Name'))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('user.email')
                            ->label(__('Email'))
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('user.phone')
                            ->label(__('Phone'))
                            ->tel()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('user.city_id')
                            ->label(__('City'))
                            ->relationship('user.city', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Toggle::make('user.is_active')
                            ->label(__('Active'))
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make(__('Driver Information'))
                    ->schema([
                        Forms\Components\DatePicker::make('date_of_birth')
                            ->label(__('Date of Birth'))
                            ->required(),
                        Forms\Components\TextInput::make('national_id')
                            ->label(__('National ID'))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('license_number')
                            ->label(__('License Number'))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('absher_phone')
                            ->label(__('Absher Phone'))
                            ->tel()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('status')
                            ->label(__('Driver Status'))
                            ->options([
                                0 => __('Offline'),
                                1 => __('Online'),
                            ])
                            ->default(0),
                        Forms\Components\Toggle::make('is_approved')
                            ->label(__('Approved'))
                            ->helperText(__('Once approved, this cannot be reverted'))
                            ->disabled(fn ($record) => $record?->is_approved === true)
                            ->default(false),
                    ])->columns(2),

                Forms\Components\Section::make(__('Vehicle Information'))
                    ->schema([
                        Forms\Components\Select::make('vehicle.vehicle_type_id')
                            ->label(__('Vehicle Type'))
                            ->options(VehicleType::where('active', true)->pluck('name', 'id'))
                            ->required()
                            ->reactive(),
                        Forms\Components\Select::make('vehicle.vehicle_brand_id')
                            ->label(__('Vehicle Brand'))
                            ->options(VehicleBrand::where('active', true)->pluck('name', 'id'))
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set) => $set('vehicle.vehicle_brand_model_id', null)),
                        Forms\Components\Select::make('vehicle.vehicle_brand_model_id')
                            ->label(__('Vehicle Model'))
                            ->options(function (callable $get) {
                                $vehicleBrandId = $get('vehicle.vehicle_brand_id');
                                if (!$vehicleBrandId) {
                                    return [];
                                }
                                return VehicleBrandModel::where('vehicle_brand_id', $vehicleBrandId)
                                    ->where('active', true)
                                    ->pluck('name', 'id');
                            })
                            ->required()
                            ->reactive()
                            ->searchable(),
                        Forms\Components\TextInput::make('vehicle.color')
                            ->label(__('Vehicle Color'))
                            ->maxLength(255),
                        Forms\Components\TextInput::make('vehicle.license_number')
                            ->label(__('Vehicle License Number'))
                            ->maxLength(255),
                        Forms\Components\TextInput::make('vehicle.plate_number')
                            ->label(__('Plate Number'))
                            ->maxLength(255),
                        Forms\Components\TextInput::make('vehicle.seats')
                            ->label(__('Number of Seats'))
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(50),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.phone')
                    ->label(__('Phone'))
                    ->searchable(),
                TextColumn::make('user.email')
                    ->label(__('Email'))
                    ->searchable(),
                TextColumn::make('user.city.name')
                    ->label(__('City'))
                    ->sortable(),
                BooleanColumn::make('user.is_active')
                    ->label(__('Active'))
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle'),
                BadgeColumn::make('is_approved')
                    ->label(__('Approval Status'))
                    ->formatStateUsing(fn (bool $state): string => $state ? __('Approved') : __('Pending'))
                    ->colors([
                        'success' => true,
                        'warning' => false,
                    ]),
                BadgeColumn::make('status')
                    ->label(__('Driver Status'))
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        '0' => __('Offline'),
                        '1' => __('Online'),
                        default => __('Unknown'),
                    })
                    ->colors([
                        'danger' => '0',
                        'success' => '1',
                    ]),
                
                TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label(__('Active Status'))
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            fn (Builder $query, $value): Builder => $query->whereHas('user', function ($q) use ($value) {
                                $q->where('is_active', $value);
                            }),
                        );
                    })
                    ->options([
                        1 => __('Active'),
                        0 => __('Inactive'),
                    ]),
                SelectFilter::make('driver_status')
                    ->label(__('Driver Status'))
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            fn (Builder $query, $value): Builder => $query->where('status', $value),
                        );
                    })
                    ->options([
                        0 => __('Offline'),
                        1 => __('Online'),
                    ]),
                SelectFilter::make('approval_status')
                    ->label(__('Approval Status'))
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'] !== null,
                            fn (Builder $query, $value): Builder => $query->where('is_approved', $data['value']),
                        );
                    })
                    ->options([
                        1 => __('Approved'),
                        0 => __('Pending'),
                    ]),
                SelectFilter::make('city')
                    ->label(__('City'))
                    ->relationship('user.city', 'name')
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve_driver')
                    ->label(__('Approve Driver'))
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn ($record) => !$record->is_approved)
                    ->form([
                        Forms\Components\Select::make('vehicle_type_id')
                            ->label(__('Vehicle Type'))
                            ->options(VehicleType::where('active', true)->pluck('name', 'id'))
                            ->required()
                            ->helperText(__('Please select a vehicle type for this driver when approving them.'))
                    ])
                    ->action(function ($record, array $data) {
                        try {
                            // Approve the driver (one-time action)
                            $approved = $record->approve();
                            
                            if (!$approved) {
                                \Filament\Notifications\Notification::make()
                                    ->title(__('Driver is already approved'))
                                    ->warning()
                                    ->send();
                                return;
                            }
                            
                            // Update vehicle type if provided and driver has a vehicle
                            if (!empty($data['vehicle_type_id']) && $record->vehicle) {
                                $record->vehicle->update(['vehicle_type_id' => $data['vehicle_type_id']]);
                            } elseif (!empty($data['vehicle_type_id']) && !$record->vehicle) {
                                // Create vehicle if doesn't exist
                                $record->vehicle()->create([
                                    'vehicle_type_id' => $data['vehicle_type_id'],
                                    'seats' => 4, // Default seats
                                    'color' => null,
                                    'license_number' => null,
                                    'plate_number' => null,
                                    'vehicle_brand_model_id' => null,
                                ]);
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->title(__('Driver approved successfully'))
                                ->body(__('Driver can now receive trip requests when active and online.'))
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title(__('Error'))
                                ->body(__('An error occurred: :error', ['error' => $e->getMessage()]))
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading(__('Approve Driver'))
                    ->modalDescription(__('Are you sure you want to approve this driver? This action cannot be undone.'))
                    ->modalSubmitActionLabel(__('Approve Driver')),
               
                    Tables\Actions\Action::make('toggle_active')
                    ->label(fn ($record) => $record->user->is_active ? __('Deactivate') : __('Activate'))
                    ->icon(fn ($record) => $record->user->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->user->is_active ? 'danger' : 'success')
                    ->action(function ($record) {
                        try {
                            $record->user->update(['is_active' => !$record->user->is_active]);
                            if (!$record->user->is_active) {
                                $record->update(['status' => 0]); // Set driver offline when deactivated
                                $record->user->fcmTokens()->delete();
                                $record->user->tokens()->delete();
                            }
                            $message = $record->user->is_active ? __('Driver activated successfully') : __('Driver deactivated successfully');
                                
                            \Filament\Notifications\Notification::make()
                                ->title($message)
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title(__('Error'))
                                ->body(__('An error occurred: :error', ['error' => $e->getMessage()]))
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record) => $record->user->is_active ? __('Deactivate Driver') : __('Activate Driver'))
                    ->modalDescription(fn ($record) => $record->user->is_active 
                        ? __('Are you sure you want to deactivate this driver? They will not be able to login.')
                        : __('Are you sure you want to activate this driver account?'))
                    ->modalSubmitActionLabel(fn ($record) => $record->user->is_active ? __('Deactivate') : __('Activate')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make(__('Personal Information'))
                    ->icon('heroicon-o-user')
                    ->schema([
                        TextEntry::make('user.name')
                            ->label(__('Name'))
                            ->icon('heroicon-o-user'),
                        TextEntry::make('user.email')
                            ->label(__('Email'))
                            ->icon('heroicon-o-envelope')
                            ->copyable(),
                        TextEntry::make('user.phone')
                            ->label(__('Phone'))
                            ->icon('heroicon-o-phone')
                            ->copyable(),
                        TextEntry::make('user.city.name')
                            ->label(__('City'))
                            ->icon('heroicon-o-map-pin'),
                        TextEntry::make('user.is_active')
                            ->label(__('Account Status'))
                            ->badge()
                            ->formatStateUsing(fn (?bool $state): string => $state ? __('Active') : __('Inactive'))
                            ->color(fn (?bool $state): string => $state ? 'success' : 'danger'),
                    ])->columns(2),

                Section::make(__('Driver Information'))
                    ->icon('heroicon-o-identification')
                    ->schema([
                        TextEntry::make('date_of_birth')
                            ->label(__('Date of Birth'))
                            ->date(),
                        TextEntry::make('national_id')
                            ->label(__('National ID'))
                            ->copyable(),
                        TextEntry::make('license_number')
                            ->label(__('License Number'))
                            ->copyable(),
                        TextEntry::make('absher_phone')
                            ->label(__('Absher Phone'))
                            ->copyable(),
                        TextEntry::make('is_approved')
                            ->label(__('Approval Status'))
                            ->badge()
                            ->formatStateUsing(fn (?bool $state): string => $state ? __('Approved') : __('Pending Approval'))
                            ->color(fn (?bool $state): string => $state ? 'success' : 'warning'),
                        TextEntry::make('status')
                            ->label(__('Driver Status'))
                            ->badge()
                            ->formatStateUsing(fn (?int $state): string => match ($state) {
                                0 => __('Offline'),
                                1 => __('Online'),
                                default => __('Unknown'),
                            })
                            ->color(fn (?int $state): string => match ($state) {
                                0 => 'danger',
                                1 => 'success',
                                default => 'gray',
                            }),
                    ])->columns(2),

                Section::make(__('Vehicle Information'))
                    ->icon('heroicon-o-truck')
                    ->schema([
                        TextEntry::make('vehicle.vehicleType.name')
                            ->label(__('Vehicle Type'))
                            ->placeholder(__('No vehicle type')),
                        TextEntry::make('vehicle.vehicleBrandModel.vehicleBrand.name')
                            ->label(__('Vehicle Brand'))
                            ->placeholder(__('No vehicle brand')),
                        TextEntry::make('vehicle.vehicleBrandModel.name')
                            ->label(__('Vehicle Model'))
                            ->placeholder(__('No vehicle model')),
                        TextEntry::make('vehicle.color')
                            ->label(__('Color'))
                            ->placeholder(__('No color specified')),
                        TextEntry::make('vehicle.plate_number')
                            ->label(__('Plate Number'))
                            ->copyable()
                            ->placeholder(__('No plate number')),
                        TextEntry::make('vehicle.license_number')
                            ->label(__('Vehicle License'))
                            ->copyable()
                            ->placeholder(__('No license number')),
                        TextEntry::make('vehicle.seats')
                            ->label(__('Number of Seats'))
                            ->placeholder(__('No seats specified')),
                    ])->columns(2),

                Section::make(__('Statistics'))
                    ->icon('heroicon-o-chart-bar')
                    ->schema([
                        TextEntry::make('trips_count')
                            ->label(__('Total Trips'))
                            ->formatStateUsing(function ($record) {
                                return $record->trips()->count() ?? 0;
                            }),
                        TextEntry::make('ratings_count')
                            ->label(__('Total Ratings'))
                            ->formatStateUsing(function ($record) {
                                return $record->ratings()->count() ?? 0;
                            }),
                        TextEntry::make('average_rating')
                            ->label(__('Average Rating'))
                            ->formatStateUsing(function ($record) {
                                $avgRating = $record->averageRating();
                                return $avgRating ? number_format($avgRating, 2) . ' ★' : __('No ratings yet');
                            }),
                        TextEntry::make('user.created_at')
                            ->label(__('Joined Date'))
                            ->dateTime(),
                    ])->columns(2),
            ]);
    }

    public static function getRelations(): array
    {
        return [
          
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDrivers::route('/'),
            'create' => Pages\CreateDriver::route('/create'),
            'view' => Pages\ViewDriver::route('/{record}'),
            'edit' => Pages\EditDriver::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('user.roles', function ($query) {
                $query->where('roles.name', 'driver');
            })
            ->with(['user', 'user.city', 'vehicle']);
    }

    public static function getLabel(): string
    {
        return __('Driver');
    }

    public static function getPluralLabel(): string
    {
        return __('Drivers');
    }
}