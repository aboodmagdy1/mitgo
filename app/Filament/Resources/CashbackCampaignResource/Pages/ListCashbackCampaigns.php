<?php

namespace App\Filament\Resources\CashbackCampaignResource\Pages;

use App\Filament\Resources\CashbackCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCashbackCampaigns extends ListRecords
{
    protected static string $resource = CashbackCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('إضافة حملة كاش باك'),
        ];
    }

    public function getTitle(): string
    {
        return 'حملات الكاش باك';
    }
}

