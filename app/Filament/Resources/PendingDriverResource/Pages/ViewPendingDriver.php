<?php

namespace App\Filament\Resources\PendingDriverResource\Pages;

use App\Filament\Resources\PendingDriverResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewPendingDriver extends ViewRecord
{
    protected static string $resource = PendingDriverResource::class;

    protected ?string $maxContentWidth = 'full';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('move_to_in_progress')
                ->label(__('Move to Inspection'))
                ->icon('heroicon-o-magnifying-glass')
                ->color('info')
                ->visible(fn ($record) => $record->isPendingApproval())
                ->requiresConfirmation()
                ->modalHeading(__('Move to Inspection'))
                ->modalDescription(__('Are you sure you want to move this driver to the inspection stage?'))
                ->action(function ($record) {
                    $record->moveToInProgress();
                    Notification::make()
                        ->title(__('Driver moved to inspection stage.'))
                        ->info()
                        ->send();
                    $this->redirect(
                        \App\Filament\Resources\InProgressDriverResource::getUrl('view', ['record' => $record])
                    );
                }),
        ];
    }
}
