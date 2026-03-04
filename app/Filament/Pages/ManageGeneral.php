<?php

namespace App\Filament\Pages;

    use App\Settings\GeneralSettings;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Pages\SettingsPage;

class ManageGeneral extends SettingsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $settings = GeneralSettings::class;
    protected static ?int $navigationSort =1000;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                
             
                Section::make(__('Timers'))
                ->schema([

                    Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('driver_acceptance_time')
                        ->label('Driver Acceptance Time')
                        ->translateLabel()
                        ->numeric()
                        ->step(1)
                        ->minValue(0)
                        ->suffix('sconds')
                        ->required(),
                    ])
                    ->columns(2),
                    Forms\Components\TextInput::make('search_wave_time')
                    ->label('Search Wave Time')
                    ->translateLabel()
                    ->suffix('sconds')

                    ->helperText(__('the time the wave takes to search for drivers'))
                    ->required(),
                    Forms\Components\TextInput::make('search_wave_count')
                    ->label('Search Wave Count')
                    ->translateLabel()
                    ->helperText(__('the number of waves to search for drivers'))
                    ->required(),
                    Forms\Components\TextInput::make('free_waiting_time')
                    ->label('Free Waiting Time')
                    ->translateLabel()
                    ->suffix('minutes')

                    ->helperText(__('the free time that dirver wait for the cleint'))
                    ->required(),
                  
                ])
                ->icon('heroicon-o-clock')
                ->columns(2),
                Section::make(__('Commission'))
                ->schema([
                    Forms\Components\TextInput::make('commission_rate')
                    ->label('Commission Rate')
                    ->translateLabel()
                    ->required(),
                ])
                ->icon('heroicon-o-currency-dollar')
                ->columns(2),
                Forms\Components\TextInput::make('emergency_phone')
                ->label('Emergency Phone')
                ->translateLabel()
                ->tel()
                ->helperText(__('the emergency phone number that will be displayed in the app'))
                ->required(),
            ]);
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Settings');
    }
    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable{
        return __('Manage General');
    }

    public static function getNavigationLabel(): string
    {
        return __('Manage General');
    }
}
