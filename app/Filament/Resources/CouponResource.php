<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CouponResource\Pages;
use App\Models\Coupon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    public static function getNavigationGroup(): ?string
    {
        return 'التسويق';
    }

    public static function getPluralLabel(): ?string
    {
        return 'الكوبونات';
    }

    public static function getModelLabel(): string
    {
        return 'الكوبون';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الكوبون الأساسية')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('اسم الكوبون'))
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('code')
                            ->label('كود الكوبون')
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true)
                            ->helperText('كود فريد للكوبون'),

                        Forms\Components\Select::make('type')
                            ->label('نوع الكوبون')
                            ->options([
                                1 => 'نسبة مئوية (%)',
                                2 => 'مبلغ ثابت (ريال)',
                            ])
                            ->required()
                            ->default(1)
                            ->reactive(),

                        Forms\Components\TextInput::make('amount')
                            ->label(fn (callable $get) => $get('type') == 1 ? 'نسبة الخصم (%)' : 'مبلغ الخصم (ريال)')
                            ->numeric()
                            ->required()
                            ->suffix(fn (callable $get) => $get('type') == 1 ? '%' : 'ريال')
                            ->minValue(0)
                            ->maxValue(fn (callable $get) => $get('type') == 1 ? 100 : null),

                        Forms\Components\TextInput::make('max_discount_amount')
                            ->label('الحد الأقصى للخصم (ريال)')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('ريال')
                            ->helperText('مهم للكوبونات النسبة المئوية - اتركه فارغ للخصم بدون حد أقصى')
                            ->visible(fn (callable $get) => $get('type') == 1),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('صلاحية الكوبون')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('تاريخ البداية')
                            ->helperText('اختياري - اتركه فارغ للبدء الفوري'),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('تاريخ النهاية')
                            ->helperText('اختياري - اتركه فارغ للكوبون مفتوح')
                            ->after('start_date'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('حدود الاستخدام')
                    ->schema([
                        Forms\Components\TextInput::make('total_usage_limit')
                            ->label('عدد مرات الاستخدام الكلي')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('اختياري - اتركه فارغ للاستخدام المفتوح'),

                        Forms\Components\TextInput::make('usage_limit_per_user')
                            ->label('عدد مرات الاستخدام لكل مستخدم')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('اختياري - اتركه فارغ للاستخدام المفتوح لكل مستخدم'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('الحالة')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('مفعل')
                            ->default(true),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('اسم الكوبون'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('code')
                    ->label('الكود')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('type_name')
                    ->label('النوع')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'نسبة مئوية' => 'success',
                        'مبلغ ثابت' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('amount')
                    ->label('القيمة')
                    ->sortable()
                    ->formatStateUsing(fn ($record) => 
                        $record->type == 1 
                            ? $record->amount . '%' 
                            : $record->amount . ' ريال'
                    )
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('max_discount_amount')
                    ->label('الحد الأقصى')
                    ->formatStateUsing(fn ($state) => $state ? $state . ' ريال' : '---')
                    ->alignCenter()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('used_count')
                    ->label('مرات الاستخدام')
                    ->formatStateUsing(fn ($record) => 
                        $record->total_usage_limit 
                            ? $record->used_count . '/' . $record->total_usage_limit
                            : $record->used_count . '/∞'
                    )
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('remaining_usage')
                    ->label('المتبقي')
                    ->formatStateUsing(fn ($record) => 
                        $record->remaining_usage === null ? '∞' : $record->remaining_usage
                    )
                    ->alignCenter()
                    ->color(fn ($record) => 
                        $record->remaining_usage === null ? 'success' : 
                        ($record->remaining_usage > 10 ? 'success' : 
                        ($record->remaining_usage > 0 ? 'warning' : 'danger'))
                    ),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('مفعل')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('تاريخ البداية')
                    ->date()
                    ->sortable()
                    ->placeholder('---')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('تاريخ النهاية')
                    ->date()
                    ->sortable()
                    ->placeholder('---')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('نشط')
                    ->boolean()
                    ->trueLabel('المفعّلة فقط')
                    ->falseLabel('غير المفعّلة فقط')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض الاستخدام')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => static::getUrl('usage', ['record' => $record])),
                Tables\Actions\EditAction::make()->label('تعديل'),
                Tables\Actions\DeleteAction::make()->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('حذف المحدد'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'edit' => Pages\EditCoupon::route('/{record}/edit'),
            'usage' => Pages\ViewCouponUsage::route('/{record}/usage'),
        ];
    }
}


