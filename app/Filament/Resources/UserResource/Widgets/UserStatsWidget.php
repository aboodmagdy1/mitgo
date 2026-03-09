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
            Stat::make('إجمالي المدراء', User::whereHas('roles', function ($query) {
                $query->whereNotIn('name',['client','provider']);
            })->count())
                ->description('مستخدمي لوحة التحكم')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),
            Stat::make('إجمالي الأدوار', Role::count())
                ->description('أدوار لوحة التحكم')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('primary'),
            Stat::make('إجمالي الصلاحيات', Permission::count())
                ->description('صلاحيات لوحة التحكم')
                ->descriptionIcon('heroicon-m-lock-closed')
                ->color('danger'),
        ];  
    }
}
