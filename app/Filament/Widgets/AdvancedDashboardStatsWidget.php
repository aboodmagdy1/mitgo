<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Driver;
use App\Models\Trip;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdvancedDashboardStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    
    protected function getStats(): array
    {
        return [
            $this->getTotalTripsStats(),
            $this->getActiveTripsStats(),
            $this->getTotalDriversStats(),
            $this->getActiveDriversStats(),
            $this->getTotalClientsStats(),
            $this->getCompletedTripsStats(),
            $this->getTotalRevenueStats(),
            $this->getCancelledTripsStats(),
        ];
    }

    private function getTotalTripsStats(): Stat
    {
        $total = Trip::count();
        $thisMonth = Trip::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();
        
        return Stat::make(__('stats.total_trips'), number_format($total))
            ->description(__('stats.trips_this_month', ['count' => number_format($thisMonth)]))
            ->descriptionIcon('heroicon-m-map-pin')
            ->color('primary');
    }

    private function getActiveTripsStats(): Stat
    {
        $activeTrips = Trip::whereIn('status', [
            \App\Enums\TripStatus::SEARCHING->value,
            \App\Enums\TripStatus::IN_ROUTE_TO_PICKUP->value,
            \App\Enums\TripStatus::PICKUP_ARRIVED->value,
            \App\Enums\TripStatus::IN_PROGRESS->value
        ])->count();
        
        $scheduledTrips = Trip::where('status', \App\Enums\TripStatus::SCHEDULED->value)->count();
        
        return Stat::make(__('stats.active_trips'), number_format($activeTrips))
            ->description(__('stats.scheduled_trips_count', ['count' => number_format($scheduledTrips)]))
            ->descriptionIcon('heroicon-m-clock')
            ->color('warning');
    }

    private function getTotalDriversStats(): Stat
    {
        $total = Driver::count();
        $newThisMonth = Driver::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();
        
        return Stat::make(__('stats.total_drivers'), number_format($total))
            ->description(__('stats.new_drivers_this_month', ['count' => number_format($newThisMonth)]))
            ->descriptionIcon('heroicon-m-user-circle')
            ->color('success');
    }

    private function getActiveDriversStats(): Stat
    {
        $onlineDrivers = Driver::where('status', 1)->count();
        $withVehicles = Driver::whereHas('vehicle')->count();
        
        return Stat::make(__('stats.online_drivers'), number_format($onlineDrivers))
            ->description(__('stats.drivers_with_vehicles', ['count' => number_format($withVehicles)]))
            ->descriptionIcon('heroicon-m-truck')
            ->color('info');
    }

    private function getTotalClientsStats(): Stat
    {
        $totalClients = User::whereHas('roles', function ($query) {
            $query->where('name', 'client');
        })->count();
        
        $activeClients = User::whereHas('roles', function ($query) {
            $query->where('name', 'client');
        })->where('is_active', true)->count();
        
        return Stat::make(__('stats.total_clients'), number_format($totalClients))
            ->description(__('stats.active_clients_count', ['count' => number_format($activeClients)]))
            ->descriptionIcon('heroicon-m-users')
            ->color('success');
    }

    private function getCompletedTripsStats(): Stat
    {
        $completedTrips = Trip::where('status', \App\Enums\TripStatus::PAID->value)->count();
        $thisMonth = Trip::where('status', \App\Enums\TripStatus::PAID->value)
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();
        
        return Stat::make(__('stats.completed_trips'), number_format($completedTrips))
            ->description(__('stats.completed_this_month', ['count' => number_format($thisMonth)]))
            ->descriptionIcon('heroicon-m-check-circle')
            ->color('success');
    }

    private function getTotalRevenueStats(): Stat
    {
        $totalRevenue = Trip::where('status', \App\Enums\TripStatus::PAID->value)
            ->sum('actual_fare') ?? 0;
        
        $thisMonthRevenue = Trip::where('status', \App\Enums\TripStatus::PAID->value)
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('actual_fare') ?? 0;
        
        return Stat::make(__('stats.total_revenue'), number_format($totalRevenue, 2) . ' ' . __('SAR'))
            ->description(__('stats.revenue_this_month', ['amount' => number_format($thisMonthRevenue, 2)]))
            ->descriptionIcon('heroicon-m-banknotes')
            ->color('success');
    }

    private function getCancelledTripsStats(): Stat
    {
        $cancelledTrips = Trip::whereIn('status', [
            \App\Enums\TripStatus::CANCELLED_BY_DRIVER->value,
            \App\Enums\TripStatus::CANCELLED_BY_RIDER->value,
            \App\Enums\TripStatus::CANCELLED_BY_SYSTEM->value
        ])->count();
        
        $thisMonth = Trip::whereIn('status', [
            \App\Enums\TripStatus::CANCELLED_BY_DRIVER->value,
            \App\Enums\TripStatus::CANCELLED_BY_RIDER->value,
            \App\Enums\TripStatus::CANCELLED_BY_SYSTEM->value
        ])->whereMonth('created_at', Carbon::now()->month)
          ->whereYear('created_at', Carbon::now()->year)
          ->count();
        
        return Stat::make(__('stats.cancelled_trips'), number_format($cancelledTrips))
            ->description(__('stats.cancelled_this_month', ['count' => number_format($thisMonth)]))
            ->descriptionIcon('heroicon-m-x-circle')
            ->color('danger');
    }
}
