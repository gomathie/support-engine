<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')

            // No ->login(). Everyone signs in through the one Laravel/Inertia
            // login page; Filament's Authenticate middleware redirects there and
            // User::canAccessPanel() decides who is let through afterwards.

            ->colors([
                // #0284c7 — the prototype's --primary, so the admin panel reads as
                // the same product as the employee portal.
                'primary' => Color::hex('#0284c7'),
                'warning' => Color::hex('#eab308'),
                'success' => Color::hex('#22c55e'),
                'danger' => Color::hex('#ef4444'),
            ])
            ->brandName('PILOT Training Hub')
            ->favicon(asset('favicon.ico'))

            ->navigationGroups([
                NavigationGroup::make('Content'),
                NavigationGroup::make('People'),
                NavigationGroup::make('Assignment'),
                NavigationGroup::make('Support panel'),
                NavigationGroup::make('Reporting'),
            ])

            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')

            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
