<?php

namespace App\Filament\Resources\RatingCommentResource\Pages;

use App\Filament\Resources\RatingCommentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRatingComment extends ViewRecord
{
    use ViewRecord\Concerns\Translatable;
    protected static string $resource = RatingCommentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

        ];
    }
}
