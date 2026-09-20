<?php

namespace AmjadIqbal\Refresher\Snapshots;

use AmjadIqbal\Refresher\Exceptions\RefresherException;
use Symfony\Component\Process\Process;

/**
 * MySQL snapshots shell out to the real mysqldump/mysql client binaries.
 * A native PDO-based dump was considered and rejected: mysqldump correctly
 * handles views, triggers, generated columns and foreign-key ordering that a
 * hand-rolled PDO dumper would have to reimplement — the same conclusion
 * code-distortion/adapt's real LaravelMySQLSnapshot.php reaches (it also
 * shells out, with --add-drop-table --skip-lock-tables and an atomic
 * tmp-file-then-rename export, both mirrored here).
 */
class MySqlSnapshotDriver implements SnapshotDriver
{
    public function __construct(
        private readonly array $connectionConfig,
        private readonly string $snapshotDirectory,
        private readonly string $dumpBinary,
        private readonly string $restoreBinary,
    ) {}

    public function canSnapshot(): bool
    {
        return true;
    }

    public function pathFor(string $hash): string
    {
        return rtrim($this->snapshotDirectory, '/')."/mysql-{$hash}.sql";
    }

    public function exists(string $hash): bool
    {
        return is_file($this->pathFor($hash));
    }

    public function take(string $hash): void
    {
        if (! is_dir($this->snapshotDirectory)) {
            mkdir($this->snapshotDirectory, 0755, true);
        }

        $tmp = $this->pathFor($hash).'.tmp.'.getmypid();

        $command = array_merge([$this->dumpBinary], $this->connectionArgs(), [
            '--add-drop-table',
            '--routines',
            '--triggers',
            '--skip-lock-tables',
            '--result-file='.$tmp,
            (string) $this->connectionConfig['database'],
        ]);

        $process = new Process($command);
        $process->run();

        if (! $process->isSuccessful()) {
            @unlink($tmp);

            throw new RefresherException(
                "mysqldump failed while taking a Refresher snapshot: {$process->getErrorOutput()}"
            );
        }

        rename($tmp, $this->pathFor($hash));
    }

    public function restore(string $hash): void
    {
        if (! $this->exists($hash)) {
            throw new RefresherException("No Refresher snapshot exists for hash [{$hash}].");
        }

        $command = array_merge([$this->restoreBinary], $this->connectionArgs(), [
            (string) $this->connectionConfig['database'],
        ]);

        $process = new Process($command);
        $process->setInput(fopen($this->pathFor($hash), 'r'));
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RefresherException(
                "mysql client failed while restoring a Refresher snapshot: {$process->getErrorOutput()}"
            );
        }
    }

    /**
     * @return string[]
     */
    private function connectionArgs(): array
    {
        $args = [
            '--host='.($this->connectionConfig['host'] ?? '127.0.0.1'),
            '--port='.($this->connectionConfig['port'] ?? '3306'),
            '--user='.($this->connectionConfig['username'] ?? 'root'),
        ];

        // An empty password must not produce a bare "--password=" (which makes
        // the client prompt interactively); only pass it when non-empty.
        if (! empty($this->connectionConfig['password'])) {
            $args[] = '--password='.$this->connectionConfig['password'];
        }

        return $args;
    }
}
