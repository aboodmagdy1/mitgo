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
                    ->label('الاسم')
                    ->required()
                    ->maxLength(255),
              
                Forms\Components\TextInput::make('email')
                    ->label('البريد الإلكتروني')
                    ->email()
                    ->unique(ignoreRecord: true)
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->label('رقم الهاتف')
                    ->tel()
                    ->unique(ignoreRecord: true)
                    ->required()
                    ->maxLength(255),
                // select role 
                Forms\Components\Select::make('role_id')
                    ->label('الدور')
                    ->options(Role::whereNotIn('name',['client','driver'])->pluck('name', 'id'))
                    ->required()
                    ->dehydrated(false), // Don't save this field directly to the model
                    //fill with current password if editing
                Forms\Components\TextInput::make('password')
                    ->label('كلمة المرور')
                    ->password()
                    ->required()
                    ->maxLength(255)
                    ->visibleOn('create')
                    ,

              
                

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('الاسم'),
                TextColumn::make('email')->label('البريد الإلكتروني'),
                TextColumn::make('roles.name')->label('الأدوار')->badge(),

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
        return "المدراء";
    }
    
    public static function getLabel(): ?string
    {
        return "مدير";
    }
    public static function getNavigationGroup(): ?string
    {
        return 'المستخدمين';
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('المعلومات الشخصية')
                    ->icon('heroicon-o-user')
                    ->collapsible()
                    ->schema([
                        ImageEntry::make('avatar_url')
                            ->label('الصورة الشخصية')
                            ->height(120)
                            ->width(120)
                            ->circular()
                            ->defaultImageUrl(url('/images/default-avatar.png'))
                            ->columnSpan(1),
                        
                        \Filament\Infolists\Components\Grid::make(2)
                            ->schema([
                                
                                TextEntry::make('name')
                                    ->label('الاسم')
                                    ->icon('heroicon-o-user')
                                    ->size('lg')
                                    ->weight('bold')
                                    ->color('primary'),
                                TextEntry::make('email')
                                    ->label('البريد الإلكتروني')
                                    ->icon('heroicon-o-envelope')
                                    ->copyable()
                                    ->copyMessage('تم نسخ البريد الإلكتروني!')
                                    ->color('gray'),
                            ])
                            ->columnSpan(2),
                    ])
                    ->columns(3),

                Section::make('الوصول والصلاحيات')
                    ->icon('heroicon-o-shield-check')
                    ->collapsible()
                    ->schema([
                        TextEntry::make('roles.name')
                            ->label('الأدوار')
                            ->badge()
                            ->color('success')
                            ->icon('heroicon-o-key')
                            ->separator(',')
                            ->columnSpanFull(),
                        
                        TextEntry::make('created_at')
                            ->label('عضو منذ')
                            ->icon('heroicon-o-calendar')
                            ->dateTime('F j, Y')
                            ->color('gray'),
                      
                    ])
                    ->columns(2),

                Section::make('إحصائيات الحساب')
                    ->icon('heroicon-o-chart-bar')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        \Filament\Infolists\Components\Grid::make(3)
                            ->schema([
                                TextEntry::make('updated_at')
                                    ->label('آخر تحديث')
                                    ->icon('heroicon-o-clock')
                                    ->since()
                                    ->color('gray'),
                                
                                TextEntry::make('id')
                                    ->label('معرف المستخدم')
                                    ->icon('heroicon-o-hashtag')
                                    ->color('gray'),
                                
                                TextEntry::make('roles')
                                    ->label('إجمالي الأدوار')
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
