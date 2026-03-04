<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Support\DashboardDateFilter;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdvancedDashboardStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static int $cacheTtlSeconds = 300;

    protected $listeners = ['dashboard-filter-changed' => 'onFilterChanged'];

    /**
     * Disable auto-polling; dashboard uses cache.
     */
    protected static ?string $pollingInterval = null;

    public int $filterVersion = 0;

    public function onFilterChanged(): void
    {
        $this->cachedStats = null;
        $this->filterVersion++;
    }

    private function rememberStats(string $cacheKey): array
    {
        try {
            return Cache::store('redis')->remember(
                $cacheKey,
                self::$cacheTtlSeconds,
                fn () => $this->computeStatsData()
            );
        } catch (\Throwable) {
            return Cache::remember(
                $cacheKey,
                self::$cacheTtlSeconds,
                fn () => $this->computeStatsData()
            );
        }
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    protected function dateScope(Builder $query): Builder
    {
        $range = DashboardDateFilter::getDateRange();

        if ($range === null) {
            return $query;
        }

        return $query->whereBetween('created_at', $range);
    }

    protected function getStats(): array
    {
        $cacheKey = 'dashboard:advanced_stats:' . DashboardDateFilter::getCacheKeySuffix();

        $data = $this->rememberStats($cacheKey);

        return $this->buildStatsFromData($data);
    }

    /**
     * Single aggregated query: 6+ round-trips → 1.
     *
     * @return array<string, int>
     */
    private function computeStatsData(): array
    {
        $range = DashboardDateFilter::getDateRange();

        $activeStatuses = [
            \App\Enums\TripStatus::SEARCHING->value,
            \App\Enums\TripStatus::IN_ROUTE_TO_PICKUP->value,
            \App\Enums\TripStatus::PICKUP_ARRIVED->value,
            \App\Enums\TripStatus::IN_PROGRESS->value,
        ];
        $cancelledStatuses = [
            \App\Enums\TripStatus::CANCELLED_BY_DRIVER->value,
            \App\Enums\TripStatus::CANCELLED_BY_RIDER->value,
            \App\Enums\TripStatus::CANCELLED_BY_SYSTEM->value,
        ];

        $tripDateClause = $range ? ' AND created_at BETWEEN ? AND ?' : '';
        $completedDateClause = $range ? ' AND ended_at BETWEEN ? AND ?' : '';
        $driverDateClause = $range ? ' AND drivers.created_at BETWEEN ? AND ?' : '';
        $userDateClause = $range ? ' AND users.created_at BETWEEN ? AND ?' : '';

        $activeList = implode(',', $activeStatuses);
        $cancelledList = implode(',', $cancelledStatuses);
        $userModel = User::class;

        $sql = "
            SELECT
                (SELECT COUNT(*) FROM trips WHERE 1=1 {$tripDateClause}) AS total_trips,
                (SELECT COALESCE(SUM(CASE WHEN status IN ({$activeList}) THEN 1 ELSE 0 END), 0) FROM trips WHERE 1=1 {$tripDateClause}) AS active_trips,
                (SELECT COALESCE(SUM(CASE WHEN status = ? THEN 1 ELSE 0 END), 0) FROM trips WHERE 1=1 {$tripDateClause}) AS scheduled_trips,
                (SELECT COALESCE(SUM(CASE WHEN status IN ({$cancelledList}) THEN 1 ELSE 0 END), 0) FROM trips WHERE 1=1 {$tripDateClause}) AS cancelled_trips,
                (SELECT COUNT(*) FROM trips WHERE status = ? {$completedDateClause}) AS completed_trips,
                (SELECT COUNT(*) FROM drivers WHERE 1=1 {$driverDateClause}) AS total_drivers,
                (SELECT COUNT(*) FROM users
                    INNER JOIN model_has_roles ON users.id = model_has_roles.model_id AND model_has_roles.model_type = ?
                    INNER JOIN roles ON model_has_roles.role_id = roles.id AND roles.name = 'client'
                    WHERE 1=1 {$userDateClause}) AS total_clients,
                (SELECT COUNT(*) FROM users
                    INNER JOIN model_has_roles ON users.id = model_has_roles.model_id AND model_has_roles.model_type = ?
                    INNER JOIN roles ON model_has_roles.role_id = roles.id AND roles.name = 'client'
                    WHERE users.is_active = 1 {$userDateClause}) AS active_clients
        ";

        $bindingsOrdered = $range
            ? array_merge(
                [$range[0], $range[1]], // total_trips
                [$range[0], $range[1]], // active_trips
                [\App\Enums\TripStatus::SCHEDULED->value, $range[0], $range[1]], // scheduled_trips
                [$range[0], $range[1]], // cancelled_trips
                [\App\Enums\TripStatus::COMPLETED->value, $range[0], $range[1]], // completed_trips
                [$range[0], $range[1]], // total_drivers
                [$userModel, $range[0], $range[1]], // total_clients
                [$userModel, $range[0], $range[1]], // active_clients
            )
            : [
                \App\Enums\TripStatus::SCHEDULED->value,
                \App\Enums\TripStatus::COMPLETED->value,
                $userModel,
                $userModel,
            ];

        $row = DB::selectOne($sql, $bindingsOrdered);

        return [
            'total_trips' => (int) ($row->total_trips ?? 0),
            'active_trips' => (int) ($row->active_trips ?? 0),
            'scheduled_trips' => (int) ($row->scheduled_trips ?? 0),
            'cancelled_trips' => (int) ($row->cancelled_trips ?? 0),
            'completed_trips' => (int) ($row->completed_trips ?? 0),
            'total_drivers' => (int) ($row->total_drivers ?? 0),
            'total_clients' => (int) ($row->total_clients ?? 0),
            'active_clients' => (int) ($row->active_clients ?? 0),
        ];
    }

    /**
     * @param  array<string, int>  $data
     * @return array<Stat>
     */
    private function buildStatsFromData(array $data): array
    {
        return [
            Stat::make(__('stats.total_trips'), number_format($data['total_trips']))
                ->description(DashboardDateFilter::hasActiveFilter() ? __('stats.trips_in_period') : __('stats.all_time'))
                ->descriptionIcon('heroicon-m-map-pin')
                ->color('primary'),
            Stat::make(__('stats.active_trips'), number_format($data['active_trips']))
                ->description(__('stats.scheduled_trips_count', ['count' => number_format($data['scheduled_trips'])]))
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
            Stat::make(__('stats.total_drivers'), number_format($data['total_drivers']))
                ->description(DashboardDateFilter::hasActiveFilter() ? __('stats.drivers_in_period') : __('stats.all_time'))
                ->descriptionIcon('heroicon-m-user-circle')
                ->color('success'),
            Stat::make(__('stats.total_clients'), number_format($data['total_clients']))
                ->description(__('stats.active_clients_count', ['count' => number_format($data['active_clients'])]))
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),
            Stat::make(__('stats.completed_trips'), number_format($data['completed_trips']))
                ->description(DashboardDateFilter::hasActiveFilter() ? __('stats.completed_in_period') : __('stats.all_time'))
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            Stat::make(__('stats.cancelled_trips'), number_format($data['cancelled_trips']))
                ->description(DashboardDateFilter::hasActiveFilter() ? __('stats.cancelled_in_period') : __('stats.all_time'))
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),
        ];
    }
}
