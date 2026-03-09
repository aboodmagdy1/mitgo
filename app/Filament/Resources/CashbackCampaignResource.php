<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CashbackCampaignResource\Pages;
use App\Models\CashbackCampaign;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CashbackCampaignResource extends Resource
{
    protected static ?string $model = CashbackCampaign::class;

    public static function getNavigationGroup(): ?string
    {
        return 'المالية';
    }

    public static function getPluralLabel(): ?string
    {
        return 'حملات الكاش باك';
    }

    public static function getModelLabel(): string
    {
        return 'حملة كاش باك';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الكاش باك الأساسية')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('اسم الحملة')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label('وصف الحملة')
                            ->rows(3)
                            ->maxLength(1000),

                        Forms\Components\Select::make('type')
                            ->label('نوع الكاش باك')
                            ->options([
                                CashbackCampaign::TYPE_FIXED_AMOUNT => 'مبلغ ثابت (ريال)',
                                CashbackCampaign::TYPE_PERCENTAGE => 'نسبة مئوية (%)',
                            ])
                            ->required()
                            ->default(CashbackCampaign::TYPE_FIXED_AMOUNT)
                            ->reactive(),

                        Forms\Components\TextInput::make('amount')
                            ->label(fn (callable $get) => $get('type') == CashbackCampaign::TYPE_PERCENTAGE
                                ? 'نسبة الكاش باك (%)'
                                : 'مبلغ الكاش باك (ريال)')
                            ->numeric()
                            ->required()
                            ->suffix(fn (callable $get) => $get('type') == CashbackCampaign::TYPE_PERCENTAGE ? '%' : 'ريال')
                            ->minValue(0),

                        Forms\Components\TextInput::make('max_cashback_amount')
                            ->label('الحد الأقصى للكاش باك لكل رحلة (ريال)')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('ريال')
                            ->helperText('اختياري - اتركه فارغاً لغير محدود')
                            ->visible(fn (callable $get) => $get('type') == CashbackCampaign::TYPE_PERCENTAGE),

                        Forms\Components\Toggle::make('can_stack_with_coupon')
                            ->label('يمكن الجمع مع الكوبونات')
                            ->default(true),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('فترة الحملة')
                    ->schema([
                        Forms\Components\DateTimePicker::make('start_date')
                            ->label('تاريخ ووقت البداية')
                            ->helperText('اختياري - اتركه فارغاً للبدء فوراً'),

                        Forms\Components\DateTimePicker::make('end_date')
                            ->label('تاريخ ووقت النهاية')
                            ->helperText('اختياري - اتركه فارغاً لحملة مفتوحة')
                            ->after('start_date'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('حدود الاستخدام')
                    ->schema([
                        Forms\Components\TextInput::make('max_trips_per_user')
                            ->label('عدد الرحلات لكل مستخدم')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('اختياري - اتركه فارغاً لغير محدود لكل مستخدم'),

                        Forms\Components\TextInput::make('max_trips_global')
                            ->label('الحد الأقصى للرحلات على مستوى النظام')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('اختياري - اتركه فارغاً لغير محدود'),

                        Forms\Components\TextInput::make('used_trips_global')
                            ->label('عدد الرحلات التي حصلت على كاش باك')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(3),

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
                    ->label('اسم الحملة')
                    ->searchable()
                    ->sortable(),

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
                        $record->type == CashbackCampaign::TYPE_PERCENTAGE
                            ? $record->amount . '%'
                            : $record->amount . ' ريال'
                    )
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('max_cashback_amount')
                    ->label('الحد الأقصى للكاش باك')
                    ->formatStateUsing(fn ($state) => $state ? $state . ' ريال' : '---')
                    ->alignCenter()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('max_trips_per_user')
                    ->label('الرحلات لكل مستخدم')
                    ->formatStateUsing(fn ($state) => $state ?: '∞')
                    ->alignCenter()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('max_trips_global')
                    ->label('الحد الأقصى الكلي')
                    ->formatStateUsing(fn ($state) => $state ?: '∞')
                    ->alignCenter()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('used_trips_global')
                    ->label('عدد الرحلات المستخدمة')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('مفعل')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('تاريخ البداية')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('---')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('تاريخ النهاية')
                    ->dateTime()
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
                    ->label('الحالة')
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
            'index' => Pages\ListCashbackCampaigns::route('/'),
            'create' => Pages\CreateCashbackCampaign::route('/create'),
            'edit' => Pages\EditCashbackCampaign::route('/{record}/edit'),
            'usage' => Pages\ViewCashbackUsage::route('/{record}/usage'),
        ];
    }
}

