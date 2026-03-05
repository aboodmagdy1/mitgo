<?php

namespace App\Filament\Resources;

use App\Enums\ApprovalStatus;
use App\Filament\Resources\InProgressDriverResource\Pages;
use Illuminate\Database\Eloquent\Builder;

class InProgressDriverResource extends BaseDriverResource
{
    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';
    protected static ?string $navigationLabel = 'قسم المعاينة';
    protected static ?string $slug = 'inprogress-drivers';
    protected static ?int $navigationSort = 4;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('approval_status', ApprovalStatus::IN_PROGRESS->value)
            ->whereHas('user', fn ($q) => $q->where('is_active', false));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInProgressDrivers::route('/'),
            'view'  => Pages\ViewInProgressDriver::route('/{record}'),
        ];
    }
}
