<?php

namespace App\Filament\Pages;

use App\Settings\ContentSettings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\SettingsPage;

class ManageContent extends SettingsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $settings = ContentSettings::class;

    protected static ?int $navigationSort = 1001;
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\RichEditor::make('about_us_ar')
                    ->label('About Us Ar')
                    ->translateLabel()
                    ->toolbarButtons([
                        'bold',
                        'italic',
                        'underline',
                        'orderedList',
                        'bulletList',
                    ])
                    ->required(),
                Forms\Components\RichEditor::make('about_us_en')
                    ->label('About Us En')
                    ->translateLabel()
                    ->toolbarButtons([
                        'bold',
                        'italic',
                        'underline',
                        'orderedList',
                        'bulletList',
                    ])
                    ->required(),
                Forms\Components\RichEditor::make('privacy_ar')
                    ->label('Privacy Ar')
                    ->translateLabel()
                    ->toolbarButtons([
                        'bold',
                        'italic',
                        'underline',
                        'orderedList',
                        'bulletList',
                    ])
                    ->required(),
                Forms\Components\RichEditor::make('privacy_en')
                    ->label('Privacy En')
                    ->translateLabel()
                    ->toolbarButtons([
                        'bold',
                        'italic',
                        'underline',
                        'orderedList',
                        'bulletList',
                    ])
                    ->required(),
                // ...
            ]);
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Settings');
    }
    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable{
        return __('Manage Static Content');
    }

    public static function getNavigationLabel(): string
    {
        return __('Manage Static Content');
    }
}
