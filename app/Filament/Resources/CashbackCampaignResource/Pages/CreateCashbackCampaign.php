<?php

namespace App\Filament\Resources\CashbackCampaignResource\Pages;

use App\Filament\Resources\CashbackCampaignResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCashbackCampaign extends CreateRecord
{
    protected static string $resource = CashbackCampaignResource::class;

    public function getTitle(): string
    {
        return 'إنشاء حملة كاش باك';
    }
}

