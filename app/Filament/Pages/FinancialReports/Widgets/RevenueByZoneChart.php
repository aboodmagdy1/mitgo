<?php

namespace App\Filament\Pages\FinancialReports\Widgets;

use App\Services\FinancialReportService;
use Carbon\Carbon;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class RevenueByZoneChart extends ApexChartWidget
{
    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 1;

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
        return 'الإيرادات حسب المنطقة';
    }

    protected function getOptions(): array
    {
        $data = app(FinancialReportService::class)->getRevenueByZone($this->resolveRange());

        return [
            'chart' => [
                'type'    => 'bar',
                'height'  => 300,
                'toolbar' => ['show' => false],
            ],
            'series' => [
                ['name' => 'الإيرادات', 'data' => $data['data']],
            ],
            'xaxis'       => ['categories' => $data['labels']],
            'colors'      => ['#8b5cf6'],
            'dataLabels'  => ['enabled' => false],
            'plotOptions' => ['bar' => ['borderRadius' => 4, 'columnWidth' => '60%']],
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
