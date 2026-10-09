<?php

use App\Http\Controllers\BookingCalendarController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\LicenseController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

Route::controller(InstallController::class)->group(function (): void {
    Route::get('/instalar', 'show')->name('install.show');
    Route::post('/instalar', 'store')->middleware('throttle:10,1')->name('install.store');
});

Route::controller(LicenseController::class)->group(function (): void {
    Route::get('/licencia', 'show')->name('license.show');
    Route::post('/licencia', 'store')->middleware('throttle:10,1')->name('license.store');
    Route::post('/licencia/comprobar', 'check')->middleware('throttle:10,1')->name('license.check');
});

// Files in the client's R2 bucket. No session or cookies: every photo of the site goes through here.
Route::get('/media-r2', [MediaController::class, 'show'])->name('media.r2')->withoutMiddleware([
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class,
]);

Route::get('/', [SiteController::class, 'home'])->name('site.home');
Route::get('/cars', [SiteController::class, 'cars'])->name('site.cars');
Route::get('/cars/{key}', [SiteController::class, 'car'])->name('site.car');
Route::get('/cars/{key}/precio', [SiteController::class, 'quote'])->name('site.car.quote');
Route::get('/moneda/{code}', [SiteController::class, 'currency'])->where('code', '[A-Za-z]{3}')->name('site.currency');

Route::controller(PageController::class)->group(function (): void {
    Route::get('/about-us', 'about')->name('site.about');
    Route::get('/faqs', 'faqs')->name('site.faqs');
    Route::get('/blog', 'blog')->name('site.blog');
    Route::get('/news/{key}', 'post')->name('site.post');
});

Route::controller(CheckoutController::class)->group(function (): void {
    Route::get('/reservar/{key}', 'show')->name('site.checkout');
    Route::post('/reservar/{key}', 'store')->middleware('throttle:10,1')->name('site.checkout.store');
    Route::get('/reserva/{transaction}', 'confirmation')->name('site.booking');
});

Route::middleware('auth')
    ->get('admin/reservas/calendario/eventos', [BookingCalendarController::class, 'events'])
    ->name('admin.bookings.calendar.events');

// Contract documents open in their own tab (printable, signable on a tablet),
// outside the panel layout but behind the same staff login.
Route::middleware('auth')
    ->prefix('admin/bookings/{booking}')
    ->name('contracts.')
    ->controller(ContractController::class)
    ->group(function (): void {
        Route::get('contract', 'delivery')->name('delivery');
        Route::post('contract', 'saveDelivery')->name('delivery.save');
        Route::post('contract/void', 'voidDelivery')->name('delivery.void');

        Route::get('return', 'return')->name('return');
        Route::post('return', 'saveReturn')->name('return.save');
        Route::post('return/void', 'voidReturn')->name('return.void');
    });
