<?php

namespace App\Filament\Resources\ZonePricingModifierResource\Pages;

use App\Filament\Resources\ZonePricingModifierResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditZonePricingModifier extends EditRecord
{
    protected static string $resource = ZonePricingModifierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
