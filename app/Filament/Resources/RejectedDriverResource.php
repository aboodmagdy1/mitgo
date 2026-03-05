<?php

namespace App\Filament\Resources;

use App\Enums\ApprovalStatus;
use App\Filament\Resources\RejectedDriverResource\Pages;
use Illuminate\Database\Eloquent\Builder;

class RejectedDriverResource extends BaseDriverResource
{
    protected static ?string $navigationIcon = 'heroicon-o-x-circle';
    protected static ?string $navigationLabel = 'قسم الرفض';
    protected static ?string $slug = 'rejected-drivers';
    protected static ?int $navigationSort = 5;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('approval_status', ApprovalStatus::REJECTED->value)
            ->whereHas('user', fn ($q) => $q->where('is_active', false));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRejectedDrivers::route('/'),
            'view'  => Pages\ViewRejectedDriver::route('/{record}'),
        ];
    }
}
