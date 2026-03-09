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
                Section::make('معلومات محفظة السائق')
                    ->schema([
                        TextEntry::make('driver.user.name')
                            ->label('اسم السائق')
                            ->weight('bold')
                            ->color('primary'),
                        TextEntry::make('driver.user.phone')
                            ->label('هاتف السائق'),
                        TextEntry::make('current_balance')
                            ->label('الرصيد الحالي')
                            ->state(function (DriverWithdrawRequest $record): string {
                                $balance = $record->driver->user->balance / 100 ?? 0;
                                return number_format($balance,2) . ' SAR';
                            })
                            ->weight('bold')
                            ->color('success'),
                    ])
                    ->columns(3),
                
                Section::make('معلومات طلب السحب')
                    ->schema([
                        TextEntry::make('amount')
                            ->label('المبلغ المطلوب')
                            ->weight('bold')
                            ->state(function (DriverWithdrawRequest $record): string {
                                return $record->amount . ' SAR';
                            })
                            ->color('warning'),
                        TextEntry::make('is_approved')
                            ->label('حالة الموافقة')
                            ->badge()
                            ->formatStateUsing(fn (bool $state): string => $state ? 'مكتمل' : 'قيد الانتظار')
                            ->color(fn (bool $state): string => $state ? 'success' : 'warning'),
                        TextEntry::make('created_at')
                            ->label('تاريخ الطلب')
                            ->dateTime(),
                        TextEntry::make('notes')
                            ->label('الملاحظات')
                            ->placeholder('لا توجد ملاحظات متاحة')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('mark_completed')
                ->label('تحديد كمكتمل')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (DriverWithdrawRequest $record): bool => !$record->is_approved)
                ->form([
                    Forms\Components\Textarea::make('completion_notes')
                    ->label('ملاحظات الإكمال')
                            ->placeholder('أضف ملاحظات الإكمال...')
                        ->rows(3),
                ])
                ->action(function (DriverWithdrawRequest $record, array $data): void {
                    // Update the request as completed
                    $record->update([
                        'is_approved' => true,
                        'notes' => ($record->notes ? $record->notes . "\n\n" : '') . 
                                  'ملاحظات الإدارة: ' . ($data['completion_notes'] ?? 'لا توجد ملاحظات إضافية'),
                    ]);

                    Notification::make()
                        ->title('تم تحديد الطلب كمكتمل')
                        ->body('تم إكمال طلب السحب بنجاح')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('تحديد كمكتمل')
                ->modalDescription('تحديد طلب السحب هذا كمكتمل. هذا للحفظ والتوثيق فقط.')
                ->modalSubmitActionLabel('تحديد كمكتمل'),
            //    Actions\EditAction::make(),
        ];
    }
}
