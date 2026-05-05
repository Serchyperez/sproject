<?php

namespace App\Providers\Filament;

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

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('app')
            ->path('app')
            ->login()
            ->colors([
                'primary' => Color::Violet,
            ])
            ->brandName('SProjects')
            ->renderHook('panels::styles.before', fn () => '<link rel="stylesheet" href="/frappe-gantt.min.css">')
            ->renderHook('panels::head.end', fn () => '
<style>
/* ── Sidebar header (logo area) — black bg, white text ── */
.fi-sidebar-header {
    background-color: #000 !important;
    --tw-ring-color: transparent !important;
    box-shadow: none !important;
}
.fi-sidebar-header .fi-logo {
    color: #fff !important;
}
.fi-sidebar-header svg {
    color: #fff !important;
    fill: currentColor;
}

/* ── Topbar nav — black bg ── */
.fi-topbar > nav {
    background-color: #000 !important;
    --tw-ring-color: transparent !important;
    box-shadow: none !important;
}
/* Hamburger / close sidebar icon buttons */
.fi-topbar nav .fi-topbar-open-sidebar-btn svg,
.fi-topbar nav .fi-topbar-close-sidebar-btn svg {
    color: #fff !important;
}

/* ── User avatar — invert to white bg, black text ── */
.fi-user-avatar {
    filter: invert(1) !important;
}
</style>')
            ->discoverResources(in: app_path('Filament/App/Resources'), for: 'App\\Filament\\App\\Resources')
            ->discoverPages(in: app_path('Filament/App/Pages'), for: 'App\\Filament\\App\\Pages')
            ->navigationGroups([
                NavigationGroup::make('Administración')
                    ->icon('heroicon-o-shield-check')
                    ->collapsible(false),
            ])
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/App/Widgets'), for: 'App\\Filament\\App\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
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
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
