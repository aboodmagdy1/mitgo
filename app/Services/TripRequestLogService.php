<?php

namespace App\Services;

use App\Enums\TripRequestOutcome;
use App\Enums\TripStatus;
use App\Models\Trip;
use App\Models\TripRequestLog;
use App\Models\Zone;
use App\Support\DashboardDateFilter;
use Carbon\Carbon;
use function App\Helpers\point_in_polygon_from_zone;

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

    /**
     * Get zone statistics: trips count and breakdown (complete, acceptance, rejection, cancellation).
     * When zone_id count is 0, falls back to counting trips by pickup location (point-in-polygon)
     * for trips with null zone_id (e.g. legacy data).
     *
     * @return array{
     *     total_trips: int,
     *     completed: int,
     *     completed_percentage: float|null,
     *     accepted: int,
     *     acceptance_percentage: float|null,
     *     rejected: int,
     *     rejection_percentage: float|null,
     *     cancelled: int,
     *     cancellation_percentage: float|null,
     *     total_requests: int
     * }
     */
    public function getZoneStats(int $zoneId, ?Carbon $from = null, ?Carbon $to = null, ?Zone $zone = null): array
    {
        $tripsQuery = Trip::where('zone_id', $zoneId);
        $logsQuery = TripRequestLog::whereHas('trip', fn ($q) => $q->where('zone_id', $zoneId));

        if ($from) {
            $tripsQuery->where('created_at', '>=', $from);
            $logsQuery->where('sent_at', '>=', $from);
        }
        if ($to) {
            $tripsQuery->where('created_at', '<=', $to);
            $logsQuery->where('sent_at', '<=', $to);
        }

        $totalTrips = $tripsQuery->count();

        // Fallback: when zone_id count is 0, count trips by pickup location (point-in-polygon)
        // for trips with null zone_id (legacy data or when zone was not set at creation)
        if ($totalTrips === 0 && $zone) {
            $fallback = $this->countTripsByPickupInZone($zone, $from, $to);
            $totalTrips = $fallback['total'];
            $completed = $fallback['completed'];
            $cancelled = $fallback['cancelled'];
        } else {
            $completed = (clone $tripsQuery)->whereIn('status', [
                TripStatus::COMPLETED,
                TripStatus::PAID,
                TripStatus::COMPLETED_PENDING_PAYMENT,
            ])->count();
            $cancelled = (clone $tripsQuery)->whereIn('status', [
                TripStatus::CANCELLED_BY_DRIVER,
                TripStatus::CANCELLED_BY_RIDER,
                TripStatus::CANCELLED_BY_SYSTEM,
            ])->count();
        }

        $totalRequests = $logsQuery->count();
        $accepted = (clone $logsQuery)->where('outcome', TripRequestOutcome::ACCEPTED)->count();
        $rejected = (clone $logsQuery)->where('outcome', TripRequestOutcome::REJECTED)->count();

        $completedPct = $totalTrips > 0 ? round(($completed / $totalTrips) * 100, 1) : null;
        $cancellationPct = $totalTrips > 0 ? round(($cancelled / $totalTrips) * 100, 1) : null;
        $responded = $accepted + $rejected;
        $acceptancePct = $responded > 0 ? round(($accepted / $responded) * 100, 1) : null;
        $rejectionPct = $responded > 0 ? round(($rejected / $responded) * 100, 1) : null;

        return [
            'total_trips' => $totalTrips,
            'completed' => $completed,
            'completed_percentage' => $completedPct,
            'accepted' => $accepted,
            'acceptance_percentage' => $acceptancePct,
            'rejected' => $rejected,
            'rejection_percentage' => $rejectionPct,
            'cancelled' => $cancelled,
            'cancellation_percentage' => $cancellationPct,
            'total_requests' => $totalRequests,
        ];
    }

    /**
     * Count trips by pickup location (point-in-polygon).
     * Used as fallback when zone_id count is 0 (legacy data or incorrect zone_id).
     */
    private function countTripsByPickupInZone(Zone $zone, ?Carbon $from, ?Carbon $to): array
    {
        $query = Trip::whereNotNull('pickup_lat')
            ->whereNotNull('pickup_long');

        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to);
        }

        $total = 0;
        $completed = 0;
        $cancelled = 0;
        $completedValues = [
            TripStatus::COMPLETED->value,
            TripStatus::PAID->value,
            TripStatus::COMPLETED_PENDING_PAYMENT->value,
        ];
        $cancelledValues = [
            TripStatus::CANCELLED_BY_DRIVER->value,
            TripStatus::CANCELLED_BY_RIDER->value,
            TripStatus::CANCELLED_BY_SYSTEM->value,
        ];

        $query->select(['id', 'status', 'pickup_lat', 'pickup_long'])
            ->chunk(500, function ($trips) use ($zone, &$total, &$completed, &$cancelled, $completedValues, $cancelledValues) {
                foreach ($trips as $trip) {
                    $lat = (float) $trip->pickup_lat;
                    $lng = (float) $trip->pickup_long;
                    if (point_in_polygon_from_zone($zone, $lat, $lng)) {
                        $total++;
                        $statusValue = $trip->status instanceof TripStatus ? $trip->status->value : (int) $trip->status;
                        if (in_array($statusValue, $completedValues, true)) {
                            $completed++;
                        } elseif (in_array($statusValue, $cancelledValues, true)) {
                            $cancelled++;
                        }
                    }
                }
            });

        return [
            'total' => $total,
            'completed' => $completed,
            'cancelled' => $cancelled,
        ];
    }
}
