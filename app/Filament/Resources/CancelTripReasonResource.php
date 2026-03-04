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
        return __('Cancel Trip Reasons');
    }

    public static function getModelLabel(): string
    {
        return __('Cancel Trip Reason');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Cancel Trip Reasons');
    }

    public static function getNavigationGroup(): ?string
    {
        return __("Informative Content");
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('reason')
                    ->label(__('Reason'))
                    ->required()
                    ->translateLabel()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Forms\Components\Select::make('type')
                    ->label(__('Type'))
                    ->options([
                        CancelTripReasonType::Rider->value => CancelTripReasonType::Rider->label(),
                        CancelTripReasonType::Driver->value => CancelTripReasonType::Driver->label(),
                    ])
                    ->required()
                    ->native(false),

                Forms\Components\Toggle::make('is_active')
                    ->label(__('Active'))
                    ->default(true)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label(__('ID'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('reason')
                    ->label(__('Reason'))
                    ->translateLabel()
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('Type'))
                    ->formatStateUsing(fn (CancelTripReasonType $state): string => $state->label())
                    ->badge()
                    ->color(fn (CancelTripReasonType $state): string => match ($state) {
                        CancelTripReasonType::Rider => 'info',
                        CancelTripReasonType::Driver => 'warning',
                    })
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('Active'))
                    ->boolean()
                    ->sortable(),

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
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('Type'))
                    ->options([
                        CancelTripReasonType::Rider->value => CancelTripReasonType::Rider->label(),
                        CancelTripReasonType::Driver->value => CancelTripReasonType::Driver->label(),
                    ])
                    ->native(false),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('Active'))
                    ->boolean()
                    ->trueLabel(__('Active only'))
                    ->falseLabel(__('Inactive only'))
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggle_active')
                    ->label(fn ($record) => $record->is_active ? __('Deactivate') : __('Activate'))
                    ->icon(fn ($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                    ->action(function ($record) {
                        $record->update(['is_active' => !$record->is_active]);
                        
                        $message = $record->is_active 
                            ? __('Cancel reason activated successfully')
                            : __('Cancel reason deactivated successfully');
                            
                        \Filament\Notifications\Notification::make()
                            ->title($message)
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record) => $record->is_active ? __('Deactivate Cancel Reason') : __('Activate Cancel Reason'))
                    ->modalDescription(fn ($record) => $record->is_active 
                        ? __('Are you sure you want to deactivate this cancel reason?')
                        : __('Are you sure you want to activate this cancel reason?'))
                    ->modalSubmitActionLabel(fn ($record) => $record->is_active ? __('Deactivate') : __('Activate')),
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
