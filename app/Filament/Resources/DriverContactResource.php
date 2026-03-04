<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DriverContactResource\Pages;

class DriverContactResource extends ContactResourceBase
{
    public static function getNavigationLabel(): string
    {
        return __('Drivers Section');
    }

    public static function getModelLabel(): string
    {
        return __('Driver Contact Message');
    }

    public static function getPluralLabel(): ?string
    {
        return __('Driver Contact Messages');
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    protected static function getSource(): int
    {
        return 1;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDriverContacts::route('/'),
            'view' => Pages\ViewDriverContact::route('/{record}'),
        ];
    }
}
