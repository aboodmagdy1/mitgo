<?php

namespace App\Filament\Resources\DriverContactResource\Pages;

use App\Filament\Resources\ContactResource\Concerns\HasContactInfolist;
use App\Filament\Resources\DriverContactResource;
use Filament\Actions;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewDriverContact extends ViewRecord
{
    use HasContactInfolist;

    protected static string $resource = DriverContactResource::class;

    public function getTitle(): string
    {
        return 'تفاصيل رسالة الاتصال';
    }

    public function getHeading(): string
    {
        return 'رسالة من ' . $this->record->name;
    }

    public function getSubheading(): string
    {
        return 'تم الاستلام في ' . $this->record->created_at->format('F j, Y \a\t g:i A');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('is_read')
                ->label('تعليم كمغلق')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn ($record) => $record->is_read == false)
                ->action(function () {
                    $this->record->update(['is_read' => true]);
                    $this->record->save();
                    \Filament\Notifications\Notification::make()
                        ->title('تم تعليم رسالة الاتصال كمغلق')
                        ->success()
                        ->send();
                }),

            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('حذف رسالة الاتصال')
                ->modalDescription('هل أنت متأكد من حذف رسالة الاتصال هذه؟ لا يمكن التراجع عن هذا الإجراء.')
                ->modalSubmitActionLabel('حذف')
                ->modalCancelActionLabel('إلغاء'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $this->contactInfolist($infolist);
    }
}
