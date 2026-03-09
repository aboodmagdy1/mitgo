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
            Forms\Components\Section::make('معلومات المستخدم')
                ->schema([
                    Forms\Components\TextInput::make('user.name')
                        ->label('الاسم')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('user.email')
                        ->label('البريد الإلكتروني')
                        ->email()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('user.phone')
                        ->label('رقم الهاتف')
                        ->tel()
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Select::make('user.city_id')
                        ->label('المدينة')
                        ->relationship('user.city', 'name')
                        ->required()
                        ->searchable()
                        ->preload(),
                    Forms\Components\Toggle::make('user.is_active')
                        ->label('نشط')
                        ->default(true),
                ])->columns(2),

            Forms\Components\Section::make('معلومات السائق')
                ->schema([
                    Forms\Components\DatePicker::make('date_of_birth')
                        ->label('تاريخ الميلاد')
                        ->required(),
                    Forms\Components\TextInput::make('national_id')
                        ->label('رقم الهوية الوطنية')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('absher_phone')
                        ->label('هاتف أبشر')
                        ->tel()
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Select::make('status')
                        ->label('حالة السائق')
                        ->options([0 => 'غير متصل', 1 => 'متصل'])
                        ->default(0),
                    Forms\Components\Select::make('approval_status')
                        ->label('حالة الموافقة')
                        ->options([
                            ApprovalStatus::PENDING->value     => 'قيد الانتظار',
                            ApprovalStatus::IN_PROGRESS->value => 'قيد التنفيذ',
                            ApprovalStatus::APPROVED->value    => 'موافق عليه',
                            ApprovalStatus::REJECTED->value    => 'مرفوض',
                        ])
                        ->default(ApprovalStatus::PENDING->value)
                        ->required(),
                ])->columns(2),

            Forms\Components\Section::make('معلومات المركبة')
                ->schema([
                    Forms\Components\Select::make('vehicle.vehicle_type_id')
                        ->label('تصنيف المركبة')
                        ->options(VehicleType::where('active', true)->pluck('name', 'id'))
                        ->required()
                        ->reactive(),
                    Forms\Components\Select::make('vehicle.vehicle_brand_id')
                        ->label('ماركة المركبة')
                        ->options(VehicleBrand::where('active', true)->pluck('name', 'id'))
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(fn ($state, callable $set) => $set('vehicle.vehicle_brand_model_id', null)),
                    Forms\Components\Select::make('vehicle.vehicle_brand_model_id')
                        ->label('موديل المركبة')
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
                        ->label('لون المركبة')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('vehicle.license_number')
                        ->label('رقم رخصة المركبة')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('vehicle.plate_number')
                        ->label('رقم اللوحة')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('vehicle.seats')
                        ->label('عدد المقاعد')
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
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.phone')
                    ->label('رقم الهاتف')
                    ->searchable(),
                TextColumn::make('user.email')
                    ->label('البريد الإلكتروني')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('user.gender')
                    ->label('النوع')
                    ->formatStateUsing(fn ($state) => $state === 'male' ? 'ذكر' : ($state === 'female' ? 'أنثى' : '-'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('user.city.name')
                    ->label('المدينة')
                    ->sortable(),
                BooleanColumn::make('user.is_active')
                    ->label('نشط')
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle'),
                BadgeColumn::make('approval_status')
                    ->label('حالة الموافقة')
                    ->formatStateUsing(fn ($state): string => self::normalizeApprovalStatus($state)->label())
                    ->colors([
                        'warning' => fn ($state) => self::normalizeApprovalStatus($state) === ApprovalStatus::PENDING,
                        'info'    => fn ($state) => self::normalizeApprovalStatus($state) === ApprovalStatus::IN_PROGRESS,
                        'success' => fn ($state) => self::normalizeApprovalStatus($state) === ApprovalStatus::APPROVED,
                        'danger'  => fn ($state) => self::normalizeApprovalStatus($state) === ApprovalStatus::REJECTED,
                    ]),
                BadgeColumn::make('status')
                    ->label('حالة السائق')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        '0'     => 'غير متصل',
                        '1'     => 'متصل',
                        default => 'غير معروف',
                    })
                    ->colors([
                        'danger'  => '0',
                        'success' => '1',
                    ]),
                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('city')
                    ->label('المدينة')
                    ->relationship('user.city', 'name')
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => $record->isApproved()),

                // Move to In Progress — visible only for pending drivers
                Tables\Actions\Action::make('move_to_in_progress')
                    ->label('نقل للمعاينة')
                    ->icon('heroicon-o-magnifying-glass')
                    ->color('info')
                    ->visible(fn ($record) => $record->isPendingApproval())
                    ->requiresConfirmation()
                    ->modalHeading('نقل للمعاينة')
                    ->modalDescription('هل أنت متأكد من نقل هذا السائق لمرحلة المعاينة؟')
                    ->action(function ($record) {
                        $record->moveToInProgress();
                        \Filament\Notifications\Notification::make()
                            ->title('تم نقل السائق لمرحلة المعاينة.')
                            ->info()
                            ->send();
                    }),

                // Approve — visible only for in-progress drivers
                Tables\Actions\Action::make('approve_driver')
                    ->label('موافقة على السائق')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn ($record) => $record->isInProgress())
                    ->form([
                        Forms\Components\Select::make('vehicle_type_id')
                            ->label('تصنيف المركبة')
                            ->options(VehicleType::where('active', true)->pluck('name', 'id'))
                            ->required()
                            ->helperText('يرجى اختيار نوع المركبة لهذا السائق عند الموافقة عليه.'),
                    ])
                    ->action(function ($record, array $data) {
                        $approved = $record->approve();
                        if (! $approved) {
                            \Filament\Notifications\Notification::make()
                                ->title('السائق موافق عليه مسبقاً')
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
                            ->title('تم الموافقة على السائق بنجاح')
                            ->body('يمكن للسائق الآن استقبال طلبات الرحلات عندما يكون نشطاً ومتصلاً.')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('موافقة على السائق')
                    ->modalDescription('هل أنت متأكد من الموافقة على هذا السائق؟ لا يمكن التراجع عن هذا الإجراء.')
                    ->modalSubmitActionLabel('موافقة على السائق'),

                // Reject — visible only for in-progress drivers
                Tables\Actions\Action::make('reject_driver')
                    ->label('رفض')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->isInProgress())
                    ->requiresConfirmation()
                    ->modalHeading('رفض السائق')
                    ->modalDescription('هل أنت متأكد من رفض طلب هذا السائق؟')
                    ->action(function ($record) {
                        $record->reject();
                        \Filament\Notifications\Notification::make()
                            ->title('تم رفض طلب السائق.')
                            ->danger()
                            ->send();
                    }),

                // Toggle active — visible only for approved drivers
                Tables\Actions\Action::make('toggle_active')
                    ->label(fn ($record) => $record->user->is_active ? 'إلغاء التفعيل' : 'تفعيل')
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
                            ? 'تم تفعيل السائق بنجاح'
                            : 'تم إلغاء تفعيل السائق بنجاح';
                        \Filament\Notifications\Notification::make()
                            ->title($message)
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record) => $record->user->is_active ? 'إلغاء تفعيل السائق' : 'تفعيل السائق')
                    ->modalDescription(fn ($record) => $record->user->is_active
                        ? 'هل أنت متأكد من إلغاء تفعيل هذا السائق؟ لن يتمكن من تسجيل الدخول.'
                        : 'هل أنت متأكد من تفعيل حساب هذا السائق؟')
                    ->modalSubmitActionLabel(fn ($record) => $record->user->is_active ? 'إلغاء التفعيل' : 'تفعيل'),
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
                        Tabs\Tab::make('المعلومات العامة')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Section::make('المعلومات الشخصية')
                                    ->icon('heroicon-o-user')
                                    ->schema([
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
                                        TextEntry::make('user.gender')
                                            ->label('النوع')
                                            ->formatStateUsing(fn ($state) => $state === 'male' ? 'ذكر' : ($state === 'female' ? 'أنثى' : '-')),
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
                                            ->label('رقم الهوية الوطنية')
                                            ->copyable(),
                                        TextEntry::make('absher_phone')
                                            ->label('هاتف أبشر')
                                            ->copyable(),
                                        TextEntry::make('approval_status')
                                            ->label('حالة الموافقة')
                                            ->badge()
                                            ->formatStateUsing(fn ($state): string => self::normalizeApprovalStatus($state)->label())
                                            ->color(fn ($state): string => self::normalizeApprovalStatus($state)->color()),
                                        TextEntry::make('status')
                                            ->label('حالة السائق')
                                            ->badge()
                                            ->formatStateUsing(fn (?int $state): string => match ($state) {
                                                0       => 'غير متصل',
                                                1       => 'متصل',
                                                default => 'غير معروف',
                                            })
                                            ->color(fn (?int $state): string => match ($state) {
                                                0       => 'danger',
                                                1       => 'success',
                                                default => 'gray',
                                            }),
                                    ])->columns(2),

                                Section::make('معلومات المركبة')
                                    ->icon('heroicon-o-truck')
                                    ->schema([
                                        TextEntry::make('vehicle.vehicleType.name')
                                            ->label('تصنيف المركبة')
                                            ->placeholder('لا يوجد تصنيف مركبة'),
                                        TextEntry::make('vehicle.vehicleBrandModel.vehicleBrand.name')
                                            ->label('ماركة المركبة')
                                            ->placeholder('لا توجد ماركة'),
                                        TextEntry::make('vehicle.vehicleBrandModel.name')
                                            ->label('موديل المركبة')
                                            ->placeholder('لا يوجد موديل'),
                                        TextEntry::make('vehicle.color')
                                            ->label('اللون')
                                            ->placeholder('غير محدد'),
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
                                            ->placeholder('غير محدد'),
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
                                                $avg = $record->averageRating();
                                                return $avg ? number_format($avg, 2) . ' ★' : 'لا توجد تقييمات بعد';
                                            }),
                                        ViewEntry::make('trip_request_rates')
                                            ->label('معدلات طلبات الرحلات')
                                            ->view('filament.infolists.components.driver-request-rates')
                                            ->state(fn ($record) => app(TripRequestLogService::class)->getDriverRates($record->id))
                                            ->columnSpanFull(),
                                        TextEntry::make('user.created_at')
                                            ->label('تاريخ الانضمام')
                                            ->dateTime(),
                                    ])->columns(2),
                            ]),

                        Tabs\Tab::make('محفظة والمعاملات')
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
                                            ->label('السحوبات المعلقة')
                                            ->state(function ($record) {
                                                $amount = \App\Models\DriverWithdrawRequest::where('driver_id', $record->id)
                                                    ->where('is_approved', false)->sum('amount') ?? 0;
                                                return number_format((float) $amount, 2) . ' ' . config('app.currency', 'SAR');
                                            })
                                            ->badge()
                                            ->color('warning'),
                                        TextEntry::make('total_withdrawals')
                                            ->label('إجمالي السحوبات الموافق عليها')
                                            ->state(function ($record) {
                                                $amount = \App\Models\DriverWithdrawRequest::where('driver_id', $record->id)
                                                    ->where('is_approved', true)->sum('amount') ?? 0;
                                                return number_format((float) $amount, 2) . ' ' . config('app.currency', 'SAR');
                                            })
                                            ->badge()
                                            ->color('info'),
                                    ])->columns(3),

                                Section::make('المعاملات الأخيرة')
                                    ->icon('heroicon-o-list-bullet')
                                    ->columnSpanFull()
                                    ->collapsible()
                                    ->schema([
                                        ViewEntry::make('transactions')
                                            ->view('filament.infolists.components.wallet-transactions')
                                            ->state(fn ($record) => $record->getWalletTransactions()->latest()->limit(10)->get()),
                                    ]),

                                Section::make('طلبات السحب')
                                    ->icon('heroicon-o-minus-circle')
                                    ->columnSpanFull()
                                    ->collapsible()
                                    ->schema([
                                        ViewEntry::make('withdraw_requests')
                                            ->view('filament.infolists.components.withdraw-requests')
                                            ->state(fn ($record) => $record->withdrawRequests()->latest()->limit(10)->get()),
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
        return 'السائق';
    }

    public static function getPluralLabel(): string
    {
        return 'السائقين';
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
