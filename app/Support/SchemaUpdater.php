<?php

namespace App\Support;

use Illuminate\Support\Facades\Artisan;
use Throwable;

/**
 * Applies new migrations by itself after the code is updated (git pull), for hosting plans
 * without SSH. A marker file named after the list of migrations makes the check free once done.
 */
class SchemaUpdater
{
    public static function run(): void
    {
        $names = array_map('basename', glob(database_path('migrations/*.php')) ?: []);
        sort($names);
        $marker = storage_path('framework/migrated-' . md5(implode('|', $names)));

        if (is_file($marker)) {
            return;
        }

        try {
            Artisan::call('migrate', ['--force' => true]);
            @file_put_contents($marker, now()->toDateTimeString());
        } catch (Throwable $exception) {
            // A site that cannot alter its tables must keep working; the panel will show the problem.
            report($exception);
        }
    }
}
