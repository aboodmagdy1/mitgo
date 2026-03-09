<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class ClientResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    public static function getNavigationGroup(): ?string
    {
        return 'المستخدمين';
    }    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Personal Information'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('Name'))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label(__('Email'))
                            ->email()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label(__('Phone'))
                            ->tel()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\Select::make('city_id')
                            ->label(__('City'))
                            ->relationship('city', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Toggle::make('is_active')
                            ->label(__('Active'))
                            ->default(true),
                    ])->columns(2),

                
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->label(__('Phone'))
                    ->searchable(),
                TextColumn::make('email')
                    ->label(__('Email'))
                    ->searchable(),
                TextColumn::make('city.name')
                    ->label(__('City'))
                    ->sortable(),
                BooleanColumn::make('is_active')
                    ->label(__('Active'))
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle'),
                TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label(__('Active Status'))
                    ->options([
                        1 => __('Active'),
                        0 => __('Inactive'),
                    ]),
                SelectFilter::make('city')
                    ->label(__('City'))
                    ->relationship('city', 'name')
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggle_active')
                    ->label(fn ($record) => $record->is_active ? __('Deactivate') : __('Activate'))
                    ->icon(fn ($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                    ->action(function ($record) {
                        $record->update(['is_active' => !$record->is_active]);
                        
                        $message = $record->is_active 
                            ? __('Client activated successfully')
                            : __('Client deactivated successfully');
                            
                        \Filament\Notifications\Notification::make()
                            ->title($message)
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record) => $record->is_active ? __('Deactivate Client') : __('Activate Client'))
                    ->modalDescription(fn ($record) => $record->is_active 
                        ? __('Are you sure you want to deactivate this client?')
                        : __('Are you sure you want to activate this client?'))
                    ->modalSubmitActionLabel(fn ($record) => $record->is_active ? __('Deactivate') : __('Activate')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make(__('Personal Information'))
                    ->icon('heroicon-o-user')
                    ->schema([
                        TextEntry::make('name')
                            ->label(__('Name'))
                            ->icon('heroicon-o-user'),
                        TextEntry::make('email')
                            ->label(__('Email'))
                            ->icon('heroicon-o-envelope')
                            ->copyable(),
                        TextEntry::make('phone')
                            ->label(__('Phone'))
                            ->icon('heroicon-o-phone')
                            ->copyable(),
                        TextEntry::make('city.name')
                            ->label(__('City'))
                            ->icon('heroicon-o-map-pin'),
                        TextEntry::make('is_active')
                            ->label(__('Account Status'))
                            ->badge()
                            ->formatStateUsing(fn (bool $state): string => $state ? __('Active') : __('Inactive'))
                            ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
                    ])->columns(2),

                
                Section::make(__('Trip Statistics'))
                    ->icon('heroicon-o-chart-bar')
                    ->schema([
                        TextEntry::make('trips_count')
                            ->label(__('Total Trips'))
                            ->formatStateUsing(function ($record) {
                                return $record->trips()->count();
                            }),
                        TextEntry::make('completed_trips_count')
                            ->label(__('Completed Trips'))
                            ->formatStateUsing(function ($record) {
                                return $record->trips()->where('status', 4)->count();
                            }),
                        TextEntry::make('cancelled_trips_count')
                            ->label(__('Cancelled Trips'))
                            ->formatStateUsing(function ($record) {
                                return $record->trips()->where('status', 5)->count();
                            }),
                        TextEntry::make('ratings_given_count')
                            ->label(__('Ratings Given'))
                            ->formatStateUsing(function ($record) {
                                return $record->givenRatings()->count();
                            }),
                        TextEntry::make('created_at')
                            ->label(__('Joined Date'))
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->label(__('Last Updated'))
                            ->dateTime(),
                    ])->columns(3),
            ]);
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
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'view' => Pages\ViewClient::route('/{record}'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('roles', function ($query) {
                $query->where('name', 'client');
            })
            ->with(['city', 'trips', 'givenRatings']);
    }

    public static function getLabel(): string
    {
        return __('Client');
    }

    public static function getPluralLabel(): string
    {
        return 'العملاء';
    }
}
