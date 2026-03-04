<?php

namespace App\Filament\Resources\RatingCommentResource\Pages;

use App\Filament\Resources\RatingCommentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRatingComment extends EditRecord
{
    use EditRecord\Concerns\Translatable;
    protected static string $resource = RatingCommentResource::class;

    public function getTitle(): string
    {
        return __('Edit Rating Comment');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\LocaleSwitcher::make(),
        ];
    }
}
