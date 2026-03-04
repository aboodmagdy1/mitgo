<?php

namespace App\Filament\Resources;

use App\Enums\UserTypeEnum;
use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\Widgets\UserStatsWidget;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;


class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->translateLabel()
                    ->maxLength(255),
              
                Forms\Components\TextInput::make('email')
                    ->translateLabel()
                    ->email()
                    ->unique(ignoreRecord: true)
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->translateLabel()
                    ->tel()
                    ->unique(ignoreRecord: true)
                    ->required()
                    ->maxLength(255),
                // select role 
                Forms\Components\Select::make('role_id')
                    ->label(__('Role'))
                    ->translateLabel()
                    ->options(Role::whereNotIn('name',['client','driver'])->pluck('name', 'id'))
                    ->required()
                    ->dehydrated(false), // Don't save this field directly to the model
                    //fill with current password if editing
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->required()
                    ->translateLabel()
                    ->maxLength(255)
                    ->visibleOn('create')
                    ,

              
                

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->translateLabel(),
                TextColumn::make('email')->translateLabel(),
                TextColumn::make('roles.name')->badge()->translateLabel(),

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
            'view' => Pages\ViewUser::route('/{record}'),
        ];
    }

    public static function getPluralLabel(): ?string
    {
        return __("Admins");
    }
    
    public static function getLabel(): ?string
    {
        return __("Admin");
    }
    public static function getNavigationGroup(): ?string
    {
        return __('Users');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make(__('Personal Information'))
                    ->translateLabel()
                    ->icon('heroicon-o-user')
                    ->collapsible()
                    ->schema([
                        ImageEntry::make('avatar_url')
                            ->label('Avatar')
                            ->translateLabel()
                            ->height(120)
                            ->width(120)
                            ->circular()
                            ->defaultImageUrl(url('/images/default-avatar.png'))
                            ->columnSpan(1),
                        
                        \Filament\Infolists\Components\Grid::make(2)
                            ->schema([
                                
                                TextEntry::make('name')
                                    ->label('Name')
                                    ->translateLabel()
                                    ->icon('heroicon-o-user')
                                    ->size('lg')
                                    ->weight('bold')
                                    ->color('primary'),
                                TextEntry::make('email')
                                    ->label('Email')
                                    ->translateLabel()
                                    ->icon('heroicon-o-envelope')
                                    ->copyable()
                                    ->copyMessage('Email copied!')
                                    ->color('gray'),
                            ])
                            ->columnSpan(2),
                    ])
                    ->columns(3),

                Section::make(__('Access & Permissions'))
                    ->translateLabel()
                    ->icon('heroicon-o-shield-check')
                    ->collapsible()
                    ->schema([
                        TextEntry::make('roles.name')
                            ->translateLabel()
                            ->label('Roles')
                            ->badge()
                            ->color('success')
                            ->icon('heroicon-o-key')
                            ->separator(',')
                            ->columnSpanFull(),
                        
                        TextEntry::make('created_at')
                            ->translateLabel()
                            ->label('Member Since')
                            ->icon('heroicon-o-calendar')
                            ->dateTime('F j, Y')
                            ->color('gray'),
                      
                    ])
                    ->columns(2),

                Section::make(__('Account Statistics'))
                    ->translateLabel()
                    ->icon('heroicon-o-chart-bar')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        \Filament\Infolists\Components\Grid::make(3)
                            ->schema([
                                TextEntry::make('updated_at')
                                    ->translateLabel()
                                    ->label('Last Updated')
                                    ->icon('heroicon-o-clock')
                                    ->since()
                                    ->color('gray'),
                                
                                TextEntry::make('id')
                                    ->translateLabel()
                                    ->label('User ID')
                                    ->icon('heroicon-o-hashtag')
                                    ->color('gray'),
                                
                                TextEntry::make('roles')
                                    ->translateLabel()
                                    ->label('Total Roles')
                                    ->icon('heroicon-o-users')
                                    ->formatStateUsing(fn ($record) => $record->roles->count())
                                    ->color('primary'),
                            ]),
                    ]),
            ]);
    }

    public static function getWidgets(): array
    {
        return [
            UserStatsWidget::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereHas('roles', function ($query) {
            $query->whereNotIn('name',['client','driver']);
        });
    }

    public static function mutateFormDataBeforeFill(array $data): array
    {
        // Get the current user's role ID for editing
        if (isset($data['id'])) {
            $user = User::find($data['id']);
            if ($user && $user->roles->isNotEmpty()) {
                $data['role_id'] = $user->roles->first()->id;
            }
        }
        
        return $data;
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        // Remove role_id from the data that will be saved to the model
        unset($data['role_id']);
        return $data;
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        // Remove role_id from the data that will be saved to the model
        unset($data['role_id']);
        return $data;
    }
}
