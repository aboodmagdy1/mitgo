<?php

namespace App\Filament\Resources\DriverResource\Pages;

use App\Filament\Resources\DriverResource;
use App\Models\DriverWithdrawRequest;
use App\Models\VehicleType;
use App\Services\TripRequestLogService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Tabs;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Facades\Auth;

class ViewDriver extends ViewRecord
{
    protected static string $resource = DriverResource::class;

    protected ?string $maxContentWidth = 'full';

    public ?array $driverRequestRates = null;

    public function loadDriverRequestRates(): void
    {
        if ($this->record) {
            $this->driverRequestRates = app(TripRequestLogService::class)->getDriverRates($this->record->id);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            
            // Approve Driver Action
            Actions\Action::make('approve_driver')
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
                            Notification::make()
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
                        
                        Notification::make()
                            ->title(__('Driver approved successfully'))
                            ->body(__('Driver can now receive trip requests when active and online.'))
                            ->success()
                            ->send();
                            
                        // Refresh the page data
                        $this->refreshFormData(['record']);
                    } catch (\Exception $e) {
                        Notification::make()
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
            
            // Toggle Active/Deactivate Action
            Actions\Action::make('toggle_active')
                ->label(fn ($record) => $record->user->is_active ? __('Deactivate') : __('Activate'))
                ->icon(fn ($record) => $record->user->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                ->color(fn ($record) => $record->user->is_active ? 'danger' : 'success')
                ->action(function ($record) {
                    try {
                        $record->user->update(['is_active' => !$record->user->is_active]);
                        $message = $record->user->is_active ? __('Driver activated successfully') : __('Driver deactivated successfully');
                            
                        Notification::make()
                            ->title($message)
                            ->success()
                            ->send();
                            
                        // Refresh the page data
                        $this->refreshFormData(['record']);
                    } catch (\Exception $e) {
                        Notification::make()
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
            
            // Withdraw Action
            Actions\Action::make('withdraw')
                ->label(__('wallet.withdraw'))
                ->icon('heroicon-o-minus-circle')
                ->color('danger')
                ->form([
                    Forms\Components\TextInput::make('amount')
                        ->label(__('wallet.amount'))
                        ->numeric()
                        ->required()
                        ->step(0.01)
                        ->minValue(0.01)
                        ->prefix('SAR')
                        ->helperText(function () {
                            $balance = $this->record->getFormattedBalanceAttribute();
                            return __('wallet.current_balance') . ': ' . $balance;
                        }),
                    Forms\Components\Textarea::make('notes')
                        ->label(__('wallet.notes'))
                        ->placeholder(__('wallet.reason_for_withdrawal') . '...')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    $driver = $this->record;
                    
                    $amount = $data['amount'] * 100; // Convert to halalas
                    
                    if (!$driver->canWithdraw($amount)) {
                        Notification::make()
                            ->title(__('wallet.insufficient_balance'))
                            ->body(__('Driver does not have sufficient balance for this withdrawal.'))
                            ->danger()
                            ->send();
                        return;
                    }

                    try {
                        $driver->withdraw($amount, [
                            'description' => 'Admin withdrawal',
                            'notes' => $data['notes'],
                            'admin_id' => Auth::user()?->id,
                        ]);

                        Notification::make()
                            ->title(__('wallet.withdrawal_successful'))
                            ->body(__('Amount :amount has been withdrawn from driver wallet.', [
                                'amount' => $data['amount'] . ' SAR'
                            ]))
                            ->success()
                            ->send();
                            
                        $this->refreshFormData(['driver']);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title(__('Error Processing Withdrawal'))
                            ->body(__('An error occurred: :error', ['error' => $e->getMessage()]))
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->modalHeading(__('wallet.withdraw_from_wallet'))
                ->modalDescription(__('This will immediately deduct the amount from the driver\'s wallet.'))
                ->modalSubmitActionLabel(__('wallet.withdraw')),

            // Deposit Action
            Actions\Action::make('deposit')
                ->label(__('wallet.deposit'))
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->form([
                    Forms\Components\TextInput::make('amount')
                        ->label(__('wallet.amount'))
                        ->numeric()
                        ->required()
                        
                        ->prefix('SAR'),
                    Forms\Components\Textarea::make('notes')
                        ->label(__('wallet.notes'))
                        ->placeholder(__('wallet.reason_for_deposit') . '...')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    $driver = $this->record;
                    
                    $amount = $data['amount'] * 100; // Convert to halalas

                    try {
                        $driver->deposit($amount, [
                            'description' => 'Admin deposit',
                            'notes' => $data['notes'],
                            'admin_id' => Auth::user()?->id,
                        ]);

                        Notification::make()
                            ->title(__('wallet.deposit_successful'))
                            ->body(__('Amount :amount has been deposited to driver wallet.', [
                                'amount' => $data['amount'] . ' SAR'
                            ]))
                            ->success()
                            ->send();
                            
                        $this->refreshFormData(['driver']);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title(__('Error Processing Deposit'))
                            ->body(__('An error occurred: :error', ['error' => $e->getMessage()]))
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->modalHeading(__('wallet.deposit_to_wallet'))
                ->modalDescription(__('This will immediately add the amount to the driver\'s wallet.'))
                ->modalSubmitActionLabel(__('wallet.deposit')),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
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
                                        \Filament\Infolists\Components\ViewEntry::make('trip_request_rates')
                                            ->label(__('Trip Request Rates'))
                                            ->view('filament.infolists.components.driver-request-rates')
                                            ->viewData(['driverRequestRates' => $this->driverRequestRates])
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
                                                try {
                                                    // Force fresh query to avoid any caching issues
                                                    $pendingAmount = \App\Models\DriverWithdrawRequest::where('driver_id', $record->id)
                                                        ->where('is_approved', false)
                                                        ->sum('amount') ?? 0;
                                                    
                                                    return number_format((float)$pendingAmount, 2) . ' ' . config('app.currency', 'SAR');
                                                } catch (\Exception $e) {
                                                    return 'Error: ' . $e->getMessage();
                                                }
                                            })
                                            ->badge()
                                            ->color('warning'),
                                        TextEntry::make('total_withdrawals')
                                            ->label(__('Total Approved Withdrawals'))
                                            ->state(function ($record) {
                                                try {
                                                    // Force fresh query to avoid any caching issues
                                                    $approvedAmount = \App\Models\DriverWithdrawRequest::where('driver_id', $record->id)
                                                        ->where('is_approved', true)
                                                        ->sum('amount') ?? 0;
                                                    
                                                    return number_format((float)$approvedAmount, 2) . ' ' . config('app.currency', 'SAR');
                                                } catch (\Exception $e) {
                                                    return 'Error: ' . $e->getMessage();
                                                }
                                            })
                                            ->badge()
                                            ->color('info'),
                                    ])->columns(3),

                                Section::make(__('wallet.recent_transactions'))
                                    ->icon('heroicon-o-list-bullet')
                                    ->columnSpanFull()
                                    ->collapsible()
                                    ->schema([
                                        \Filament\Infolists\Components\ViewEntry::make('transactions')
                                            ->view('filament.infolists.components.wallet-transactions')
                                            ->state(function ($record) {
                                                return $record->getWalletTransactions()
                                                    ->latest()
                                                    ->limit(10)
                                                    ->get();
                                            }),
                                    ]),

                                Section::make(__('wallet.withdraw_requests'))
                                    ->icon('heroicon-o-minus-circle')
                                    ->columnSpanFull()
                                    ->collapsible()
                                    ->schema([
                                        \Filament\Infolists\Components\ViewEntry::make('withdraw_requests')
                                            ->view('filament.infolists.components.withdraw-requests')
                                            ->state(function ($record) {
                                                return $record->withdrawRequests()
                                                    ->latest()
                                                    ->limit(10)
                                                    ->get();
                                            }),
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
                                        \Filament\Infolists\Components\ViewEntry::make('trips')
                                            ->label('')
                                            ->view('filament.infolists.components.driver-trips')
                                            ->state(function ($record) {
                                                return $record->trips()
                                                    ->with(['payment', 'rate'])
                                                    ->latest()
                                                    ->limit(15)
                                                    ->get();
                                            }),
                                    ]),
                            ]),
                    ])
            ]);
    }
}
