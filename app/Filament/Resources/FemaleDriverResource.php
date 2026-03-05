<?php

namespace App\Filament\Resources;

use App\Enums\ApprovalStatus;
use App\Filament\Resources\FemaleDriverResource\Pages;
use Illuminate\Database\Eloquent\Builder;

class FemaleDriverResource extends BaseDriverResource
{
    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?string $navigationLabel = 'السائقات';
    protected static ?string $slug = 'female-drivers';
    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('approval_status', ApprovalStatus::APPROVED->value)
            ->whereHas('user', fn ($q) => $q->where('gender', 'female'));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFemaleDrivers::route('/'),
            'view'  => Pages\ViewFemaleDriver::route('/{record}'),
            'edit'  => Pages\EditFemaleDriver::route('/{record}/edit'),
        ];
    }
}
