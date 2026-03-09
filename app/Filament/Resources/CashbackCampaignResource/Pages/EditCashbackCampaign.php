<?php

namespace App\Filament\Resources\CashbackCampaignResource\Pages;

use App\Filament\Resources\CashbackCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCashbackCampaign extends EditRecord
{
    protected static string $resource = CashbackCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return 'تعديل حملة كاش باك';
    }
}

