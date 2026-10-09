<?php

namespace App\Console\Commands;

use App\Support\R2;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Sube a R2 los archivos que estaban en el servidor (public/media o MEDIA_ROOT), para
 * un negocio que ya tenía fotos y contratos antes de usar su bucket.
 */
class MediaToR2Command extends Command
{
    protected $signature = 'media:to-r2 {--delete : Borrar el archivo local después de subirlo}';

    protected $description = 'Sube a Cloudflare R2 los archivos de public/media';

    public function handle(): int
    {
        if (! R2::enabled()) {
            $this->error('Este sistema no tiene bucket R2: activa la licencia o define R2_ENDPOINT, R2_BUCKET, R2_KEY y R2_SECRET en .env.');

            return self::FAILURE;
        }

        $root = (string) env('MEDIA_ROOT', 'public/media');
        $root = preg_match('#^([A-Za-z]:)?[\\\\/]#', $root) ? $root : base_path($root);

        if (! is_dir($root)) {
            $this->info('No hay archivos locales que subir.');

            return self::SUCCESS;
        }

        $disk = Storage::disk('media');
        $sent = $failed = 0;

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
            $relative = ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen(rtrim($root, '\\/')))), '/');

            if (! $file->isFile() || str_starts_with(basename($relative), '.')) {
                continue;
            }

            $stream = fopen($file->getPathname(), 'rb');

            if ($stream !== false && $disk->writeStream($relative, $stream)) {
                $sent++;
                $this->line("✔ {$relative}");

                if ($this->option('delete')) {
                    @unlink($file->getPathname());
                }
            } else {
                $failed++;
                $this->warn("✘ {$relative}");
            }

            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        $this->info("Subidos: {$sent}. Con error: {$failed}.");

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
