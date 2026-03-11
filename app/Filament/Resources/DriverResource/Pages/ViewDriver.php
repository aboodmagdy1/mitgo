<?php

namespace App\Filament\Resources\DriverResource\Pages;

use App\Enums\ApprovalStatus;
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
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Tabs;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Facades\Auth;

class ViewDriver extends ViewRecord
{
    protected static string $resource = DriverResource::class;

    protected ?string $maxContentWidth = 'full';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            
            // Approve Driver Action
            Actions\Action::make('approve_driver')
                ->label('موافقة على السائق')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn ($record) => ! $record->isApproved())
                ->form([
                    Forms\Components\Select::make('vehicle_type_id')
                        ->label('نوع المركبة')
                        ->options(VehicleType::where('active', true)->pluck('name', 'id'))
                        ->required()
                        ->helperText('يرجى اختيار نوع المركبة لهذا السائق عند الموافقة عليه.')
                ])
                ->action(function ($record, array $data) {
                    try {
                        // Approve the driver (one-time action)
                        $approved = $record->approve();
                        
                        if (!$approved) {
                            Notification::make()
                                ->title('السائق موافق عليه مسبقاً')
                                ->warning()
                                ->send();
                            return;
                        }
                        $vechileType = VehicleType::find($data['vehicle_type_id']);
                        // Update vehicle type if provided and driver has a vehicle
                        if (!empty($data['vehicle_type_id']) && $record->vehicle) {
                            $record->vehicle->update(['vehicle_type_id' => $data['vehicle_type_id'] , 'seats'=>$vechileType->seats]);
                        } elseif (!empty($data['vehicle_type_id']) && !$record->vehicle) {

                            // Create vehicle if doesn't exist
                            $record->vehicle()->create([
                                'vehicle_type_id' => $data['vehicle_type_id'],
                                'seats' =>$vechileType->seats  , // Default seats
                                'color' => null,
                                'license_number' => null,
                                'plate_number' => null,
                                'vehicle_brand_model_id' => null,
                            ]);
                        }
                        
                        Notification::make()
                            ->title('تمت الموافقة على السائق بنجاح')
                            ->body('يمكن للسائق الآن تلقي طلبات الرحلات عند كونه نشطاً ومتصلاً.')
                            ->success()
                            ->send();
                            
                        // Refresh the page data
                        $this->refreshFormData(['record']);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('خطأ')
                            ->body('حدث خطأ: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->modalHeading('موافقة على السائق')
                ->modalDescription('هل أنت متأكد من الموافقة على هذا السائق؟ لا يمكن التراجع عن هذا الإجراء.')
                ->modalSubmitActionLabel('موافقة على السائق'),
            
            // Toggle Active/Deactivate Action
            Actions\Action::make('toggle_active')
                ->label(fn ($record) => $record->user->is_active ? 'إلغاء التفعيل' : 'تفعيل')
                ->icon(fn ($record) => $record->user->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                ->color(fn ($record) => $record->user->is_active ? 'danger' : 'success')
                ->action(function ($record) {
                    try {
                        $record->user->update(['is_active' => !$record->user->is_active]);
                        $message = $record->user->is_active ? 'تم تفعيل السائق بنجاح' : 'تم إلغاء تفعيل السائق بنجاح';
                            
                        Notification::make()
                            ->title($message)
                            ->success()
                            ->send();
                            
                        // Refresh the page data
                        $this->refreshFormData(['record']);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('خطأ')
                            ->body('حدث خطأ: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->modalHeading(fn ($record) => $record->user->is_active ? 'إلغاء تفعيل السائق' : 'تفعيل السائق')
                ->modalDescription(fn ($record) => $record->user->is_active 
                    ? 'هل أنت متأكد من إلغاء تفعيل هذا السائق؟ لن يتمكن من تسجيل الدخول.'
                    : 'هل أنت متأكد من تفعيل حساب هذا السائق؟')
                ->modalSubmitActionLabel(fn ($record) => $record->user->is_active ? 'إلغاء التفعيل' : 'تفعيل'),
            
            // Withdraw Action
            Actions\Action::make('withdraw')
                ->label('سحب')
                ->icon('heroicon-o-minus-circle')
                ->color('danger')
                ->form([
                    Forms\Components\TextInput::make('amount')
                        ->label('المبلغ')
                        ->numeric()
                        ->required()
                        ->step(0.01)
                        ->minValue(0.01)
                        ->prefix('SAR')
                        ->helperText(function () {
                            $balance = $this->record->getFormattedBalanceAttribute();
                            return 'الرصيد الحالي: ' . $balance;
                        }),
                    Forms\Components\Textarea::make('notes')
                        ->label('الملاحظات')
                        ->placeholder('سبب السحب...')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    $driver = $this->record;
                    
                    $amount = $data['amount'] * 100; // Convert to halalas
                    
                    if (!$driver->canWithdraw($amount)) {
                        Notification::make()
                            ->title('رصيد غير كافٍ')
                            ->body('لا يمتلك السائق رصيداً كافياً لهذا السحب.')
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
                            ->title('تم السحب بنجاح')
                            ->body('تم سحب المبلغ ' . $data['amount'] . ' SAR من محفظة السائق.')
                            ->success()
                            ->send();
                            
                        $this->refreshFormData(['driver']);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('خطأ في معالجة السحب')
                            ->body('حدث خطأ: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->modalHeading('سحب من المحفظة')
                ->modalDescription('سيتم خصم المبلغ فوراً من محفظة السائق.')
                ->modalSubmitActionLabel('سحب'),

            // Deposit Action
            Actions\Action::make('deposit')
                ->label('إيداع')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->form([
                    Forms\Components\TextInput::make('amount')
                        ->label('المبلغ')
                        ->numeric()
                        ->required()
                        
                        ->prefix('SAR'),
                    Forms\Components\Textarea::make('notes')
                        ->label('الملاحظات')
                        ->placeholder('سبب الإيداع...')
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
                            ->title('تم الإيداع بنجاح')
                            ->body('تم إيداع المبلغ ' . $data['amount'] . ' SAR في محفظة السائق.')
                            ->success()
                            ->send();
                            
                        $this->refreshFormData(['driver']);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('خطأ في معالجة الإيداع')
                            ->body('حدث خطأ: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->modalHeading('إيداع في المحفظة')
                ->modalDescription('سيتم إضافة المبلغ فوراً إلى محفظة السائق.')
                ->modalSubmitActionLabel('إيداع'),
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
                        Tabs\Tab::make('المعلومات العامة')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Section::make('المعلومات الشخصية')
                                    ->icon('heroicon-o-user')
                                    ->schema([
                                        // driver have avatar using user->getFirstMediaUrl("avatar")
                                        SpatieMediaLibraryImageEntry::make('user.avatar')
                                            ->label('الصورة الشخصية')
                                            ->collection('avatar')
                                            ->columnSpan(2),
                                        TextEntry::make('user.name')
                                            ->label('الاسم')
                                            ->icon('heroicon-o-user'),
                                        TextEntry::make('user.email')
                                            ->label('البريد الإلكتروني')
                                            ->icon('heroicon-o-envelope')
                                            ->copyable(),
                                        TextEntry::make('user.phone')
                                            ->label('رقم الهاتف')
                                            ->icon('heroicon-o-phone')
                                            ->copyable(),
                                        TextEntry::make('user.city.name')
                                            ->label('المدينة')
                                            ->icon('heroicon-o-map-pin'),
                                        TextEntry::make('user.is_active')
                                            ->label('حالة الحساب')
                                            ->badge()
                                            ->formatStateUsing(fn (?bool $state): string => $state ? 'نشط' : 'غير نشط')
                                            ->color(fn (?bool $state): string => $state ? 'success' : 'danger'),
                                    ])->columns(2),

                                Section::make('معلومات السائق')
                                    ->icon('heroicon-o-identification')
                                    ->schema([
                                        TextEntry::make('date_of_birth')
                                            ->label('تاريخ الميلاد')
                                            ->date(),
                                        TextEntry::make('national_id')
                                            ->label('رقم الهوية')
                                            ->copyable(),
                                        
                                        TextEntry::make('absher_phone')
                                            ->label('رقم أبشر')
                                            ->copyable(),
                                        TextEntry::make('approval_status')
                                            ->label('حالة الموافقة')
                                            ->badge()
                                            ->formatStateUsing(fn ($state): string => \App\Filament\Resources\BaseDriverResource::normalizeApprovalStatus($state)->label())
                                            ->color(fn ($state): string => \App\Filament\Resources\BaseDriverResource::normalizeApprovalStatus($state)->color()),
                                        TextEntry::make('status')
                                            ->label('حالة السائق')
                                            ->badge()
                                            ->formatStateUsing(fn (?int $state): string => match ($state) {
                                                0 => 'غير متصل',
                                                1 => 'متصل',
                                                default => 'غير معروف',
                                            })
                                            ->color(fn (?int $state): string => match ($state) {
                                                0 => 'danger',
                                                1 => 'success',
                                                default => 'gray',
                                            }),
                                    ])->columns(2),

                                Section::make('معلومات المركبة')
                                    ->icon('heroicon-o-truck')
                                    ->schema([
                                        TextEntry::make('vehicle.vehicleType.name')
                                            ->label('نوع المركبة')
                                            ->placeholder('لا يوجد نوع مركبة'),
                                        TextEntry::make('vehicle.vehicleBrandModel.vehicleBrand.name')
                                            ->label('ماركة المركبة')
                                            ->placeholder('لا توجد ماركة مركبة'),
                                        TextEntry::make('vehicle.vehicleBrandModel.name')
                                            ->label('موديل المركبة')
                                            ->placeholder('لا يوجد موديل مركبة'),
                                        TextEntry::make('vehicle.color')
                                            ->label('اللون')
                                            ->placeholder('لم يتم تحديد اللون'),
                                        TextEntry::make('vehicle.plate_number')
                                            ->label('رقم اللوحة')
                                            ->copyable()
                                            ->placeholder('لا يوجد رقم لوحة'),
                                        TextEntry::make('vehicle.license_number')
                                            ->label('رخصة المركبة')
                                            ->copyable()
                                            ->placeholder('لا يوجد رقم رخصة'),
                                        TextEntry::make('vehicle.seats')
                                            ->label('عدد المقاعد')
                                            ->placeholder('لم يتم تحديد المقاعد'),
                                    ])->columns(2),

                                Section::make('الإحصائيات')
                                    ->icon('heroicon-o-chart-bar')
                                    ->schema([
                                        TextEntry::make('trips_count')
                                            ->label('إجمالي الرحلات')
                                            ->state(fn ($record) => $record->trips()->count()),
                                        TextEntry::make('ratings_count')
                                            ->label('إجمالي التقييمات')
                                            ->state(fn ($record) => $record->ratings()->count()),
                                        TextEntry::make('average_rating')
                                            ->label('متوسط التقييم')
                                            ->state(function ($record) {
                                                $avgRating = $record->averageRating();
                                                return $avgRating ? number_format($avgRating, 2) . ' ★' : 'لا توجد تقييمات بعد';
                                            }),
                                        \Filament\Infolists\Components\ViewEntry::make('trip_request_rates')
                                            ->label('معدلات طلبات الرحلات')
                                            ->view('filament.infolists.components.driver-request-rates')
                                            ->state(fn ($record) => app(TripRequestLogService::class)->getDriverRates($record->id))
                                            ->columnSpanFull(),
                                        TextEntry::make('user.created_at')
                                            ->label('تاريخ الانضمام')
                                            ->dateTime(),
                                    ])->columns(2),
                            ]),

                        Tabs\Tab::make('المحفظة والمعاملات')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                Section::make('معلومات المحفظة')
                                    ->icon('heroicon-o-banknotes')
                                    ->columnSpanFull()
                                    ->schema([
                                        TextEntry::make('formatted_balance')
                                            ->label('الرصيد الحالي')
                                            ->badge()
                                            ->color('success')
                                            ->size(TextEntry\TextEntrySize::Large)
                                            ->weight(FontWeight::Bold),
                                        TextEntry::make('pending_withdrawals')
                                            ->label('طلبات السحب المعلقة')
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
                                            ->label('إجمالي السحوبات المعتمدة')
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

                                Section::make('المعاملات الأخيرة')
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

                                Section::make('طلبات السحب')
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

                        Tabs\Tab::make('رحلات السائق')
                            ->icon('heroicon-o-map')
                            ->schema([
                                Section::make('رحلات السائق')
                                    ->icon('heroicon-o-map')
                                    ->columnSpanFull()
                                    ->collapsible()
                                    ->schema([
                                        \Filament\Infolists\Components\ViewEntry::make('trips')
                                            ->label('')
                                            ->view('filament.infolists.components.driver-trips')
                                            ->state(function ($record) {
                                                return $record->trips()
                                                    ->with(['payment', 'rate.ratingComment'])
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
