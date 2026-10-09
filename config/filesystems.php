<?php

// Uploads (vehicle photos, contract photos, logos...) live in MEDIA_ROOT, served
// from MEDIA_URL. By default that is public/media inside this app. A relative path
// is resolved from the project root; on shared hosting the web root is public_html,
// so the package sets MEDIA_ROOT=../media (see docs/DESPLIEGUE-HOSTINGER.md).
$mediaRoot = env('MEDIA_ROOT', 'public/media');
if (! preg_match('#^([A-Za-z]:)?[\\\\/]#', $mediaRoot)) {
    $mediaRoot = base_path($mediaRoot);
}

$r2 = \App\Support\R2::settings();

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        // Uploads: vehicle photos, contract and return photos, logos... In the client's own
        // Cloudflare R2 bucket when it has one (see App\Support\R2), otherwise on this server.
        'media' => $r2 ? [
            'driver' => 'r2',
            'endpoint' => $r2['endpoint'],
            'bucket' => $r2['bucket'],
            'key' => $r2['key'],
            'secret' => $r2['secret'],
            'region' => 'auto',
            'throw' => false,
            'report' => false,
        ] : [
            'driver' => 'local',
            'root' => $mediaRoot,
            // Same origin as the admin, so photos preview without cross-origin problems.
            'url' => env('MEDIA_URL', rtrim(env('APP_URL', 'http://localhost'), '/').'/media'),
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
        // Not needed when the photos already live in public/media (or in R2).
        ...(! $r2 && realpath($mediaRoot) !== realpath(public_path('media')) ? [public_path('media') => $mediaRoot] : []),
    ],

];
