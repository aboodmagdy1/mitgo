<?php

namespace App\Filament\Pages\FinancialReports\Widgets;

use App\Exports\DailyFinancialSummaryExport;
use App\Services\FinancialReportService;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\LaravelPdf\Facades\Pdf;

class DailySummaryTableWidget extends Widget
{
    protected static ?int $sort = 10;

    protected int|string|array $columnSpan = 'full';

    protected static bool $isLazy = true;

    protected static string $view = 'filament.pages.financial-reports.widgets.daily-summary-table';

    public string $dateFrom = '';

    public string $dateTo = '';

    public int $perPage = 15;

    public int $currentPage = 1;

    public string $sortBy = 'date';

    public string $sortDir = 'desc';

    protected $listeners = ['financial-filter-changed' => 'onFilterChanged'];

    public function onFilterChanged(string $dateFrom, string $dateTo): void
    {
        $this->dateFrom    = $dateFrom;
        $this->dateTo      = $dateTo;
        $this->currentPage = 1;
    }

    public function sortBy(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDir = 'desc';
        }
        $this->currentPage = 1;
    }

    public function nextPage(): void
    {
        $total = $this->getTotalRows();
        $max   = (int) ceil($total / $this->perPage);
        if ($this->currentPage < $max) {
            $this->currentPage++;
        }
    }

    public function prevPage(): void
    {
        if ($this->currentPage > 1) {
            $this->currentPage--;
        }
    }

    public function exportExcel(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        return Excel::download(
            new DailyFinancialSummaryExport($this->resolveRange()),
            'financial-report-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    public function exportPdf(): \Spatie\LaravelPdf\PdfBuilder
    {
        $range = $this->resolveRange();
        $rows  = app(FinancialReportService::class)->getDailySummary($range);

        return Pdf::view('exports.daily-financial-summary-pdf', [
            'rows'     => $rows,
            'dateFrom' => $this->dateFrom,
            'dateTo'   => $this->dateTo,
        ])
            ->format('a4')
            ->landscape()
            ->name('financial-report-' . now()->format('Y-m-d') . '.pdf');
    }

    protected function getViewData(): array
    {
        $allRows = $this->getAllRows();
        $sorted  = $this->sortRows($allRows);
        $total   = $sorted->count();
        $pages   = max(1, (int) ceil($total / $this->perPage));
        $page    = min($this->currentPage, $pages);
        $rows    = $sorted->forPage($page, $this->perPage);

        return [
            'rows'        => $rows,
            'totalRows'   => $total,
            'totalPages'  => $pages,
            'currentPage' => $page,
            'perPage'     => $this->perPage,
            'sortBy'      => $this->sortBy,
            'sortDir'     => $this->sortDir,
            'totals'      => $this->computeTotals($allRows),
        ];
    }

    private function getAllRows(): Collection
    {
        return app(FinancialReportService::class)->getDailySummary($this->resolveRange());
    }

    private function getTotalRows(): int
    {
        return $this->getAllRows()->count();
    }

    private function sortRows(Collection $rows): Collection
    {
        return $this->sortDir === 'asc'
            ? $rows->sortBy($this->sortBy)->values()
            : $rows->sortByDesc($this->sortBy)->values();
    }

    private function computeTotals(Collection $rows): object
    {
        return (object) [
            'total_trips'        => $rows->sum('total_trips'),
            'total_revenue'      => $rows->sum(fn ($r) => (float) $r->total_revenue),
            'commission'         => $rows->sum(fn ($r) => (float) $r->commission),
            'driver_earnings'    => $rows->sum(fn ($r) => (float) $r->driver_earnings),
            'coupon_discounts'   => $rows->sum(fn ($r) => (float) $r->coupon_discounts),
            'cancellation_fees'  => $rows->sum(fn ($r) => (float) $r->cancellation_fees),
            'waiting_fees'       => $rows->sum(fn ($r) => (float) $r->waiting_fees),
            'net_revenue'        => $rows->sum(fn ($r) => (float) $r->net_revenue),
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
