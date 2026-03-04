<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FaqResource\Pages;
use App\Filament\Resources\FaqResource\RelationManagers;
use App\Models\Faq;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FaqResource extends Resource
{
    use Translatable;
    protected static ?string $model = Faq::class;

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?string $navigationGroup = 'Content';

    public static function getNavigationLabel(): string
    {
        return __('FAQs');
    }

    public static function getModelLabel(): string
    {
        return __('FAQ');
    }

    public static function getPluralModelLabel(): string
    {
        return __('FAQs');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('FAQ Information'))
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label(__('Title'))
                            ->required()
                            ->maxLength(255)
                            ->translateLabel()
                            ->rules([
                                function () {
                                    return function (string $attribute, $value, \Closure $fail) {
                                        if (is_array($value)) {
                                            if (empty($value['en']) && empty($value['ar'])) {
                                                $fail(__('Title is required in at least one language'));
                                            }
                                        }
                                    };
                                },
                            ]),
                        
                        Forms\Components\RichEditor::make('description')
                            ->label(__('Description'))
                            ->required()
                            ->translateLabel()
                            ->rules([
                                function () {
                                    return function (string $attribute, $value, \Closure $fail) {
                                        if (is_array($value)) {
                                            if (empty($value['en']) && empty($value['ar'])) {
                                                $fail(__('Description is required in at least one language'));
                                            }
                                        }
                                    };
                                },
                            ])
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'orderedList',
                                'bulletList',
                                'link',
                            ]),
                    ])
                    ->columns(1),
                
                Forms\Components\Section::make(__('Settings'))
                    ->schema([
                        Forms\Components\Toggle::make('active')
                            ->label(__('Active'))
                            ->default(true),
                        
                        Forms\Components\TextInput::make('sort_order')
                            ->label(__('Sort Order'))
                            ->numeric()
                            ->default(0)
                            ->helperText(__('Lower numbers appear first')),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label(__('Title'))
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('active')
                    ->label(__('Active'))
                    ->boolean()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('sort_order')
                    ->label(__('Sort Order'))
                    ->sortable()
                    ->alignCenter(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('Updated At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('active')
                    ->label(__('Active'))
                    ->boolean()
                    ->trueLabel(__('Active only'))
                    ->falseLabel(__('Inactive only'))
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label(__('Edit')),
                Tables\Actions\DeleteAction::make()
                    ->label(__('Delete')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label(__('Delete Selected')),
                ]),
            ])
            ->defaultSort('sort_order');
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
            'index' => Pages\ListFaqs::route('/'),
            'create' => Pages\CreateFaq::route('/create'),
            'edit' => Pages\EditFaq::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return __("Informative Content");
    }

    public static function getTranslatableLocales(): array
    {
        return ['en', 'ar'];
    }

    protected static function getValidationRules(): array
    {
        return [
            'title' => [
                'required',
                'array',
                function ($attribute, $value, $fail) {
                    if (!isset($value['en']) || empty(trim($value['en']))) {
                        $fail(__('Title is required in English'));
                    }
                    if (!isset($value['ar']) || empty(trim($value['ar']))) {
                        $fail(__('Title is required in Arabic'));
                    }
                },
            ],
            'description' => [
                'required',
                'array',
                function ($attribute, $value, $fail) {
                    if (!isset($value['en']) || empty(trim($value['en']))) {
                        $fail(__('Description is required in English'));
                    }
                    if (!isset($value['ar']) || empty(trim($value['ar']))) {
                        $fail(__('Description is required in Arabic'));
                    }
                },
            ],
        ];
    }
}
