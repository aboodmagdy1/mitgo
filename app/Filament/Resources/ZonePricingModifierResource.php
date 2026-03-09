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
        return 'معدلات تسعير المناطق';
    }

    public static function getModelLabel(): string
    {
        return 'معدل تسعير المنطقة';
    }

    public static function getPluralModelLabel(): string
    {
        return 'معدلات تسعير المناطق';
    }
    public static function getNavigationGroup(): ?string
    {
        return 'إدارة الرحلات';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('المعلومات الأساسية')
                    ->schema([
                        Forms\Components\Select::make('zone_id')
                            ->label('المنطقة')
                            ->options(Zone::where('status', true)->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('name')
                            ->label('الاسم')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('start_date')
                            ->label('تاريخ البداية')
                            ->required(),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('تاريخ النهاية')
                            ->required(),
                        Forms\Components\TextInput::make('multiplier')
                            ->label('النسبة')
                            ->required()
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->maxValue(99.99)
                            ->suffix('%')
                            ->helperText('أدخل النسبة'),
                    ])->columns(2),

                Forms\Components\Section::make('إعدادات الوقت')
                    ->schema([
                        Forms\Components\TimePicker::make('start_time')
                            ->label('وقت البداية')
                            ->required()
                            ->seconds(false),
                        Forms\Components\TimePicker::make('end_time')
                            ->label('وقت النهاية')
                            ->required()
                            ->seconds(false)
                            ->after('start_time'),
                    ])->columns(2),

                Forms\Components\Section::make('الحالة')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('نشط')
                            ->default(true)
                            ->helperText('سيتم تطبيق المعدلات النشطة فقط على التسعير'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('zone.name')
                    ->label('المنطقة')
                    ->searchable()
                    ->sortable()
                    ->badge(),
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('تاريخ البداية')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('تاريخ النهاية')
                    ->date()
                    ->sortable(),
                    Tables\Columns\TextColumn::make('multiplier')
                    ->label('النسبة')
                    ->formatStateUsing(fn ($state) => number_format($state, 0) . '%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->label('وقت البداية')
                    ->time('H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_time')
                    ->label('وقت النهاية')
                    ->time('H:i')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('zone_id')
                    ->label('المنطقة')
                    ->options(Zone::pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('is_active')
                    ->label('نشط')
                    ->boolean()
                    ->trueLabel('النشط فقط')
                    ->falseLabel('غير النشط فقط')
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
