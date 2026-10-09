<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // A site configured on https builds every link and form on https, even
        // when the hosting hands the request to PHP as plain http.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // Hosts that only serve existing files (public/ is not the web root) set ASSET_URL=/public;
        // Livewire's published script must be linked from there too.
        if (config('app.asset_url') && ! config('livewire.asset_url')) {
            config(['livewire.asset_url' => rtrim((string) config('app.asset_url'), '/').'/vendor/livewire/livewire'.(config('app.debug') ? '' : '.min').'.js']);
        }
    }
}
