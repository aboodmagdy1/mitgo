<?php

namespace App\Filament\Resources\RejectedDriverResource\Pages;

use App\Filament\Resources\RejectedDriverResource;
use Filament\Resources\Pages\ViewRecord;

class ViewRejectedDriver extends ViewRecord
{
    protected static string $resource = RejectedDriverResource::class;

    protected ?string $maxContentWidth = 'full';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
