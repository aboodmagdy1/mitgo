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
                Section::make('معلومات نوع المركبة')
                    ->schema([
                        TextInput::make('name')
                            ->label('الاسم')
                            ->required(),
                        
                        TextInput::make('seats')
                            ->label('عدد المقاعد')
                            ->numeric()
                            ->required()
                            ->default(0),
                        
                       
                        SpatieMediaLibraryFileUpload::make('icon')
                            ->label('الأيقونة')
                            ->collection('icon')
                            ->image(),

                        Toggle::make('active')
                            ->label('نشط')
                            ->default(true),
                    ])
                    ->columns(2),
                
                Section::make('التسعير الافتراضي')
                    ->description('إعدادات التسعير الافتراضية المستخدمة عندما لا يتوفر تسعير خاص بالمنطقة')
                    ->schema([
                        TextInput::make('defaultPricing.base_fare')
                            ->label('الأجرة الأساسية')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->required()
                            ->placeholder('أدخل مبلغ الأجرة الأساسية'),
                        
                        TextInput::make('defaultPricing.fare_per_km')
                            ->label('الأجرة لكل كيلومتر')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->required()
                            ->placeholder('أدخل الأجرة لكل كيلومتر'),
                        
                        TextInput::make('defaultPricing.fare_per_minute')
                            ->label('الأجرة لكل دقيقة')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->required()
                            ->placeholder('أدخل الأجرة لكل دقيقة'),
                        
                        TextInput::make('defaultPricing.cancellation_fee')
                            ->label('رسوم الإلغاء')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->required()
                            ->placeholder('أدخل رسوم الإلغاء'),
                        
                        TextInput::make('defaultPricing.waiting_fee')
                            ->label('رسوم الانتظار')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->required()
                            ->placeholder('أدخل رسوم الانتظار لكل دقيقة'),
                    ])
                    ->columns(3)
                    ->collapsed(),
                
                Section::make('تسعير المناطق')
                    ->schema([
                        Repeater::make('zonePricing')
                            ->label('التسعير لكل منطقة')
                            ->relationship('zonePricing')
                            ->schema([
                                Select::make('zone_id')
                                    ->label('المنطقة')
                                    ->options(Zone::where('status', true)->pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                                
                                TextInput::make('base_fare')
                                    ->label('الأجرة الأساسية')
                                    ->numeric()
                                    ->step(0.01)
                                    ->minValue(0)
                                    ->required()
                                    ->placeholder('أدخل مبلغ الأجرة الأساسية'),
                                
                                TextInput::make('fare_per_km')
                                    ->label('الأجرة لكل كيلومتر')
                                    ->numeric()
                                    ->step(0.01)
                                    ->minValue(0)
                                    ->required()
                                    ->placeholder('أدخل الأجرة لكل كيلومتر'),
                                
                                TextInput::make('fare_per_minute')
                                    ->label('الأجرة لكل دقيقة')
                                    ->numeric()
                                    ->step(0.01)
                                    ->minValue(0)
                                    ->required()
                                    ->placeholder('أدخل الأجرة لكل دقيقة'),
                                
                                TextInput::make('cancellation_fee')
                                    ->label('رسوم الإلغاء')
                                    ->numeric()
                                    ->step(0.01)
                                    ->minValue(0)
                                    ->required()
                                    ->placeholder('أدخل رسوم الإلغاء'),
                                
                                TextInput::make('waiting_fee')
                                    ->label('رسوم الانتظار')
                                    ->numeric()
                                    ->step(0.01)
                                    ->minValue(0)
                                    ->required()
                                    ->placeholder('أدخل رسوم الانتظار لكل دقيقة'),
                                
                                TextInput::make('extra_fare')
                                    ->label('أجرة إضافية')
                                    ->numeric()
                                    ->step(0.01)
                                    ->minValue(0)
                                    ->required()
                                    ->placeholder('أدخل مبلغ الأجرة الإضافية'),
                            ])
                            ->columns(3)
                            ->addActionLabel('إضافة تسعير المنطقة')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => Zone::find($state['zone_id'])?->name ?? null)
                            ->defaultItems(0)
                            ->createItemButtonLabel('إضافة تسعير المنطقة')
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table       
            ->columns([
                SpatieMediaLibraryImageColumn::make('icon')
                    ->label('الأيقونة')
                    ->collection('icon')
                    ->circular()
                    ->size(50),

                TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('seats')
                    ->label('المقاعد')
                    ->sortable(),
                
               
                
                BooleanColumn::make('active')
                    ->label('نشط')
                    ->sortable(),
                
                
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('active')
                    ->label('نشط'),
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
        return 'إدارة المركبات';
    }

    public static function getPluralLabel(): ?string
    {
        return 'تصنيفات المركبات';
    }

    public static function getLabel(): ?string
    {
        return 'تصنيف المركبة';
    }
}
