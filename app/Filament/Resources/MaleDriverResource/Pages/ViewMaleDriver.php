<?php

namespace App\Filament\Resources\MaleDriverResource\Pages;

use App\Filament\Resources\MaleDriverResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewMaleDriver extends ViewRecord
{
    protected static string $resource = MaleDriverResource::class;

    protected ?string $maxContentWidth = 'full';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\Action::make('toggle_active')
                ->label(fn ($record) => $record->user->is_active ? __('Deactivate') : __('Activate'))
                ->icon(fn ($record) => $record->user->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                ->color(fn ($record) => $record->user->is_active ? 'danger' : 'success')
                ->action(function ($record) {
                    $record->user->update(['is_active' => ! $record->user->is_active]);
                    if (! $record->user->is_active) {
                        $record->update(['status' => 0]);
                        $record->user->fcmTokens()->delete();
                        $record->user->tokens()->delete();
                    }
                    Notification::make()
                        ->title($record->user->is_active ? __('Driver activated successfully') : __('Driver deactivated successfully'))
                        ->success()->send();
                })
                ->requiresConfirmation()
                ->modalHeading(fn ($record) => $record->user->is_active ? __('Deactivate Driver') : __('Activate Driver'))
                ->modalDescription(fn ($record) => $record->user->is_active
                    ? __('Are you sure you want to deactivate this driver? They will not be able to login.')
                    : __('Are you sure you want to activate this driver account?'))
                ->modalSubmitActionLabel(fn ($record) => $record->user->is_active ? __('Deactivate') : __('Activate')),

            Actions\Action::make('withdraw')
                ->label(__('wallet.withdraw'))
                ->icon('heroicon-o-minus-circle')
                ->color('danger')
                ->form([
                    Forms\Components\TextInput::make('amount')
                        ->label(__('wallet.amount'))
                        ->numeric()->required()->step(0.01)->minValue(0.01)->prefix('SAR')
                        ->helperText(fn () => __('wallet.current_balance') . ': ' . $this->record->getFormattedBalanceAttribute()),
                    Forms\Components\Textarea::make('notes')
                        ->label(__('wallet.notes'))->required()->rows(3),
                ])
                ->action(function (array $data): void {
                    $amount = $data['amount'] * 100;
                    if (! $this->record->canWithdraw($amount)) {
                        Notification::make()->title(__('wallet.insufficient_balance'))->danger()->send();
                        return;
                    }
                    $this->record->withdraw($amount, ['description' => 'Admin withdrawal', 'notes' => $data['notes'], 'admin_id' => Auth::id()]);
                    Notification::make()->title(__('wallet.withdrawal_successful'))->success()->send();
                })
                ->requiresConfirmation()
                ->modalHeading(__('wallet.withdraw_from_wallet'))
                ->modalSubmitActionLabel(__('wallet.withdraw')),

            Actions\Action::make('deposit')
                ->label(__('wallet.deposit'))
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->form([
                    Forms\Components\TextInput::make('amount')
                        ->label(__('wallet.amount'))->numeric()->required()->prefix('SAR'),
                    Forms\Components\Textarea::make('notes')
                        ->label(__('wallet.notes'))->required()->rows(3),
                ])
                ->action(function (array $data): void {
                    $amount = $data['amount'] * 100;
                    $this->record->deposit($amount, ['description' => 'Admin deposit', 'notes' => $data['notes'], 'admin_id' => Auth::id()]);
                    Notification::make()->title(__('wallet.deposit_successful'))->success()->send();
                })
                ->requiresConfirmation()
                ->modalHeading(__('wallet.deposit_to_wallet'))
                ->modalSubmitActionLabel(__('wallet.deposit')),
        ];
    }
}
