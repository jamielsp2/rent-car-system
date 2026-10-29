<?php

namespace App\Providers\Filament;

use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\EnsureLicensed;
use App\Support\SiteContent;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Panel for allies (/aliados): they see only their own cars and their reports.
 */
class AllyPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('aliados')
            ->path('aliados')
            ->login()
            ->brandName(fn () => SiteContent::name())
            ->brandLogo(fn () => SiteContent::logo())
            ->brandLogoHeight('2.25rem')
            ->favicon(fn () => SiteContent::favicon())
            ->colors([
                'primary' => Color::hex('#206bc4'),
            ])
            ->font('Inter')
            ->darkMode(false)
            ->maxContentWidth(Width::Full)
            ->discoverPages(in: app_path('Filament/Ally/Pages'), for: 'App\Filament\Ally\Pages')
            ->discoverWidgets(in: app_path('Filament/Ally/Widgets'), for: 'App\Filament\Ally\Widgets')
            ->middleware([
                EnsureInstalled::class,
                EnsureLicensed::class,
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
