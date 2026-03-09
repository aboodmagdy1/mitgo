<?php

namespace App\Filament\Resources;

use App\Models\Contact;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

abstract class ContactResourceBase extends Resource
{
    protected static ?string $model = Contact::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    abstract protected static function getSource(): int;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon('heroicon-o-user'),

                Tables\Columns\TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-envelope')
                    ->color('info')
                    ->copyable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('النوع')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state == 0 ? 'اقتراحات' : 'مشكلة')
                    ->icon(fn ($state) => $state == 0 ? 'heroicon-o-light-bulb' : 'heroicon-o-exclamation-triangle')
                    ->color('info'),
                    // 0: suggestions , 1: problem 

                    // 1 open , 0 closed
                    Tables\Columns\TextColumn::make('is_read')
                    ->label('مفتوح')
                    ->sortable()
                    ->icon(fn($record) => $record->is_read == 0 ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn($record) => $record->is_read == 0 ?  'danger' : 'success' )
                    ->formatStateUsing(fn($state) => $state == 0 ? 'مفتوح' : 'مغلق'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الاستلام')
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->icon('heroicon-o-clock')
                    ->color('gray'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('عرض التفاصيل')
                    ->icon('heroicon-o-eye'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->label('حذف المحدد'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->poll('30s');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('source', static::getSource());
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return 'قسم خدمة العملاء';
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('source', static::getSource())->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('source', static::getSource())->count() > 0 ? 'warning' : null;
    }
}
