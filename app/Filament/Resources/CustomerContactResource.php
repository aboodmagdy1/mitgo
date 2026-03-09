<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerContactResource\Pages;

class CustomerContactResource extends ContactResourceBase
{
    public static function getNavigationLabel(): string
    {
        return 'قسم العملاء';
    }

    public static function getModelLabel(): string
    {
        return 'رسالة تواصل عميل';
    }

    public static function getPluralLabel(): ?string
    {
        return 'رسائل تواصل العملاء';
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
