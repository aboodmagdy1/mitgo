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
        return 'الأسئلة الشائعة';
    }

    public static function getModelLabel(): string
    {
        return 'سؤال شائع';
    }

    public static function getPluralModelLabel(): string
    {
        return 'الأسئلة الشائعة';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات السؤال الشائع')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('العنوان')
                            ->required()
                            ->maxLength(255)
                            ->rules([
                                function () {
                                    return function (string $attribute, $value, \Closure $fail) {
                                        if (is_array($value)) {
                                            if (empty($value['en']) && empty($value['ar'])) {
                                                $fail('العنوان مطلوب بلغة واحدة على الأقل');
                                            }
                                        }
                                    };
                                },
                            ]),
                        
                        Forms\Components\RichEditor::make('description')
                            ->label('الوصف')
                            ->required()
                            ->rules([
                                function () {
                                    return function (string $attribute, $value, \Closure $fail) {
                                        if (is_array($value)) {
                                            if (empty($value['en']) && empty($value['ar'])) {
                                                $fail('الوصف مطلوب بلغة واحدة على الأقل');
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
                
                Forms\Components\Section::make('الإعدادات')
                    ->schema([
                        Forms\Components\Toggle::make('active')
                            ->label('نشط')
                            ->default(true),
                        
                        Forms\Components\TextInput::make('sort_order')
                            ->label('ترتيب العرض')
                            ->numeric()
                            ->default(0)
                            ->helperText('الأرقام الأقل تظهر أولاً'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('active')
                    ->label('نشط')
                    ->boolean()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('ترتيب العرض')
                    ->sortable()
                    ->alignCenter(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('تاريخ التحديث')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('active')
                    ->label('نشط')
                    ->boolean()
                    ->trueLabel('النشط فقط')
                    ->falseLabel('غير النشط فقط')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد'),
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
        return "المحتوى المعلومي";
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
                        $fail('العنوان مطلوب بالإنجليزية');
                    }
                    if (!isset($value['ar']) || empty(trim($value['ar']))) {
                        $fail('العنوان مطلوب بالعربية');
                    }
                },
            ],
            'description' => [
                'required',
                'array',
                function ($attribute, $value, $fail) {
                    if (!isset($value['en']) || empty(trim($value['en']))) {
                        $fail('الوصف مطلوب بالإنجليزية');
                    }
                    if (!isset($value['ar']) || empty(trim($value['ar']))) {
                        $fail('الوصف مطلوب بالعربية');
                    }
                },
            ],
        ];
    }
}
