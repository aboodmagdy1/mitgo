<?php

namespace App\Filament\Resources\CustomerContactResource\Pages;

use App\Filament\Resources\ContactResource\Concerns\HasContactInfolist;
use App\Filament\Resources\CustomerContactResource;
use Filament\Actions;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomerContact extends ViewRecord
{
    use HasContactInfolist;

    protected static string $resource = CustomerContactResource::class;

    public function getTitle(): string
    {
        return __('Contact Message Details');
    }

    public function getHeading(): string
    {
        return __('Contact from :name', ['name' => $this->record->name]);
    }

    public function getSubheading(): string
    {
        return __('Received on :date', ['date' => $this->record->created_at->format('F j, Y \a\t g:i A')]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('is_read')
                ->label(__('Mark As Closed'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn ($record) => $record->is_read == false)
                ->action(function () {
                    $this->record->update(['is_read' => true]);
                    $this->record->save();
                    \Filament\Notifications\Notification::make()
                        ->title(__('Contact marked as closed'))
                        ->success()
                        ->send();
                }),

            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading(__('Delete Contact Message'))
                ->modalDescription(__('Are you sure you want to delete this contact message? This action cannot be undone.'))
                ->modalSubmitActionLabel(__('Delete'))
                ->modalCancelActionLabel(__('Cancel')),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $this->contactInfolist($infolist);
    }
}
