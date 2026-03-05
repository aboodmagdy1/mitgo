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
        return __('Users');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('User Information'))
                ->schema([
                    Forms\Components\TextInput::make('user.name')
                        ->label(__('Name'))->required()->maxLength(255),
                    Forms\Components\TextInput::make('user.email')
                        ->label(__('Email'))->email()->maxLength(255),
                    Forms\Components\TextInput::make('user.phone')
                        ->label(__('Phone'))->tel()->required()->maxLength(255),
                    Forms\Components\Select::make('user.city_id')
                        ->label(__('City'))->relationship('user.city', 'name')->required()->searchable()->preload(),
                    Forms\Components\Toggle::make('user.is_active')
                        ->label(__('Active'))->default(true),
                ])->columns(2),

            Forms\Components\Section::make(__('Driver Information'))
                ->schema([
                    Forms\Components\DatePicker::make('date_of_birth')
                        ->label(__('Date of Birth'))->required(),
                    Forms\Components\TextInput::make('national_id')
                        ->label(__('National ID'))->required()->maxLength(255),
                    Forms\Components\TextInput::make('license_number')
                        ->label(__('License Number'))->required()->maxLength(255),
                    Forms\Components\TextInput::make('absher_phone')
                        ->label(__('Absher Phone'))->tel()->required()->maxLength(255),
                    Forms\Components\Select::make('status')
                        ->label(__('Driver Status'))
                        ->options([0 => __('Offline'), 1 => __('Online')])->default(0),
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
                        ->required()->reactive(),
                    Forms\Components\Select::make('vehicle.vehicle_brand_id')
                        ->label(__('Vehicle Brand'))
                        ->options(VehicleBrand::where('active', true)->pluck('name', 'id'))
                        ->required()->reactive()
                        ->afterStateUpdated(fn ($state, callable $set) => $set('vehicle.vehicle_brand_model_id', null)),
                    Forms\Components\Select::make('vehicle.vehicle_brand_model_id')
                        ->label(__('Vehicle Model'))
                        ->options(function (callable $get) {
                            $vehicleBrandId = $get('vehicle.vehicle_brand_id');
                            if (! $vehicleBrandId) return [];
                            return VehicleBrandModel::where('vehicle_brand_id', $vehicleBrandId)
                                ->where('active', true)->pluck('name', 'id');
                        })->required()->reactive()->searchable(),
                    Forms\Components\TextInput::make('vehicle.color')->label(__('Vehicle Color'))->maxLength(255),
                    Forms\Components\TextInput::make('vehicle.license_number')->label(__('Vehicle License Number'))->maxLength(255),
                    Forms\Components\TextInput::make('vehicle.plate_number')->label(__('Plate Number'))->maxLength(255),
                    Forms\Components\TextInput::make('vehicle.seats')->label(__('Number of Seats'))->numeric()->minValue(1)->maxValue(50),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label(__('Name'))->searchable()->sortable(),
                TextColumn::make('user.phone')->label(__('Phone'))->searchable(),
                TextColumn::make('user.email')->label(__('Email'))->searchable(),
                TextColumn::make('user.city.name')->label(__('City'))->sortable(),
                BooleanColumn::make('user.is_active')->label(__('Active'))
                    ->trueIcon('heroicon-o-check-badge')->falseIcon('heroicon-o-x-circle'),
                BadgeColumn::make('approval_status')
                    ->label(__('Approval Status'))
                    ->formatStateUsing(fn ($state): string => BaseDriverResource::normalizeApprovalStatus($state)->label())
                    ->colors([
                        'warning' => fn ($state) => BaseDriverResource::normalizeApprovalStatus($state) === ApprovalStatus::PENDING,
                        'info'    => fn ($state) => BaseDriverResource::normalizeApprovalStatus($state) === ApprovalStatus::IN_PROGRESS,
                        'success' => fn ($state) => BaseDriverResource::normalizeApprovalStatus($state) === ApprovalStatus::APPROVED,
                        'danger'  => fn ($state) => BaseDriverResource::normalizeApprovalStatus($state) === ApprovalStatus::REJECTED,
                    ]),
                BadgeColumn::make('status')
                    ->label(__('Driver Status'))
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        '0' => __('Offline'), '1' => __('Online'), default => __('Unknown'),
                    })
                    ->colors(['danger' => '0', 'success' => '1']),
                TextColumn::make('created_at')->label(__('Created At'))->dateTime()->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('approval_status')
                    ->label(__('Approval Status'))
                    ->options([
                        ApprovalStatus::PENDING->value     => __('Pending'),
                        ApprovalStatus::IN_PROGRESS->value => __('In Progress'),
                        ApprovalStatus::APPROVED->value    => __('Approved'),
                        ApprovalStatus::REJECTED->value    => __('Rejected'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder =>
                        $data['value'] !== null
                            ? $query->where('approval_status', $data['value'])
                            : $query
                    ),
                SelectFilter::make('city')
                    ->label(__('City'))->relationship('user.city', 'name')->searchable(),
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
        return __('Driver');
    }

    public static function getPluralLabel(): string
    {
        return __('Drivers');
    }
}
