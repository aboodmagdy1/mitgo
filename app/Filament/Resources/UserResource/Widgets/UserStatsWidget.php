<?php

namespace App\Filament\Resources\UserResource\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserStatsWidget extends BaseWidget
{
    
    protected function getStats(): array
    {
        return [
            Stat::make(__('Total Admins'), User::whereHas('roles', function ($query) {
                $query->whereNotIn('name',['client','provider']);
            })->count())
                ->description(__('Dashboard Users'))
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),
            Stat::make(__('Total Roles'), Role::count())
                ->description(__('Dashboard Roles'))
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('primary'),
            Stat::make(__('Total Permissions'), Permission::count())
                ->description(__('Dashboard Permissions'))
                ->descriptionIcon('heroicon-m-lock-closed')
                ->color('danger'),
        ];  
    }
}
