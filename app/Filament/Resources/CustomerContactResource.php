<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerContactResource\Pages;

class CustomerContactResource extends ContactResourceBase
{
    public static function getNavigationLabel(): string
    {
        return __('Customers Section');
    }

    public static function getModelLabel(): string
    {
        return __('Customer Contact Message');
    }

    public static function getPluralLabel(): ?string
    {
        return __('Customer Contact Messages');
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    protected static function getSource(): int
    {
        return 0;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomerContacts::route('/'),
            'view' => Pages\ViewCustomerContact::route('/{record}'),
        ];
    }
}
