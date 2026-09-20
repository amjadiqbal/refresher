<?php

namespace AmjadIqbal\Refresher\Support;

class MigrationHasher
{
    /**
     * Compute a stable hash of the given migration directories' contents.
     *
     * Hashes file name + file content (not mtime) for every *.php file, so the
     * result is identical across machines/CI runners regardless of checkout
     * timestamps, and changes the instant a migration's contents change,
     * a migration is added, or one is removed.
     *
     * @param  string[]  $paths
     */
    public static function hash(array $paths): string
    {
        $files = [];

        foreach ($paths as $path) {
            if (! is_dir($path)) {
                continue;
            }

            foreach (glob(rtrim($path, '/').'/*.php') ?: [] as $file) {
                $files[basename($file)] = hash('sha256', (string) file_get_contents($file));
            }
        }

        ksort($files);

        return hash('sha256', json_encode($files, JSON_THROW_ON_ERROR));
    }
}
