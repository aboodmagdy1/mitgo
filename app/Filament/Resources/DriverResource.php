<?php

namespace App\Filament\Resources;

use App\Enums\ApprovalStatus;
use App\Filament\Resources\BaseDriverResource;
use App\Filament\Resources\DriverResource\Pages;
use App\Models\Driver;
use App\Models\VehicleType;
use App\Models\VehicleBrand;
use App\Models\VehicleBrandModel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;

/**
 * Legacy full-driver resource — hidden from sidebar navigation.
 * Used internally for direct route access and as a reference.
 * All segmented navigation goes through the 5 child resources.
 */
class DriverResource extends Resource
{
    protected static ?string $model = Driver::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationGroup(): ?string
    {
        return 'المستخدمين';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('معلومات المستخدم')
                ->schema([
                    Forms\Components\TextInput::make('user.name')
                        ->label('الاسم')->required()->maxLength(255),
                    Forms\Components\TextInput::make('user.email')
                        ->label('البريد الإلكتروني')->email()->maxLength(255),
                    Forms\Components\TextInput::make('user.phone')
                        ->label('رقم الهاتف')->tel()->required()->maxLength(255),
                    Forms\Components\Select::make('user.city_id')
                        ->label('المدينة')->relationship('user.city', 'name')->required()->searchable()->preload(),
                    Forms\Components\Toggle::make('user.is_active')
                        ->label('نشط')->default(true),
                ])->columns(2),

            Forms\Components\Section::make('معلومات السائق')
                ->schema([
                    Forms\Components\DatePicker::make('date_of_birth')
                        ->label('تاريخ الميلاد')->required(),
                    Forms\Components\TextInput::make('national_id')
                        ->label('رقم الهوية الوطنية')->required()->maxLength(255),
                    Forms\Components\TextInput::make('absher_phone')
                        ->label('هاتف أبشر')->tel()->required()->maxLength(255),
                    Forms\Components\Select::make('status')
                        ->label('حالة السائق')
                        ->options([0 => 'غير متصل', 1 => 'متصل'])->default(0),
                    Forms\Components\Select::make('approval_status')
                        ->label('حالة الموافقة')
                        ->options([
                            ApprovalStatus::PENDING->value     => 'قيد الانتظار',
                            ApprovalStatus::IN_PROGRESS->value => 'قيد المعاينة',
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
                        ->required()->reactive(),
                    Forms\Components\Select::make('vehicle.vehicle_brand_id')
                        ->label('ماركة المركبة')
                        ->options(VehicleBrand::where('active', true)->pluck('name', 'id'))
                        ->required()->reactive()
                        ->afterStateUpdated(fn ($state, callable $set) => $set('vehicle.vehicle_brand_model_id', null)),
                    Forms\Components\Select::make('vehicle.vehicle_brand_model_id')
                        ->label('موديل المركبة')
                        ->options(function (callable $get) {
                            $vehicleBrandId = $get('vehicle.vehicle_brand_id');
                            if (! $vehicleBrandId) return [];
                            return VehicleBrandModel::where('vehicle_brand_id', $vehicleBrandId)
                                ->where('active', true)->pluck('name', 'id');
                        })->required()->reactive()->searchable(),
                    Forms\Components\TextInput::make('vehicle.color')->label('لون المركبة')->maxLength(255),
                    Forms\Components\TextInput::make('vehicle.plate_number')->label('رقم اللوحة')->maxLength(255),
                    Forms\Components\TextInput::make('vehicle.seats')->label('عدد المقاعد')->numeric()->minValue(1)->maxValue(50),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('الاسم')->searchable()->sortable(),
                TextColumn::make('user.phone')->label('رقم الهاتف')->searchable(),
                TextColumn::make('user.email')->label('البريد الإلكتروني')->searchable(),
                TextColumn::make('user.city.name')->label('المدينة')->sortable(),
                BooleanColumn::make('user.is_active')->label('نشط')
                    ->trueIcon('heroicon-o-check-badge')->falseIcon('heroicon-o-x-circle'),
                BadgeColumn::make('approval_status')
                    ->label('حالة الموافقة')
                    ->formatStateUsing(fn ($state): string => BaseDriverResource::normalizeApprovalStatus($state)->label())
                    ->colors([
                        'warning' => fn ($state) => BaseDriverResource::normalizeApprovalStatus($state) === ApprovalStatus::PENDING,
                        'info'    => fn ($state) => BaseDriverResource::normalizeApprovalStatus($state) === ApprovalStatus::IN_PROGRESS,
                        'success' => fn ($state) => BaseDriverResource::normalizeApprovalStatus($state) === ApprovalStatus::APPROVED,
                        'danger'  => fn ($state) => BaseDriverResource::normalizeApprovalStatus($state) === ApprovalStatus::REJECTED,
                    ]),
                BadgeColumn::make('status')
                    ->label('حالة السائق')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        '0' => 'غير متصل', '1' => 'متصل', default => 'غير معروف',
                    })
                    ->colors(['danger' => '0', 'success' => '1']),
                TextColumn::make('created_at')->label('تاريخ الإنشاء')->dateTime()->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('approval_status')
                    ->label('حالة الموافقة')
                    ->options([
                        ApprovalStatus::PENDING->value     => 'قيد الانتظار',
                        ApprovalStatus::IN_PROGRESS->value => 'قيد المعاينة',
                        ApprovalStatus::APPROVED->value    => 'موافق عليه',
                        ApprovalStatus::REJECTED->value    => 'مرفوض',
                    ])
                    ->query(fn (Builder $query, array $data): Builder =>
                        $data['value'] !== null
                            ? $query->where('approval_status', $data['value'])
                            : $query
                    ),
                SelectFilter::make('city')
                    ->label('المدينة')->relationship('user.city', 'name')->searchable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDrivers::route('/'),
            'create' => Pages\CreateDriver::route('/create'),
            'view'   => Pages\ViewDriver::route('/{record}'),
            'edit'   => Pages\EditDriver::route('/{record}/edit'),
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
        return 'السائق';
    }

    public static function getPluralLabel(): string
    {
        return 'السائقين';
    }
}
