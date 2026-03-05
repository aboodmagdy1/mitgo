<?php

namespace App\Services;

use App\Models\TripRequestLog;
use App\Support\DashboardDateFilter;
use Carbon\Carbon;

class TripRequestLogService
{
    /**
     * Get aggregate acceptance/rejection rates for all drivers.
     *
     * @return array{total: int, accepted: int, rejected: int, acceptance_rate: float|null, rejection_rate: float|null}
     */
    public function getAggregateRates(?Carbon $from = null, ?Carbon $to = null): array
    {
        $range = $from && $to ? [$from, $to] : DashboardDateFilter::getDateRange();

        $query = TripRequestLog::query();
        if ($range) {
            $query->whereBetween('sent_at', $range);
        }

        $total = $query->count();
        $accepted = (clone $query)->accepted()->count();
        $rejected = (clone $query)->rejected()->count();
        $responded = $accepted + $rejected;

        $acceptanceRate = $responded > 0 ? round(($accepted / $responded) * 100, 1) : null;
        $rejectionRate = $responded > 0 ? round(($rejected / $responded) * 100, 1) : null;

        return [
            'total' => $total,
            'accepted' => $accepted,
            'rejected' => $rejected,
            'responded' => $responded,
            'acceptance_rate' => $acceptanceRate,
            'rejection_rate' => $rejectionRate,
        ];
    }

    /**
     * Get acceptance/rejection rates for a specific driver.
     *
     * @return array{total: int, accepted: int, rejected: int, acceptance_rate: float|null, rejection_rate: float|null}
     */
    public function getDriverRates(int $driverId, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $range = $from && $to ? [$from, $to] : DashboardDateFilter::getDateRange();

        $query = TripRequestLog::where('driver_id', $driverId);
        if ($range) {
            $query->whereBetween('sent_at', $range);
        }

        $total = $query->count();
        $accepted = (clone $query)->accepted()->count();
        $rejected = (clone $query)->rejected()->count();
        $responded = $accepted + $rejected;

        $acceptanceRate = $responded > 0 ? round(($accepted / $responded) * 100, 1) : null;
        $rejectionRate = $responded > 0 ? round(($rejected / $responded) * 100, 1) : null;

        return [
            'total' => $total,
            'accepted' => $accepted,
            'rejected' => $rejected,
            'responded' => $responded,
            'acceptance_rate' => $acceptanceRate,
            'rejection_rate' => $rejectionRate,
        ];
    }
}
