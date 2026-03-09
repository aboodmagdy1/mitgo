<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehicleBrandModelResource\Pages;
use App\Models\VehicleBrand;
use App\Models\VehicleBrandModel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VehicleBrandModelResource extends Resource
{
    use Translatable;
    
    protected static ?string $model = VehicleBrandModel::class;
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    public static function getNavigationGroup(): ?string
    {
        return 'إدارة المركبات';
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('vehicle_brand_id')
                    ->label('ماركة المركبة')
                    ->options(VehicleBrand::where('active', true)->pluck('name', 'id'))
                    ->searchable()
                    ->required()
                    ->preload(),
                Forms\Components\TextInput::make('name')
                    ->label('اسم الموديل')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Toggle::make('active')
                    ->label('نشط')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('vehicleBrand.name')
                    ->label('الماركة')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم الموديل')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('active')
                    ->label('نشط')
                    ->boolean(),
                Tables\Columns\TextColumn::make('driverVehicles_count')
                    ->label('مستخدم من قبل السائقين')
                    ->counts('driverVehicles')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('vehicle_brand_id')
                    ->label('الماركة')
                    ->options(VehicleBrand::pluck('name', 'id'))
                    ->searchable(),
                Tables\Filters\TernaryFilter::make('active')
                    ->label('نشط'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListVehicleBrandModels::route('/'),
            'create' => Pages\CreateVehicleBrandModel::route('/create'),
            'edit' => Pages\EditVehicleBrandModel::route('/{record}/edit'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return 'موديلات المركبات';
    }

    public static function getPluralModelLabel(): string
    {
        return 'موديلات المركبات';
    }

    public static function getModelLabel(): string
    {
        return 'موديل المركبة';
    }
}
