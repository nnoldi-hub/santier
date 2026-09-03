<?php

namespace App\Support;

use Illuminate\Foundation\Vite;

/**
 * Laravel's Vite class caches each parsed manifest.json in a process-lifetime
 * static array, keyed by path. On this host, PHP-FPM/LSAPI workers stay alive
 * across many requests, so a worker that served a request before a deploy
 * keeps handing out the OLD manifest (and therefore 404-ing asset hashes)
 * until that specific worker eventually recycles - which can take a long time
 * under low traffic, and doesn't happen at all reliably right after a deploy.
 * Forcing a fresh read of the (tiny) manifest file on every request trades a
 * negligible bit of I/O for guaranteeing the deployed assets are always used.
 */
class FreshManifestVite extends Vite
{
    protected function manifest($buildDirectory)
    {
        $path = $this->manifestPath($buildDirectory);

        unset(static::$manifests[$path]);

        return parent::manifest($buildDirectory);
    }
}
