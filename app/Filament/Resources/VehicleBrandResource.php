<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehicleBrandResource\Pages;
use App\Models\VehicleBrand;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
class VehicleBrandResource extends Resource
{
    use Translatable;
    
    protected static ?string $model = VehicleBrand::class;
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    public static function getNavigationGroup(): ?string
    {
        return __('Vehicle Management');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('Brand Name'))
                    ->translateLabel()
                    ->required()
                    ->maxLength(255),
                Forms\Components\Toggle::make('active')
                    ->label(__('Active'))
                    ->translateLabel()
                    ->default(true),
                SpatieMediaLibraryFileUpload::make('icon')
                    ->label(__('Icon'))
                    ->translateLabel()
                    ->collection('icon')
                    ->image(),          
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Brand Name'))
                    ->translateLabel()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('active')
                    ->label(__('Active'))
                    ->translateLabel()
                    ->boolean(),
                Tables\Columns\TextColumn::make('models_count')
                    ->label(__('Models Count'))
                    ->translateLabel()
                    ->counts('models')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->translateLabel()
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('active')
                    ->label(__('Active'))
                    ->translateLabel(),
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
            'index' => Pages\ListVehicleBrands::route('/'),
            'create' => Pages\CreateVehicleBrand::route('/create'),
            'edit' => Pages\EditVehicleBrand::route('/{record}/edit'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('Vehicle Brands');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Vehicle Brands');
    }

    public static function getModelLabel(): string
    {
        return __('Vehicle Brand');
    }
}
