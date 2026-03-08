<?php

namespace App\Filament\Pages\FinancialReports\Widgets;

use App\Services\FinancialReportService;
use Carbon\Carbon;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class TripsVsRevenueChart extends ApexChartWidget
{
    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 2;

    protected static bool $isLazy = true;

    public string $dateFrom = '';

    public string $dateTo = '';

    protected $listeners = ['financial-filter-changed' => 'onFilterChanged'];

    public function onFilterChanged(string $dateFrom, string $dateTo): void
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo   = $dateTo;
        $this->updateOptions();
    }

    protected function getHeading(): ?string
    {
        return __('financial.chart_trips_vs_revenue');
    }

    protected function getOptions(): array
    {
        $data = app(FinancialReportService::class)->getTripsVsRevenue($this->resolveRange());

        return [
            'chart' => [
                'type'    => 'line',
                'height'  => 300,
                'toolbar' => ['show' => true],
            ],
            'series' => [
                ['name' => __('financial.trips'),   'type' => 'column', 'data' => $data['trips']],
                ['name' => __('financial.revenue'),  'type' => 'line',   'data' => $data['revenue']],
            ],
            'xaxis'       => ['categories' => $data['labels'], 'labels' => ['rotate' => -45]],
            'colors'      => ['#3b82f6', '#10b981'],
            'legend'      => ['position' => 'top'],
            'dataLabels'  => ['enabled' => false],
            'stroke'      => ['width' => [0, 2]],
            'plotOptions' => ['bar' => ['columnWidth' => '60%', 'borderRadius' => 3]],
        ];
    }

    /** @return array{Carbon,Carbon}|null */
    private function resolveRange(): ?array
    {
        if (empty($this->dateFrom) || empty($this->dateTo)) {
            return null;
        }

        return [
            Carbon::parse($this->dateFrom)->startOfDay(),
            Carbon::parse($this->dateTo)->endOfDay(),
        ];
    }
}
