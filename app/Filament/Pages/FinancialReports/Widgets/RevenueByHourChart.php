<?php

namespace App\Filament\Pages\FinancialReports\Widgets;

use App\Services\FinancialReportService;
use Carbon\Carbon;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class RevenueByHourChart extends ApexChartWidget
{
    protected static ?int $sort = 9;

    protected int|string|array $columnSpan = 'full';

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
        return __('financial.chart_revenue_by_hour');
    }

    protected function getOptions(): array
    {
        $data = app(FinancialReportService::class)->getRevenueByHour($this->resolveRange());

        return [
            'chart' => [
                'type'    => 'bar',
                'height'  => 280,
                'toolbar' => ['show' => false],
            ],
            'series' => [
                ['name' => __('financial.revenue'), 'data' => $data['data']],
            ],
            'xaxis'       => ['categories' => $data['labels']],
            'colors'      => ['#06b6d4'],
            'dataLabels'  => ['enabled' => false],
            'plotOptions' => ['bar' => ['borderRadius' => 3, 'columnWidth' => '75%']],
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
