<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\AdvancedDashboardStatsWidget;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Jeffgreco13\FilamentBreezy\BreezyCore;
use Filament\SpatieLaravelTranslatablePlugin;
use Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin;
use App\Http\Middleware\SetAdminLocale;
class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->spa()
            ->id('admin')
            ->path('admin')
            ->emailVerification()
            ->databaseNotifications()
            ->databaseNotificationsPolling('5s')
            ->login()
            ->colors([
                'primary' => Color::Green,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                SetAdminLocale::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins([
                BreezyCore::make()->myProfile(
                   slug: 'my-profile',hasAvatars: true,)->enableBrowserSessions(false),
                FilamentShieldPlugin::make(),
                SpatieLaravelTranslatablePlugin::make()->defaultLocales(['ar','en']),
                FilamentApexChartsPlugin::make()
            ])
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Main Core')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Drop Lists')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Finance')
                    ->icon('heroicon-o-banknotes')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Users')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Roles')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Access Management')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Settings')
                    ->collapsed(),
            ])->databaseNotifications()
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->brandName('Link')
            ->brandLogo( asset('images/logo.png'))
            ->darkModeBrandLogo( asset('images/logo.png'))
            ->brandLogoHeight('3rem')
            ->renderHook(
                'panels::body.end',
                fn () => view('filament.footer')
            )
            ->favicon("");
    }
}

