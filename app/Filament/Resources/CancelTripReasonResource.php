<?php

namespace App\Filament\Resources;

use App\Enums\CancelTripReasonType;
use App\Filament\Resources\CancelTripReasonResource\Pages;
use App\Models\CancelTripReason;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

    class CancelTripReasonResource extends Resource
    {
        use Translatable;
    protected static ?string $model = CancelTripReason::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    public static function getNavigationLabel(): string
    {
        return 'أسباب إلغاء الرحلة';
    }

    public static function getModelLabel(): string
    {
        return 'سبب إلغاء الرحلة';
    }

    public static function getPluralModelLabel(): string
    {
        return 'أسباب إلغاء الرحلة';
    }

    public static function getNavigationGroup(): ?string
    {
        return "المحتوى المعلومي";
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('reason')
                    ->label('السبب')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Forms\Components\Select::make('type')
                    ->label('النوع')
                    ->options([
                        CancelTripReasonType::Rider->value => CancelTripReasonType::Rider->label(),
                        CancelTripReasonType::Driver->value => CancelTripReasonType::Driver->label(),
                    ])
                    ->required()
                    ->native(false),

                Forms\Components\Toggle::make('is_active')
                    ->label('نشط')
                    ->default(true)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('الرقم')
                    ->sortable(),

                Tables\Columns\TextColumn::make('reason')
                    ->label('السبب')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('النوع')
                    ->formatStateUsing(fn (CancelTripReasonType $state): string => $state->label())
                    ->badge()
                    ->color(fn (CancelTripReasonType $state): string => match ($state) {
                        CancelTripReasonType::Rider => 'info',
                        CancelTripReasonType::Driver => 'warning',
                    })
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean()
                    ->sortable(),

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
                Tables\Filters\SelectFilter::make('type')
                    ->label('النوع')
                    ->options([
                        CancelTripReasonType::Rider->value => CancelTripReasonType::Rider->label(),
                        CancelTripReasonType::Driver->value => CancelTripReasonType::Driver->label(),
                    ])
                    ->native(false),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('نشط')
                    ->boolean()
                    ->trueLabel('النشط فقط')
                    ->falseLabel('غير النشط فقط')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggle_active')
                    ->label(fn ($record) => $record->is_active ? 'إلغاء التفعيل' : 'تفعيل')
                    ->icon(fn ($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                    ->action(function ($record) {
                        $record->update(['is_active' => !$record->is_active]);
                        
                        $message = $record->is_active 
                            ? 'تم تفعيل سبب الإلغاء بنجاح'
                            : 'تم إلغاء تفعيل سبب الإلغاء بنجاح';
                            
                        \Filament\Notifications\Notification::make()
                            ->title($message)
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record) => $record->is_active ? 'إلغاء التفعيل لسبب الإلغاء' : 'تفعيل سبب الإلغاء')
                    ->modalDescription(fn ($record) => $record->is_active 
                        ? 'هل أنت متأكد من أنك تريد إلغاء تفعيل هذا السبب؟'
                        : 'هل أنت متأكد من أنك تريد تفعيل هذا السبب؟')
                    ->modalSubmitActionLabel(fn ($record) => $record->is_active ? 'إلغاء التفعيل' : 'تفعيل'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCancelTripReasons::route('/'),
            'create' => Pages\CreateCancelTripReason::route('/create'),
            'edit' => Pages\EditCancelTripReason::route('/{record}/edit'),
        ];
    }
}
