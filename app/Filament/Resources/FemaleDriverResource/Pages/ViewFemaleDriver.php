<?php

namespace App\Filament\Resources\FemaleDriverResource\Pages;

use App\Filament\Resources\FemaleDriverResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewFemaleDriver extends ViewRecord
{
    protected static string $resource = FemaleDriverResource::class;

    protected ?string $maxContentWidth = 'full';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\Action::make('toggle_active')
                ->label(fn ($record) => $record->user->is_active ? 'إلغاء التفعيل' : 'تفعيل')
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
                        ->title($record->user->is_active ? 'تم تفعيل السائق بنجاح' : 'تم إلغاء تفعيل السائق بنجاح')
                        ->success()->send();
                })
                ->requiresConfirmation()
                ->modalHeading(fn ($record) => $record->user->is_active ? 'إلغاء تفعيل السائق' : 'تفعيل السائق')
                ->modalDescription(fn ($record) => $record->user->is_active
                    ? 'هل أنت متأكد من إلغاء تفعيل هذا السائق؟ لن يتمكن من تسجيل الدخول.'
                    : 'هل أنت متأكد من تفعيل حساب هذا السائق؟')
                ->modalSubmitActionLabel(fn ($record) => $record->user->is_active ? 'إلغاء التفعيل' : 'تفعيل'),

            Actions\Action::make('withdraw')
                ->label('سحب')
                ->icon('heroicon-o-minus-circle')
                ->color('danger')
                ->form([
                    Forms\Components\TextInput::make('amount')
                        ->label('المبلغ')
                        ->numeric()->required()->step(0.01)->minValue(0.01)->prefix('SAR')
                        ->helperText(fn () => 'الرصيد الحالي: ' . $this->record->getFormattedBalanceAttribute()),
                    Forms\Components\Textarea::make('notes')
                        ->label('الملاحظات')->required()->rows(3),
                ])
                ->action(function (array $data): void {
                    $amount = $data['amount'] * 100;
                    if (! $this->record->canWithdraw($amount)) {
                        Notification::make()->title('رصيد غير كافي')->danger()->send();
                        return;
                    }
                    $this->record->withdraw($amount, ['description' => 'Admin withdrawal', 'notes' => $data['notes'], 'admin_id' => Auth::id()]);
                    Notification::make()->title('تم السحب بنجاح')->success()->send();
                })
                ->requiresConfirmation()
                ->modalHeading('سحب من محفظة السائق')
                ->modalSubmitActionLabel('سحب'),

            Actions\Action::make('deposit')
                ->label('إيداع')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->form([
                    Forms\Components\TextInput::make('amount')
                        ->label('المبلغ')->numeric()->required()->prefix('SAR'),
                    Forms\Components\Textarea::make('notes')
                        ->label('الملاحظات')->required()->rows(3),
                ])
                ->action(function (array $data): void {
                    $amount = $data['amount'] * 100;
                    $this->record->deposit($amount, ['description' => 'Admin deposit', 'notes' => $data['notes'], 'admin_id' => Auth::id()]);
                    Notification::make()->title('تم الإيداع بنجاح')->success()->send();
                })
                ->requiresConfirmation()
                ->modalHeading('إيداع في محفظة السائق')
                ->modalSubmitActionLabel('إيداع'),
        ];
    }
}
