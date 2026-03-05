<?php

namespace App\Filament\Resources;

use App\Enums\ApprovalStatus;
use App\Models\Driver;
use App\Models\VehicleType;
use App\Models\VehicleBrand;
use App\Models\VehicleBrandModel;
use App\Services\TripRequestLogService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

abstract class BaseDriverResource extends Resource
{
    protected static ?string $model = Driver::class;

    public static function getNavigationGroup(): ?string
    {
        return 'أقسام السائق';
    }

    /*
    |--------------------------------------------------------------------------
    | Base query — all entries must have the driver role
    |--------------------------------------------------------------------------
    */

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('user.roles', function ($query) {
                $query->where('roles.name', 'driver');
            })
            ->with(['user', 'user.city', 'vehicle']);
    }

    /*
    |--------------------------------------------------------------------------
    | Shared Form
    |--------------------------------------------------------------------------
    */

    public static function form(Form $form): Form
    {
        return $form->schema([
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
                        ->options([0 => __('Offline'), 1 => __('Online')])
                        ->default(0),
                    Forms\Components\Select::make('approval_status')
                        ->label(__('Approval Status'))
                        ->options([
                            ApprovalStatus::PENDING->value     => __('Pending'),
                            ApprovalStatus::IN_PROGRESS->value => __('In Progress'),
                            ApprovalStatus::APPROVED->value    => __('Approved'),
                            ApprovalStatus::REJECTED->value    => __('Rejected'),
                        ])
                        ->default(ApprovalStatus::PENDING->value)
                        ->required(),
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
                            if (! $vehicleBrandId) {
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

    /*
    |--------------------------------------------------------------------------
    | Shared Table
    |--------------------------------------------------------------------------
    */

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
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('user.gender')
                    ->label(__('Gender'))
                    ->formatStateUsing(fn ($state) => $state === 'male' ? __('Male') : ($state === 'female' ? __('Female') : '-'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('user.city.name')
                    ->label(__('City'))
                    ->sortable(),
                BooleanColumn::make('user.is_active')
                    ->label(__('Active'))
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle'),
                BadgeColumn::make('approval_status')
                    ->label(__('Approval Status'))
                    ->formatStateUsing(fn ($state): string => self::normalizeApprovalStatus($state)->label())
                    ->colors([
                        'warning' => fn ($state) => self::normalizeApprovalStatus($state) === ApprovalStatus::PENDING,
                        'info'    => fn ($state) => self::normalizeApprovalStatus($state) === ApprovalStatus::IN_PROGRESS,
                        'success' => fn ($state) => self::normalizeApprovalStatus($state) === ApprovalStatus::APPROVED,
                        'danger'  => fn ($state) => self::normalizeApprovalStatus($state) === ApprovalStatus::REJECTED,
                    ]),
                BadgeColumn::make('status')
                    ->label(__('Driver Status'))
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        '0'     => __('Offline'),
                        '1'     => __('Online'),
                        default => __('Unknown'),
                    })
                    ->colors([
                        'danger'  => '0',
                        'success' => '1',
                    ]),
                TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('city')
                    ->label(__('City'))
                    ->relationship('user.city', 'name')
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => $record->isApproved()),

                // Move to In Progress — visible only for pending drivers
                Tables\Actions\Action::make('move_to_in_progress')
                    ->label(__('Move to Inspection'))
                    ->icon('heroicon-o-magnifying-glass')
                    ->color('info')
                    ->visible(fn ($record) => $record->isPendingApproval())
                    ->requiresConfirmation()
                    ->modalHeading(__('Move to Inspection'))
                    ->modalDescription(__('Are you sure you want to move this driver to the inspection stage?'))
                    ->action(function ($record) {
                        $record->moveToInProgress();
                        \Filament\Notifications\Notification::make()
                            ->title(__('Driver moved to inspection stage.'))
                            ->info()
                            ->send();
                    }),

                // Approve — visible only for in-progress drivers
                Tables\Actions\Action::make('approve_driver')
                    ->label(__('Approve Driver'))
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn ($record) => $record->isInProgress())
                    ->form([
                        Forms\Components\Select::make('vehicle_type_id')
                            ->label(__('Vehicle Type'))
                            ->options(VehicleType::where('active', true)->pluck('name', 'id'))
                            ->required()
                            ->helperText(__('Please select a vehicle type for this driver when approving them.')),
                    ])
                    ->action(function ($record, array $data) {
                        $approved = $record->approve();
                        if (! $approved) {
                            \Filament\Notifications\Notification::make()
                                ->title(__('Driver is already approved'))
                                ->warning()
                                ->send();
                            return;
                        }
                        if (! empty($data['vehicle_type_id'])) {
                            if ($record->vehicle) {
                                $record->vehicle->update(['vehicle_type_id' => $data['vehicle_type_id']]);
                            } else {
                                $record->vehicle()->create([
                                    'vehicle_type_id'        => $data['vehicle_type_id'],
                                    'seats'                  => 4,
                                    'color'                  => null,
                                    'license_number'         => null,
                                    'plate_number'           => null,
                                    'vehicle_brand_model_id' => null,
                                ]);
                            }
                        }
                        // Activate user on approval
                        $record->user->update(['is_active' => true]);
                        \Filament\Notifications\Notification::make()
                            ->title(__('Driver approved successfully'))
                            ->body(__('Driver can now receive trip requests when active and online.'))
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading(__('Approve Driver'))
                    ->modalDescription(__('Are you sure you want to approve this driver? This action cannot be undone.'))
                    ->modalSubmitActionLabel(__('Approve Driver')),

                // Reject — visible only for in-progress drivers
                Tables\Actions\Action::make('reject_driver')
                    ->label(__('Reject'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->isInProgress())
                    ->requiresConfirmation()
                    ->modalHeading(__('Reject Driver'))
                    ->modalDescription(__('Are you sure you want to reject this driver application?'))
                    ->action(function ($record) {
                        $record->reject();
                        \Filament\Notifications\Notification::make()
                            ->title(__('Driver application rejected.'))
                            ->danger()
                            ->send();
                    }),

                // Toggle active — visible only for approved drivers
                Tables\Actions\Action::make('toggle_active')
                    ->label(fn ($record) => $record->user->is_active ? __('Deactivate') : __('Activate'))
                    ->icon(fn ($record) => $record->user->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->user->is_active ? 'danger' : 'success')
                    ->visible(fn ($record) => $record->isApproved())
                    ->action(function ($record) {
                        $record->user->update(['is_active' => ! $record->user->is_active]);
                        if (! $record->user->is_active) {
                            $record->update(['status' => 0]);
                            $record->user->fcmTokens()->delete();
                            $record->user->tokens()->delete();
                        }
                        $message = $record->user->is_active
                            ? __('Driver activated successfully')
                            : __('Driver deactivated successfully');
                        \Filament\Notifications\Notification::make()
                            ->title($message)
                            ->success()
                            ->send();
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

    /*
    |--------------------------------------------------------------------------
    | Shared Infolist
    |--------------------------------------------------------------------------
    */

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->columns(1)
            ->schema([
                Tabs::make('Driver Details')
                    ->contained(false)
                    ->columnSpanFull()
                    ->tabs([
                        Tabs\Tab::make(__('General Information'))
                            ->icon('heroicon-o-user')
                            ->schema([
                                Section::make(__('Personal Information'))
                                    ->icon('heroicon-o-user')
                                    ->schema([
                                        SpatieMediaLibraryImageEntry::make('user.avatar')
                                            ->label(__('Avatar'))
                                            ->collection('avatar')
                                            ->columnSpan(2),
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
                                        TextEntry::make('user.gender')
                                            ->label(__('Gender'))
                                            ->formatStateUsing(fn ($state) => $state === 'male' ? __('Male') : ($state === 'female' ? __('Female') : '-')),
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
                                        TextEntry::make('approval_status')
                                            ->label(__('Approval Status'))
                                            ->badge()
                                            ->formatStateUsing(fn ($state): string => self::normalizeApprovalStatus($state)->label())
                                            ->color(fn ($state): string => self::normalizeApprovalStatus($state)->color()),
                                        TextEntry::make('status')
                                            ->label(__('Driver Status'))
                                            ->badge()
                                            ->formatStateUsing(fn (?int $state): string => match ($state) {
                                                0       => __('Offline'),
                                                1       => __('Online'),
                                                default => __('Unknown'),
                                            })
                                            ->color(fn (?int $state): string => match ($state) {
                                                0       => 'danger',
                                                1       => 'success',
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
                                            ->formatStateUsing(fn ($record) => $record->trips()->count() ?? 0),
                                        TextEntry::make('ratings_count')
                                            ->label(__('Total Ratings'))
                                            ->formatStateUsing(fn ($record) => $record->ratings()->count() ?? 0),
                                        TextEntry::make('average_rating')
                                            ->label(__('Average Rating'))
                                            ->formatStateUsing(function ($record) {
                                                $avg = $record->averageRating();
                                                return $avg ? number_format($avg, 2) . ' ★' : __('No ratings yet');
                                            }),
                                        ViewEntry::make('trip_request_rates')
                                            ->label(__('Trip Request Rates'))
                                            ->view('filament.infolists.components.driver-request-rates')
                                            ->state(fn ($record) => app(TripRequestLogService::class)->getDriverRates($record->id))
                                            ->columnSpanFull(),
                                        TextEntry::make('user.created_at')
                                            ->label(__('Joined Date'))
                                            ->dateTime(),
                                    ])->columns(2),
                            ]),

                        Tabs\Tab::make(__('Wallet & Transactions'))
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                Section::make(__('wallet.wallet_information'))
                                    ->icon('heroicon-o-banknotes')
                                    ->columnSpanFull()
                                    ->schema([
                                        TextEntry::make('formatted_balance')
                                            ->label(__('Current Balance'))
                                            ->badge()
                                            ->color('success')
                                            ->size(TextEntry\TextEntrySize::Large)
                                            ->weight(FontWeight::Bold),
                                        TextEntry::make('pending_withdrawals')
                                            ->label(__('Pending Withdrawals'))
                                            ->state(function ($record) {
                                                $amount = \App\Models\DriverWithdrawRequest::where('driver_id', $record->id)
                                                    ->where('is_approved', false)->sum('amount') ?? 0;
                                                return number_format((float) $amount, 2) . ' ' . config('app.currency', 'SAR');
                                            })
                                            ->badge()
                                            ->color('warning'),
                                        TextEntry::make('total_withdrawals')
                                            ->label(__('Total Approved Withdrawals'))
                                            ->state(function ($record) {
                                                $amount = \App\Models\DriverWithdrawRequest::where('driver_id', $record->id)
                                                    ->where('is_approved', true)->sum('amount') ?? 0;
                                                return number_format((float) $amount, 2) . ' ' . config('app.currency', 'SAR');
                                            })
                                            ->badge()
                                            ->color('info'),
                                    ])->columns(3),

                                Section::make(__('wallet.recent_transactions'))
                                    ->icon('heroicon-o-list-bullet')
                                    ->columnSpanFull()
                                    ->collapsible()
                                    ->schema([
                                        ViewEntry::make('transactions')
                                            ->view('filament.infolists.components.wallet-transactions')
                                            ->state(fn ($record) => $record->getWalletTransactions()->latest()->limit(10)->get()),
                                    ]),

                                Section::make(__('wallet.withdraw_requests'))
                                    ->icon('heroicon-o-minus-circle')
                                    ->columnSpanFull()
                                    ->collapsible()
                                    ->schema([
                                        ViewEntry::make('withdraw_requests')
                                            ->view('filament.infolists.components.withdraw-requests')
                                            ->state(fn ($record) => $record->withdrawRequests()->latest()->limit(10)->get()),
                                    ]),
                            ]),

                        Tabs\Tab::make(__('Driver Trips'))
                            ->icon('heroicon-o-map')
                            ->schema([
                                Section::make(__('Driver Trips'))
                                    ->icon('heroicon-o-map')
                                    ->columnSpanFull()
                                    ->collapsible()
                                    ->schema([
                                        ViewEntry::make('trips')
                                            ->label('')
                                            ->view('filament.infolists.components.driver-trips')
                                            ->state(fn ($record) => $record->trips()
                                                ->with(['payment', 'rate.ratingComment'])
                                                ->latest()
                                                ->limit(15)
                                                ->get()),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public static function getLabel(): string
    {
        return __('Driver');
    }

    public static function getPluralLabel(): string
    {
        return __('Drivers');
    }

    /**
     * Normalize $state to an ApprovalStatus enum instance.
     * Filament passes the already-cast enum object when the model has a cast,
     * but may pass a raw int/string in other contexts (e.g. filters).
     */
    public static function normalizeApprovalStatus(mixed $state): ApprovalStatus
    {
        return $state instanceof ApprovalStatus
            ? $state
            : ApprovalStatus::from((int) $state);
    }
}
