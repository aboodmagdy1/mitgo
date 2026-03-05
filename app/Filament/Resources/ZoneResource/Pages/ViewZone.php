<?php

namespace App\Filament\Resources\ZoneResource\Pages;

use App\Filament\Resources\ZoneResource;
use App\Services\TripRequestLogService;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewZone extends ViewRecord
{
    protected static string $resource = ZoneResource::class;

    public ?array $zoneStats = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public function mount(int | string $record): void
    {
        parent::mount($record);
        $this->loadZoneStats();
    }

    public function loadZoneStats(): void
    {
        if ($this->record) {
            $from = $this->dateFrom ? Carbon::parse($this->dateFrom)->startOfDay() : null;
            $to = $this->dateTo ? Carbon::parse($this->dateTo)->endOfDay() : null;
            $this->zoneStats = app(TripRequestLogService::class)->getZoneStats(
                $this->record->id,
                $from,
                $to,
                $this->record
            );
        }
    }

    public function applyDateFilter(): void
    {
        $this->loadZoneStats();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('filter_dates')
                ->label(__('Filter by Date'))
                ->icon('heroicon-o-calendar')
                ->form([
                    DatePicker::make('dateFrom')
                        ->label(__('From'))
                        ->native(false),
                    DatePicker::make('dateTo')
                        ->label(__('To'))
                        ->native(false),
                ])
                ->fillForm(fn (): array => [
                    'dateFrom' => $this->dateFrom,
                    'dateTo' => $this->dateTo,
                ])
                ->action(function (array $data): void {
                    $this->dateFrom = $data['dateFrom'] ?? null;
                    $this->dateTo = $data['dateTo'] ?? null;
                    $this->loadZoneStats();
                }),
            Actions\Action::make('clear_date_filter')
                ->label(__('All Time'))
                ->icon('heroicon-o-x-circle')
                ->color('gray')
                ->visible(fn (): bool => $this->dateFrom !== null || $this->dateTo !== null)
                ->action(function (): void {
                    $this->dateFrom = null;
                    $this->dateTo = null;
                    $this->loadZoneStats();
                }),
            Actions\EditAction::make()->label(__('Edit')),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->columns(1)
            ->schema([
                Section::make(__('Details'))
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        TextEntry::make('name')
                            ->label(__('Name'))
                            ->icon('heroicon-o-tag'),
                        TextEntry::make('status')
                            ->label(__('Active'))
                            ->badge()
                            ->formatStateUsing(fn (?bool $state): string => $state ? __('Active') : __('Inactive'))
                            ->color(fn (?bool $state): string => $state ? 'success' : 'danger'),
                    ])->columns(2),

                Section::make(__('Zone Polygon'))
                    ->icon('heroicon-o-map')
                    ->schema([
                        ViewEntry::make('zone_map')
                            ->label('')
                            ->view('filament.infolists.components.zone-map')
                            ->columnSpanFull(),
                    ]),

                Section::make(__('Statistics'))
                    ->icon('heroicon-o-chart-bar')
                    ->description($this->getStatisticsDescription())
                    ->schema([
                        ViewEntry::make('zone_statistics')
                            ->label('')
                            ->view('filament.infolists.components.zone-statistics')
                            ->viewData(['zoneStats' => $this->zoneStats])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected function getStatisticsDescription(): ?string
    {
        if ($this->dateFrom && $this->dateTo) {
            return __('Showing trips from :from to :to', [
                'from' => Carbon::parse($this->dateFrom)->format('Y-m-d'),
                'to' => Carbon::parse($this->dateTo)->format('Y-m-d'),
            ]);
        }

        return __('All time');
    }
}
