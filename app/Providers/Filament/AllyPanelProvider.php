<?php

namespace App\Providers\Filament;

use App\Filament\Ally\Auth\Login;
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
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Panel for allies (/aliados): they see only their own cars and their reports.
 * Same look as the admin panel (theme, sign-in showcase), with its own wording.
 */
class AllyPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('aliados')
            ->path('aliados')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login(Login::class)
            ->brandName(fn () => SiteContent::name())
            ->brandLogo(fn () => SiteContent::logo())
            ->brandLogoHeight('2.25rem')
            ->favicon(fn () => SiteContent::favicon())
            ->colors([
                'primary' => Color::hex('#206bc4'),
            ])
            ->font('Inter')
            ->darkMode(false)
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth(Width::Full)
            ->sidebarWidth('15rem')
            ->discoverPages(in: app_path('Filament/Ally/Pages'), for: 'App\Filament\Ally\Pages')
            ->discoverWidgets(in: app_path('Filament/Ally/Widgets'), for: 'App\Filament\Ally\Widgets')
            ->renderHook(PanelsRenderHook::SIMPLE_LAYOUT_START, fn () => view('filament.auth.login-showcase', [
                'panelName' => 'Panel de aliados',
                'headline' => 'Tus vehículos rentados,',
                'highlight' => 'siempre a la vista.',
                'features' => [
                    ['icon' => 'heroicon-o-truck', 'title' => 'Tus vehículos', 'text' => 'Todos los autos que prestas, en un solo lugar.'],
                    ['icon' => 'heroicon-o-presentation-chart-line', 'title' => 'Rentabilidad clara', 'text' => 'Ingresos, gastos y utilidad de cada vehículo.'],
                    ['icon' => 'heroicon-o-calendar-days', 'title' => 'Renta al día', 'text' => 'Si está rentado, reservado o libre, y quién lo rentó.'],
                ],
            ]), scopes: Login::class)
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
