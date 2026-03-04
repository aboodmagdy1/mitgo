<?php

namespace App\Filament\Resources\RatingCommentResource\Pages;

use App\Filament\Resources\RatingCommentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRatingComments extends ListRecords
{
    use ListRecords\Concerns\Translatable;
    protected static string $resource = RatingCommentResource::class;

    public function getTitle(): string
    {
        return __('Rating Comments');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label(__('Create Rating Comment')),
            Actions\LocaleSwitcher::make(),
        ];
    }
}
