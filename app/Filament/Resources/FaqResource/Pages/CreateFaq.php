<?php

namespace App\Filament\Resources\FaqResource\Pages;

use App\Filament\Resources\FaqResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateFaq extends CreateRecord
{
    use CreateRecord\Concerns\Translatable;
    protected static string $resource = FaqResource::class;

    public function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
        ];
    }

    public function getTitle(): string
    {
        return __('Create FAQ');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return __('FAQ created successfully');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-fill missing language content
        if (isset($data['title'])) {
            if (!empty($data['title']['en']) && empty($data['title']['ar'])) {
                $data['title']['ar'] = $data['title']['en'];
            } elseif (!empty($data['title']['ar']) && empty($data['title']['en'])) {
                $data['title']['en'] = $data['title']['ar'];
            }
        }

        if (isset($data['description'])) {
            if (!empty($data['description']['en']) && empty($data['description']['ar'])) {
                $data['description']['ar'] = $data['description']['en'];
            } elseif (!empty($data['description']['ar']) && empty($data['description']['en'])) {
                $data['description']['en'] = $data['description']['ar'];
            }
        }

        return $data;
    }

    protected function getValidationRules(): array
    {
        return [
            'title' => [
                'required',
                'array',
                function ($attribute, $value, $fail) {
                    if (!isset($value['en']) || empty(trim($value['en']))) {
                        $fail(__('Title is required in English'));
                    }
                    if (!isset($value['ar']) || empty(trim($value['ar']))) {
                        $fail(__('Title is required in Arabic'));
                    }
                },
            ],
            'description' => [
                'required',
                'array',
                function ($attribute, $value, $fail) {
                    if (!isset($value['en']) || empty(trim($value['en']))) {
                        $fail(__('Description is required in English'));
                    }
                    if (!isset($value['ar']) || empty(trim($value['ar']))) {
                        $fail(__('Description is required in Arabic'));
                    }
                },
            ],
        ];
    }
}
