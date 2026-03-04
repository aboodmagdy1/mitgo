<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DriverWithdrawRequestResource\Pages;
use App\Models\DriverWithdrawRequest;
use App\Models\Driver;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class DriverWithdrawRequestResource extends Resource
{
    protected static ?string $model = DriverWithdrawRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('Finance');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('driver_id')
                    ->label(__('Driver'))
                    ->relationship('driver.user', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('amount')
                    ->label(__('Amount'))
                    ->numeric()
                    ->required()
                    ->step(0.01)
                    ->minValue(0.01)
                    ->prefix('SAR'),
                Forms\Components\Toggle::make('is_approved')
                    ->label(__('Approved'))
                    ->default(false),
                Forms\Components\Textarea::make('notes')
                    ->label(__('Notes'))
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->columns([
                TextColumn::make('driver.user.name')
                    ->label(__('Driver Name'))
                    ->searchable()
                    ->sortable()
                    ->url(fn (DriverWithdrawRequest $record): string => 
                        route('filament.admin.resources.drivers.view', ['record' => $record->driver_id])
                    )
                    ->color('primary')
                    ->weight('medium'),
                TextColumn::make('driver.user.phone')
                    ->label(__('Driver Phone'))
                    ->searchable(),
                TextColumn::make('amount')
                    ->label(__('Amount'))
                    ->money('SAR')
                    ->sortable(),
                BadgeColumn::make('is_approved')
                    ->label(__('Status'))
                    ->formatStateUsing(fn (bool $state): string => $state ? __('wallet.completed') : __('wallet.pending'))
                    ->colors([
                        'warning' => false,
                        'success' => true,
                    ]),
                TextColumn::make('notes')
                    ->label(__('Notes'))
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),
                TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('is_approved')
                    ->label(__('Status'))
                    ->options([
                        0 => __('wallet.pending'),
                        1 => __('wallet.completed'),
                    ]),
            ])
            ->actions([
                Action::make('mark_completed')
                    ->label(__('wallet.mark_as_completed'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (DriverWithdrawRequest $record): bool => !$record->is_approved)
                    ->form([
                        Forms\Components\Textarea::make('completion_notes')
                            ->label(__('wallet.completion_notes'))
                            ->placeholder(__('Add any notes about completing this request...'))
                            ->rows(3),
                    ])
                    ->action(function (DriverWithdrawRequest $record, array $data): void {
                        // Update the request as completed
                        $record->update([
                            'is_approved' => true,
                            'notes' => ($record->notes ? $record->notes . "\n\n" : '') . 
                                      __('wallet.admin_notes') . ": " . ($data['completion_notes'] ?? __('No additional notes')),
                        ]);

                        Notification::make()
                            ->title(__('wallet.request_marked_completed'))
                            ->body(__('wallet.withdrawal_request_completed'))
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading(__('wallet.mark_as_completed'))
                    ->modalDescription(__('Mark this withdrawal request as completed. This is for record keeping only.'))
                    ->modalSubmitActionLabel(__('wallet.mark_as_completed')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDriverWithdrawRequests::route('/'),
            'create' => Pages\CreateDriverWithdrawRequest::route('/create'),
            'view' => Pages\ViewDriverWithdrawRequest::route('/{record}/view'),
            'edit' => Pages\EditDriverWithdrawRequest::route('/{record}/edit'),
        ];
    }

    public static function getLabel(): string
    {
        return __('Withdraw Request');
    }

    public static function getPluralLabel(): string
    {
        return __('Withdraw Requests');
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_approved', false)->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }
}
