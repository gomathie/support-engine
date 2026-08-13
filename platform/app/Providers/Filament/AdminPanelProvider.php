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
            // The only panel. Marking it default is what lets
            // Resource::getUrl() resolve outside a panel request — used by the
            // tests, and by any link generated from the employee side.
            ->default()
            ->id('admin')
            ->path('admin')

            // No ->login(). Everyone signs in through the one Laravel/Inertia
            // login page; Filament's Authenticate middleware redirects there and
            // User::canAccessPanel() decides who is let through afterwards.

            ->colors([
                // The academy palette, so the admin panel reads as the same
                // product as the employee portal.
                'primary' => Color::hex('#1463ff'),
                'success' => Color::hex('#19a86b'),
                'warning' => Color::hex('#d97706'),
                'danger' => Color::hex('#dc2626'),
                'gray' => Color::Slate,
            ])
            ->brandName('Support Academy')
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
