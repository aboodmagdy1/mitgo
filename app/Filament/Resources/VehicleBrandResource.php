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
        return 'إدارة المركبات';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('اسم الماركة')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Toggle::make('active')
                    ->label('نشط')
                    ->default(true),
                SpatieMediaLibraryFileUpload::make('icon')
                    ->label('الأيقونة')
                    ->collection('icon')
                    ->image(),          
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم الماركة')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('active')
                    ->label('نشط')
                    ->boolean(),
                Tables\Columns\TextColumn::make('models_count')
                    ->label('عدد الموديلات')
                    ->counts('models')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
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
            'index' => Pages\ListVehicleBrands::route('/'),
            'create' => Pages\CreateVehicleBrand::route('/create'),
            'edit' => Pages\EditVehicleBrand::route('/{record}/edit'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return 'ماركات المركبات';
    }

    public static function getPluralModelLabel(): string
    {
        return 'ماركات المركبات';
    }

    public static function getModelLabel(): string
    {
        return 'ماركة المركبة';
    }
}
