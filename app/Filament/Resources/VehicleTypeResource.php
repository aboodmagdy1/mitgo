<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehicleTypeResource\Pages;
use App\Models\VehicleType;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Select;
use App\Models\Zone;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Table;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Illuminate\Database\Eloquent\Builder;

class VehicleTypeResource extends Resource
{
    use Translatable;
    
    protected static ?string $model = VehicleType::class;
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'Vehicle Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('Vehicle Type Information'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('Name'))
                            ->translateLabel()
                            ->required(),
                        
                        TextInput::make('seats')
                            ->label(__('Number of Seats'))
                            ->translateLabel()
                            ->numeric()
                            ->required()
                            ->default(0),
                        
                       
                        SpatieMediaLibraryFileUpload::make('icon')
                            ->label(__('Icon'))
                            ->translateLabel()
                            ->collection('icon')
                            ->image(),

                        Toggle::make('active')
                            ->label(__('Active'))
                            ->translateLabel()
                            ->default(true),
                    ])
                    ->columns(2),
                
                Section::make(__('Default Pricing'))
                    ->description(__('Default pricing configuration used when no zone-specific pricing is available'))
                    ->schema([
                        TextInput::make('defaultPricing.base_fare')
                            ->label(__('Base Fare'))
                            ->translateLabel()
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->required()
                            ->placeholder(__('Enter base fare amount')),
                        
                        TextInput::make('defaultPricing.fare_per_km')
                            ->label(__('Fare per KM'))
                            ->translateLabel()
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->required()
                            ->placeholder(__('Enter fare per kilometer')),
                        
                        TextInput::make('defaultPricing.fare_per_minute')
                            ->label(__('Fare per Minute'))
                            ->translateLabel()
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->required()
                            ->placeholder(__('Enter fare per minute')),
                        
                        TextInput::make('defaultPricing.cancellation_fee')
                            ->label(__('Cancellation Fee'))
                            ->translateLabel()
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->required()
                            ->placeholder(__('Enter cancellation fee')),
                        
                        TextInput::make('defaultPricing.waiting_fee')
                            ->label(__('Waiting Fee'))
                            ->translateLabel()
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->required()
                            ->placeholder(__('Enter waiting fee per minute')),
                    ])
                    ->columns(3)
                    ->collapsed(),
                
                Section::make(__('Zone Pricing'))
                    ->schema([
                        Repeater::make('zonePricing')
                            ->label(__('Pricing for each zone'))
                            ->relationship('zonePricing')
                            ->schema([
                                Select::make('zone_id')
                                    ->label(__('Zone'))
                                    ->options(Zone::where('status', true)->pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                                
                                TextInput::make('base_fare')
                                    ->label(__('Base Fare'))
                                    ->translateLabel()
                                    ->numeric()
                                    ->step(0.01)
                                    ->minValue(0)
                                    ->required()
                                    ->placeholder(__('Enter base fare amount')),
                                
                                TextInput::make('fare_per_km')
                                    ->label(__('Fare per KM'))
                                    ->translateLabel()
                                    ->numeric()
                                    ->step(0.01)
                                    ->minValue(0)
                                    ->required()
                                    ->placeholder(__('Enter fare per kilometer')),
                                
                                TextInput::make('fare_per_minute')
                                    ->label(__('Fare per Minute'))
                                    ->translateLabel()
                                    ->numeric()
                                    ->step(0.01)
                                    ->minValue(0)
                                    ->required()
                                    ->placeholder(__('Enter fare per minute')),
                                
                                TextInput::make('cancellation_fee')
                                    ->label(__('Cancellation Fee'))
                                    ->translateLabel()
                                    ->numeric()
                                    ->step(0.01)
                                    ->minValue(0)
                                    ->required()
                                    ->placeholder(__('Enter cancellation fee')),
                                
                                TextInput::make('waiting_fee')
                                    ->label(__('Waiting Fee'))
                                    ->translateLabel()
                                    ->numeric()
                                    ->step(0.01)
                                    ->minValue(0)
                                    ->required()
                                    ->placeholder(__('Enter waiting fee per minute')),
                                
                                TextInput::make('extra_fare')
                                    ->label(__('Extra Fare'))
                                    ->translateLabel()
                                    ->numeric()
                                    ->step(0.01)
                                    ->minValue(0)
                                    ->required()
                                    ->placeholder(__('Enter extra fare amount')),
                            ])
                            ->columns(3)
                            ->addActionLabel(__('Add Zone Pricing'))
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => Zone::find($state['zone_id'])?->name ?? null)
                            ->defaultItems(0)
                            ->createItemButtonLabel(__('Add Zone Pricing'))
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table       
            ->columns([
                SpatieMediaLibraryImageColumn::make('icon')
                    ->label(__('Icon'))
                    ->collection('icon')
                    ->circular()
                    ->size(50),

                TextColumn::make('name')
                    ->label(__('Name'))
                    ->translateLabel()
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('seats')
                    ->label(__('Seats'))
                    ->translateLabel()
                    ->sortable(),
                
               
                
                BooleanColumn::make('active')
                    ->label(__('Active'))
                    ->translateLabel()
                    ->sortable(),
                
                
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('active')
                    ->label(__('Active'))
                    ->translateLabel(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()->disabled(function (VehicleType $record) {
                    return $record->id == 1;
                }   ),
            ])
            ->bulkActions([
              
            ])
            ;
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
            'index' => Pages\ListVehicleTypes::route('/'),
            'create' => Pages\CreateVehicleType::route('/create'),
            'view' => Pages\ViewVehicleType::route('/{record}'),
            'edit' => Pages\EditVehicleType::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Vehicle Management');
    }

    public static function getPluralLabel(): ?string
    {
        return __('Vehicle Types');
    }

    public static function getLabel(): ?string
    {
        return __('Vehicle Type');
    }
}
