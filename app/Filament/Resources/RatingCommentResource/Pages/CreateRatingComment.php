<?php

namespace App\Filament\Resources\RatingCommentResource\Pages;

use App\Filament\Resources\RatingCommentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateRatingComment extends CreateRecord
{
    use CreateRecord\Concerns\Translatable;
    protected static string $resource = RatingCommentResource::class;

    public function getTitle(): string
    {
        return __('Create Rating Comment');
    }
    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
        ];
    }
}
