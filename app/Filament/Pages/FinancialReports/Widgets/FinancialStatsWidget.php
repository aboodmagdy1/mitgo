<?php

namespace App\Filament\Pages\FinancialReports\Widgets;

use App\Services\FinancialReportService;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinancialStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected static bool $isLazy = true;

    protected static ?string $pollingInterval = null;

    public string $dateFrom = '';

    public string $dateTo = '';

    protected $listeners = ['financial-filter-changed' => 'onFilterChanged'];

    public function onFilterChanged(string $dateFrom, string $dateTo): void
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo   = $dateTo;
    }

    protected function getStats(): array
    {
        $range = $this->resolveRange();
        $data  = app(FinancialReportService::class)->getStatCards($range);

        return [
            Stat::make(__('financial.total_revenue'), $this->formatMoney($data['total_revenue']))
                ->description(__('financial.completed_payments'))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->icon('heroicon-m-currency-dollar'),

            Stat::make(__('financial.company_profit'), $this->formatMoney($data['company_profit']))
                ->description(__('financial.commission_earned'))
                ->descriptionIcon('heroicon-m-building-office')
                ->color('primary')
                ->icon('heroicon-m-building-office'),

            Stat::make(__('financial.driver_earnings'), $this->formatMoney($data['driver_earnings']))
                ->description(__('financial.net_driver_payout'))
                ->descriptionIcon('heroicon-m-truck')
                ->color('info')
                ->icon('heroicon-m-truck'),

            Stat::make(__('financial.avg_trip_fare'), $this->formatMoney($data['avg_trip_fare']))
                ->description(__('financial.per_completed_trip'))
                ->descriptionIcon('heroicon-m-calculator')
                ->color('warning')
                ->icon('heroicon-m-calculator'),

            Stat::make(__('financial.total_taxes'), $this->formatMoney($data['total_taxes']))
                ->description(__('financial.tax_rate_label', ['rate' => '15%']))
                ->descriptionIcon('heroicon-m-receipt-percent')
                ->color('gray')
                ->icon('heroicon-m-receipt-percent'),

            Stat::make(__('financial.coupon_discounts'), $this->formatMoney($data['coupon_discounts']))
                ->description(__('financial.total_discounts_given'))
                ->descriptionIcon('heroicon-m-tag')
                ->color('warning')
                ->icon('heroicon-m-tag'),

            Stat::make(__('financial.cancellation_fees'), $this->formatMoney($data['cancellation_fees']))
                ->description(__('financial.fees_collected'))
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger')
                ->icon('heroicon-m-x-circle'),

            Stat::make(__('financial.waiting_fees'), $this->formatMoney($data['waiting_fees']))
                ->description(__('financial.fees_collected'))
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->icon('heroicon-m-clock'),

            Stat::make(__('financial.pending_payments'), number_format($data['pending_count']))
                ->description(__('financial.pending_amount_label', ['amount' => $this->formatMoney($data['pending_amount'])]))
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning')
                ->icon('heroicon-m-exclamation-triangle'),

            Stat::make(__('financial.refunded_amount'), $this->formatMoney($data['refunded_amount']))
                ->description(__('financial.total_refunds'))
                ->descriptionIcon('heroicon-m-arrow-uturn-left')
                ->color('danger')
                ->icon('heroicon-m-arrow-uturn-left'),

            Stat::make(__('financial.wallet_balance'), $this->formatMoney($data['wallet_balance']))
                ->description(__('financial.all_user_wallets'))
                ->descriptionIcon('heroicon-m-wallet')
                ->color('info')
                ->icon('heroicon-m-wallet'),
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

    private function formatMoney(float $value): string
    {
        return 'SAR ' . number_format($value, 2);
    }
}
