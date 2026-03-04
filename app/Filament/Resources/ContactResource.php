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
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon('heroicon-o-user'),
                    
                Tables\Columns\TextColumn::make('email')
                    ->label(__('Email'))
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-envelope')
                    ->color('info')
                    ->copyable(),
                    
                Tables\Columns\TextColumn::make('is_read')
                    ->label(__('Read'))
                    ->sortable()
                    ->icon(fn($record) => $record->is_read ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->color(fn($record) => $record->is_read ? 'success' : 'danger'),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Received At'))
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
                    ->label(__('View Details'))
                    ->icon('heroicon-o-eye'),
                    
                
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->label(__('Delete Selected')),
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
        return __('Communication');
    }

    public static function getPluralLabel(): ?string
    {
        return __('Contact Messages');
    }

    public static function getModelLabel(): string
    {
        return __('Contact Message');
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
