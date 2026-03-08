<?php

namespace App\Filament\Pages\FinancialReports\Widgets;

use App\Services\FinancialReportService;
use Carbon\Carbon;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class CommissionVsDriverChart extends ApexChartWidget
{
    protected static ?int $sort = 4;

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
        return __('financial.chart_commission_vs_driver');
    }

    protected function getOptions(): array
    {
        $data = app(FinancialReportService::class)->getCommissionVsDriverEarnings($this->resolveRange());

        return [
            'chart' => [
                'type'    => 'bar',
                'height'  => 300,
                'stacked' => true,
                'toolbar' => ['show' => true],
            ],
            'series' => [
                ['name' => __('financial.commission'),      'data' => $data['commission']],
                ['name' => __('financial.driver_earnings'), 'data' => $data['driver_earnings']],
            ],
            'xaxis'       => ['categories' => $data['labels']],
            'colors'      => ['#3b82f6', '#10b981'],
            'legend'      => ['position' => 'top'],
            'dataLabels'  => ['enabled' => false],
            'plotOptions' => ['bar' => ['borderRadius' => 4]],
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
