<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactResource\Pages;
use App\Filament\Resources\ContactResource\RelationManagers;
use App\Models\Contact;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static bool $shouldRegisterNavigation = false;

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
                    
                    // 0 open , 1 closed
                Tables\Columns\TextColumn::make('is_read')
                    ->label('مفتوح')
                    ->sortable()
                    ->icon(fn($record) => $record->is_read ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->color(fn($record) => $record->is_read ? 'success' : 'danger')
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContacts::route('/'),
            'view' => Pages\ViewContact::route('/{record}'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return 'الاتصال';
    }

    public static function getPluralLabel(): ?string
    {
        return 'رسائل الاتصال';
    }

    public static function getModelLabel(): string
    {
        return 'رسالة الاتصال';
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::count() > 0 ? 'warning' : null;
    }
}
