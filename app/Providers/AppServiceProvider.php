<?php

namespace App\Providers;

use App\Support\R2FilesystemAdapter;
use AsyncAws\S3\S3Client;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use League\Flysystem\AsyncAwsS3\AsyncAwsS3Adapter;
use League\Flysystem\Filesystem;
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
        // Disk driver for Cloudflare R2 (S3-compatible, no ACLs).
        Storage::extend('r2', function ($app, array $config) {
            $client = new S3Client([
                'region' => $config['region'] ?? 'auto',
                'endpoint' => $config['endpoint'],
                'accessKeyId' => $config['key'],
                'accessKeySecret' => $config['secret'],
                'pathStyleEndpoint' => true,
            ]);
            $adapter = new AsyncAwsS3Adapter($client, $config['bucket'], $config['root'] ?? '');

            return new R2FilesystemAdapter(new Filesystem($adapter, ['visibility' => 'private']), $adapter, $config);
        });

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
