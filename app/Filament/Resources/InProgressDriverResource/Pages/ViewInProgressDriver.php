<?php

namespace App\Filament\Resources\InProgressDriverResource\Pages;

use App\Filament\Resources\InProgressDriverResource;
use App\Models\VehicleType;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewInProgressDriver extends ViewRecord
{
    protected static string $resource = InProgressDriverResource::class;

    protected ?string $maxContentWidth = 'full';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('approve_driver')
                ->label(__('Approve Driver'))
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn ($record) => $record->isInProgress())
                ->form([
                    Forms\Components\Select::make('vehicle_type_id')
                        ->label(__('Vehicle Type'))
                        ->options(VehicleType::where('active', true)->pluck('name', 'id'))
                        ->required()
                        ->helperText(__('Please select a vehicle type for this driver when approving them.')),
                ])
                ->action(function ($record, array $data) {
                    $approved = $record->approve();
                    if (! $approved) {
                        Notification::make()->title(__('Driver is already approved'))->warning()->send();
                        return;
                    }
                    if (! empty($data['vehicle_type_id'])) {
                        if ($record->vehicle) {
                            $record->vehicle->update(['vehicle_type_id' => $data['vehicle_type_id']]);
                        } else {
                            $record->vehicle()->create([
                                'vehicle_type_id'        => $data['vehicle_type_id'],
                                'seats'                  => 4,
                                'color'                  => null,
                                'license_number'         => null,
                                'plate_number'           => null,
                                'vehicle_brand_model_id' => null,
                            ]);
                        }
                    }
                    $record->user->update(['is_active' => true]);
                    Notification::make()
                        ->title(__('Driver approved successfully'))
                        ->body(__('Driver can now receive trip requests when active and online.'))
                        ->success()
                        ->send();
                    $this->redirect(
                        \App\Filament\Resources\MaleDriverResource::getUrl('view', ['record' => $record])
                    );
                })
                ->requiresConfirmation()
                ->modalHeading(__('Approve Driver'))
                ->modalDescription(__('Are you sure you want to approve this driver? This action cannot be undone.'))
                ->modalSubmitActionLabel(__('Approve Driver')),

            Actions\Action::make('reject_driver')
                ->label(__('Reject'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn ($record) => $record->isInProgress())
                ->requiresConfirmation()
                ->modalHeading(__('Reject Driver'))
                ->modalDescription(__('Are you sure you want to reject this driver application? The driver will be notified.'))
                ->action(function ($record) {
                    $record->reject();
                    Notification::make()
                        ->title(__('Driver application rejected.'))
                        ->danger()
                        ->send();
                    $this->redirect(
                        \App\Filament\Resources\RejectedDriverResource::getUrl('index')
                    );
                }),
        ];
    }
}
