<?php

namespace App\Filament\Resources\DriverWithdrawRequestResource\Pages;

use App\Filament\Resources\DriverWithdrawRequestResource;
use App\Models\DriverWithdrawRequest;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Forms;
use Filament\Notifications\Notification;

class ViewDriverWithdrawRequest extends ViewRecord
{
    protected static string $resource = DriverWithdrawRequestResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make(__('wallet.driver_wallet_information'))
                    ->schema([
                        TextEntry::make('driver.user.name')
                            ->label(__('wallet.driver_name'))
                            ->weight('bold')
                            ->color('primary'),
                        TextEntry::make('driver.user.phone')
                            ->label(__('wallet.driver_phone')),
                        TextEntry::make('current_balance')
                            ->label(__('wallet.current_balance'))
                            ->state(function (DriverWithdrawRequest $record): string {
                                $balance = $record->driver->user->balance / 100 ?? 0;
                                return number_format($balance,2) . ' SAR';
                            })
                            ->weight('bold')
                            ->color('success'),
                    ])
                    ->columns(3),
                
                Section::make(__('wallet.withdraw_request_information'))
                    ->schema([
                        TextEntry::make('amount')
                            ->label(__('wallet.requested_amount'))
                            ->weight('bold')
                            ->state(function (DriverWithdrawRequest $record): string {
                                return $record->amount . ' SAR';
                            })
                            ->color('warning'),
                        TextEntry::make('is_approved')
                            ->label(__('wallet.approval_status'))
                            ->badge()
                            ->formatStateUsing(fn (bool $state): string => $state ? __('wallet.completed') : __('wallet.pending'))
                            ->color(fn (bool $state): string => $state ? 'success' : 'warning'),
                        TextEntry::make('created_at')
                            ->label(__('wallet.request_date'))
                            ->dateTime(),
                        TextEntry::make('notes')
                            ->label(__('wallet.notes'))
                            ->placeholder(__('wallet.no_notes_available'))
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('mark_completed')
                ->label(__('wallet.mark_as_completed'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (DriverWithdrawRequest $record): bool => !$record->is_approved)
                ->form([
                    Forms\Components\Textarea::make('completion_notes')
                        ->label(__('wallet.completion_notes'))
                        ->placeholder(__('wallet.completion_notes_placeholder'))
                        ->rows(3),
                ])
                ->action(function (DriverWithdrawRequest $record, array $data): void {
                    // Update the request as completed
                    $record->update([
                        'is_approved' => true,
                        'notes' => ($record->notes ? $record->notes . "\n\n" : '') . 
                                  __('wallet.admin_notes') . ": " . ($data['completion_notes'] ?? __('wallet.no_additional_notes')),
                    ]);

                    Notification::make()
                        ->title(__('wallet.request_marked_completed'))
                        ->body(__('wallet.withdrawal_request_completed'))
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading(__('wallet.mark_as_completed'))
                ->modalDescription(__('wallet.mark_completed_description'))
                ->modalSubmitActionLabel(__('wallet.mark_as_completed')),
            //    Actions\EditAction::make(),
        ];
    }
}
