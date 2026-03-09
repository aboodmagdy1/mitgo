<?php

namespace App\Filament\Pages;

use App\Settings\GeneralSettings;
use Filament\Forms;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Pages\SettingsPage;

class ManageGeneral extends SettingsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $settings = GeneralSettings::class;
    protected static ?int $navigationSort = 1000;

    public function form(Form $form): Form
    {
        return $form
            ->live()
            ->schema([
                Section::make('إعدادات البحث عن السائقين')
                    ->icon('heroicon-o-magnifying-glass')
                    ->schema([
                        Placeholder::make('search_description')
                            ->label('')
                            ->content(fn (Get $get) => $this->buildSearchDescription($get))
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('search_wave_count')
                            ->label('عدد الموجات للبحث')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->live(),
                        Forms\Components\TextInput::make('search_wave_time')
                            ->label('وقت الانتظار بين كل موجة')
                            ->numeric()
                            ->minValue(1)
                            ->suffix('ثانية')
                            ->required()
                            ->live(),
                        Forms\Components\TextInput::make('initial_radius_km')
                            ->label('المسافة الأولى للبحث')
                            ->numeric()
                            ->step(0.1)
                            ->minValue(0.5)
                            ->suffix('كم هوائي')
                            ->helperText('المسافة المستقيمة كخط مستقيم')
                            ->required()
                            ->live(),
                        Forms\Components\TextInput::make('radius_increment_km')
                            ->label('زيادة المسافة عند عدم وجود سائقين')
                            ->numeric()
                            ->step(0.1)
                            ->minValue(0.1)
                            ->suffix('كم هوائي')
                            ->required()
                            ->live(),
                        Forms\Components\TextInput::make('drivers_per_wave')
                            ->label('عدد السائقين في كل موجة')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(20)
                            ->required()
                            ->live(),
                        Forms\Components\TextInput::make('driver_acceptance_time')
                            ->label('وقت قبول السائق للطلب')
                            ->numeric()
                            ->step(1)
                            ->minValue(0)
                            ->suffix('ثانية')
                            ->helperText('المدة التي يمتلكها السائق لقبول أو رفض الطلب')
                            ->required()
                            ->live(),
                    ])
                    ->columns(2),
                Section::make('المؤقتات')
                    ->schema([
                        Forms\Components\TextInput::make('free_waiting_time')
                            ->label('وقت الانتظار المجاني')
                            ->suffix('دقيقة')
                            ->helperText('الوقت المجاني الذي ينتظر السائق للعميل')
                            ->required(),
                    ])
                    ->icon('heroicon-o-clock')
                    ->columns(2),
                Section::make('العمولة')
                ->schema([
                    Forms\Components\TextInput::make('commission_rate')
                    ->label('نسبة العمولة')
                    ->required(),
                ])
                ->icon('heroicon-o-currency-dollar')
                ->columns(2),
                Forms\Components\TextInput::make('emergency_phone')
                ->label('رقم الطوارئ')
                ->translateLabel()
                ->tel()
                ->helperText('رقم هاتف الطوارئ الذي سيتم عرضه في التطبيق')
                ->required(),
            ]);
    }

    public static function getNavigationGroup(): ?string
    {
        return 'الإعدادات';
    }
    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable{
        return 'إدارة العام';
    }

    public static function getNavigationLabel(): string
    {
        return 'إدارة العام';
    }

    private function buildSearchDescription(Get $get): string
    {
        $waveCount = $get('search_wave_count') ?: 10;
        $waveTime = $get('search_wave_time') ?: 30;
        $initialRadius = $get('initial_radius_km') ?: 2.0;
        $radiusIncrement = $get('radius_increment_km') ?: 1.0;
        $driversPerWave = $get('drivers_per_wave') ?: 5;
        $acceptanceTime = $get('driver_acceptance_time') ?: 60;

        return sprintf(
            'عند طلب العميل لرحلة: التطبيق يبحث عن سائقين %s مرات، وينتظر %s ثانية بين كل مرة. يبدأ البحث ضمن %s كم هوائي (المسافة المستقيمة)، ولو مفيش سائقين يكبّر المسافة %s كم. كل مرة يرسل الطلب لـ %s سائقين، وكل سائق عنده %s ثانية يقرر يقبل أو يرفض.',
            $waveCount,
            $waveTime,
            $initialRadius,
            $radiusIncrement,
            $driversPerWave,
            $acceptanceTime
        );
    }
}
