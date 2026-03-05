<?php

namespace App\Filament\Resources;

use App\Enums\ApprovalStatus;
use App\Filament\Resources\MaleDriverResource\Pages;
use Illuminate\Database\Eloquent\Builder;

class MaleDriverResource extends BaseDriverResource
{
    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?string $navigationLabel = 'السائقين';
    protected static ?string $slug = 'male-drivers';
    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('approval_status', ApprovalStatus::APPROVED->value)
            ->whereHas('user', fn ($q) => $q->where('gender', 'male'));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMaleDrivers::route('/'),
            'view'  => Pages\ViewMaleDriver::route('/{record}'),
            'edit'  => Pages\EditMaleDriver::route('/{record}/edit'),
        ];
    }
}
