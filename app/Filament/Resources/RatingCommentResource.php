<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RatingCommentResource\Pages;
use App\Models\RatingComment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
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

class RatingCommentResource extends Resource
{
    use Translatable;
    protected static ?string $model = RatingComment::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __("Informative Content");
    }
    public static function getPluralModelLabel(): string
    {
        return __('Rating Comments');
    }
   
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Comment Information'))
                    ->schema([
                        Forms\Components\TextInput::make('comment')
                            ->label(__('Comment'))
                            ->required()
                            ->translateLabel()
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('is_positive')
                            ->label(__('Is Positive Comment'))
                            ->default(true)
                            ->helperText(__('Mark as positive if this is a complimentary comment')),
                        Forms\Components\Toggle::make('active')
                            ->label(__('Active'))
                            ->default(true)
                            ->helperText(__('Only active comments will be available for selection')),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('comment')
                    ->label(__('Comment'))
                    ->formatStateUsing(function (RatingComment $record) {
                        return $record->getCommentText(app()->getLocale());
                    })
                    ->limit(50)
                    ->tooltip(function (RatingComment $record) {
                        $text = $record->getCommentText(app()->getLocale());
                        if (strlen($text) <= 50) {
                            return null;
                        }
                        return $text;
                    }),
                BooleanColumn::make('is_positive')
                    ->label(__('Positive'))
                    ->trueIcon('heroicon-o-face-smile')
                    ->falseIcon('heroicon-o-face-frown')
                    ->trueColor('success')
                    ->falseColor('danger'),
                BooleanColumn::make('active')
                    ->label(__('Active'))
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
                
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('is_positive')
                    ->label(__('Comment Type'))
                    ->options([
                        1 => __('Positive'),
                        0 => __('Negative'),
                    ]),
                SelectFilter::make('active')
                    ->label(__('Status'))
                    ->options([
                        1 => __('Active'),
                        0 => __('Inactive'),
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggle_active')
                    ->label(fn ($record) => $record->active ? __('Deactivate') : __('Activate'))
                    ->icon(fn ($record) => $record->active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->active ? 'danger' : 'success')
                    ->action(function ($record) {
                        $record->update(['active' => !$record->active]);
                    })
                    ->requiresConfirmation(),
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
                Section::make(__('Comment Details'))
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->schema([
                        TextEntry::make('comment')
                            ->label(__('Comment'))
                            ->formatStateUsing(function (RatingComment $record) {
                                return $record->getCommentText(app()->getLocale());
                            })
                            ->columnSpanFull(),
                        TextEntry::make('is_positive')
                            ->label(__('Comment Type'))
                            ->badge()
                            ->formatStateUsing(fn (bool $state): string => $state ? __('Positive') : __('Negative'))
                            ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                            ->icon(fn (bool $state): string => $state ? 'heroicon-o-face-smile' : 'heroicon-o-face-frown'),
                        TextEntry::make('active')
                            ->label(__('Status'))
                            ->badge()
                            ->formatStateUsing(fn (bool $state): string => $state ? __('Active') : __('Inactive'))
                            ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
                    ])->columns(2),

                
            ]); 

    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRatingComments::route('/'),
            'create' => Pages\CreateRatingComment::route('/create'),
            'view' => Pages\ViewRatingComment::route('/{record}'),
            'edit' => Pages\EditRatingComment::route('/{record}/edit'),
        ];
    }



    public static function getLabel(): string
    {
        return __('Rating Comment');
    }

    public static function getPluralLabel(): string
    {
        return __('Rating Comments');
    }
}