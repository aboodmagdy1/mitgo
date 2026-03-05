<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ZoneResource\Pages;
use App\Models\Zone;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Dotswan\MapPicker\Fields\Map;
use App\Rules\SinglePolygonRule;
class ZoneResource extends Resource
{

    protected static ?string $model = Zone::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';


    public static function getNavigationLabel(): string
    {
        return __('Zones');
    }

    public static function getModelLabel(): string
    {
        return __('Zone');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Zones');
    }
    public static function getNavigationGroup(): ?string
    {
        return __('Locations');
    }

    public static function form(Form $form): Form
    {
        

        return $form
            ->schema([
                Forms\Components\Section::make(__('Details'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('Name'))
                            ->required()
                            ->translateLabel(),
                        Forms\Components\Toggle::make('status')
                            ->label(__('Active'))
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make(__('Map'))
                    ->schema([
                        Map::make('points')
                        ->label(__('Polygon'))
                        ->required()
                        ->helperText(__('Draw exactly one polygon.'))
                        ->rules([new SinglePolygonRule()])
                        ->columnSpanFull()
                        ->zoom(8)
                        ->minZoom(2)
                        ->maxZoom(18)
                        ->tilesUrl('https://tile.openstreetmap.de/{z}/{x}/{y}.png')
                        ->detectRetina(true)
                        ->showMarker(false)
                        ->draggable(true)
                        // GeoMan drawing tools
                        ->geoMan(true)
                        ->geoManEditable(true)
                        ->geoManPosition('topleft')
                        ->drawPolygon(true)
                        ->drawPolyline(false)
                        ->drawCircle(false)
                        ->drawRectangle(false)
                        ->drawMarker(false)
                        ->drawText(false)
                        ->cutPolygon(true)
                        ->editPolygon(true)
                        ->deleteLayer(true)
                        ->setColor('#3388ff')
                        ->setFilledColor('#cad9ec')
                        ->snappable(true, 20),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('status')
                    ->label(__('Active'))
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label(__('View')),
                Tables\Actions\EditAction::make()->label(__('Edit')),
                Tables\Actions\DeleteAction::make()->label(__('Delete')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label(__('Delete Selected')),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListZones::route('/'),
            'create' => Pages\CreateZone::route('/create'),
            'view' => Pages\ViewZone::route('/{record}/view'),
            'edit' => Pages\EditZone::route('/{record}/edit'),
        ];
    }



    public static function getTranslatableLocales(): array
    {
        return ['en', 'ar'];
    }
}


