<?php

namespace App\Filament\Resources;

use App\Enums\ApprovalStatus;
use App\Filament\Resources\PendingDriverResource\Pages;
use Illuminate\Database\Eloquent\Builder;

class PendingDriverResource extends BaseDriverResource
{
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'المتقدمين';
    protected static ?string $slug = 'pending-drivers';
    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('approval_status', ApprovalStatus::PENDING->value)
            ->whereHas('user', fn ($q) => $q->where('is_active', false));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPendingDrivers::route('/'),
            'view'  => Pages\ViewPendingDriver::route('/{record}'),
        ];
    }
}
