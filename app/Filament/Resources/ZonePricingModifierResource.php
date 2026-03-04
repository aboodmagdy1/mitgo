<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ZonePricingModifierResource\Pages;
use App\Models\ZonePricingModifier;
use App\Models\Zone;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;

class ZonePricingModifierResource extends Resource
{
    use Translatable;

    protected static ?string $model = ZonePricingModifier::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';


    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('Zone Pricing Modifiers');
    }

    public static function getModelLabel(): string
    {
        return __('Zone Pricing Modifier');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Zone Pricing Modifiers');
    }
    public static function getNavigationGroup(): ?string
    {
        return __('Trips Management');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Basic Information'))
                    ->schema([
                        Forms\Components\Select::make('zone_id')
                            ->label(__('Zone'))
                            ->options(Zone::where('status', true)->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('name')
                            ->label(__('Name'))
                            ->required()
                            ->translateLabel()
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('start_date')
                            ->label(__('Start Date'))
                            ->required(),
                        Forms\Components\DatePicker::make('end_date')
                            ->label(__('End Date'))
                            ->required(),
                        Forms\Components\TextInput::make('multiplier')
                            ->label(__('Percentage'))
                            ->required()
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->maxValue(99.99)
                            ->suffix('%')
                            ->helperText(__('Enter percentage')),
                    ])->columns(2),

                Forms\Components\Section::make(__('Time Settings'))
                    ->schema([
                        Forms\Components\TimePicker::make('start_time')
                            ->label(__('Start Time'))
                            ->required()
                            ->seconds(false),
                        Forms\Components\TimePicker::make('end_time')
                            ->label(__('End Time'))
                            ->required()
                            ->seconds(false)
                            ->after('start_time'),
                    ])->columns(2),

                Forms\Components\Section::make(__('Status'))
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label(__('Active'))
                            ->default(true)
                            ->helperText(__('Only active modifiers will be applied to pricing')),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('zone.name')
                    ->label(__('Zone'))
                    ->searchable()
                    ->sortable()
                    ->badge(),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label(__('Start Date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label(__('End Date'))
                    ->date()
                    ->sortable(),
                    Tables\Columns\TextColumn::make('multiplier')
                    ->label(__('Percentage'))
                    ->formatStateUsing(fn ($state) => number_format($state, 0) . '%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->label(__('Start Time'))
                    ->time('H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_time')
                    ->label(__('End Time'))
                    ->time('H:i')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('Active'))
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('zone_id')
                    ->label(__('Zone'))
                    ->options(Zone::pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('is_active')
                    ->label(__('Active'))
                    ->boolean()
                    ->trueLabel(__('Active only'))
                    ->falseLabel(__('Inactive only'))
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('zone_id', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListZonePricingModifiers::route('/'),
            'create' => Pages\CreateZonePricingModifier::route('/create'),
            'edit' => Pages\EditZonePricingModifier::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->with('zone');
    }
}
