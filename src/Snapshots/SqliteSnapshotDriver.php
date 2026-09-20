<?php

namespace AmjadIqbal\Refresher\Snapshots;

use AmjadIqbal\Refresher\Exceptions\RefresherException;
use AmjadIqbal\Refresher\Exceptions\UnsupportedDriverException;
use Illuminate\Database\DatabaseManager;

/**
 * SQLite snapshots are a plain file copy — no shelling out, no client binary.
 * This is why SQLite is the cheap, natural driver to ship first (confirmed
 * against code-distortion/adapt's real LaravelSQLiteSnapshot.php, which uses
 * the identical approach).
 */
class SqliteSnapshotDriver implements SnapshotDriver
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly string $connectionName,
        private readonly string $databasePath,
        private readonly string $snapshotDirectory,
    ) {}

    public function canSnapshot(): bool
    {
        return ! $this->isInMemory();
    }

    public function pathFor(string $hash): string
    {
        return rtrim($this->snapshotDirectory, '/')."/sqlite-{$hash}.sqlite";
    }

    public function exists(string $hash): bool
    {
        return is_file($this->pathFor($hash));
    }

    public function take(string $hash): void
    {
        $this->guardAgainstInMemory();

        if (! is_dir($this->snapshotDirectory)) {
            mkdir($this->snapshotDirectory, 0755, true);
        }

        if (! is_file($this->databasePath)) {
            throw new RefresherException("SQLite database file [{$this->databasePath}] does not exist — nothing to snapshot.");
        }

        $tmp = $this->pathFor($hash).'.tmp.'.getmypid();

        if (! copy($this->databasePath, $tmp)) {
            throw new RefresherException("Failed to copy SQLite database [{$this->databasePath}] while taking a snapshot.");
        }

        rename($tmp, $this->pathFor($hash));
    }

    public function restore(string $hash): void
    {
        $this->guardAgainstInMemory();

        if (! $this->exists($hash)) {
            throw new RefresherException("No Refresher snapshot exists for hash [{$hash}].");
        }

        // Disconnect first — copying a new file over an open SQLite connection's
        // handle produces "database schema has changed" on the next query
        // (confirmed against code-distortion/adapt's real LaravelSQLiteSnapshot.php,
        // which does the same purge()-before-copy for the same reason).
        $this->db->purge($this->connectionName);

        if (! copy($this->pathFor($hash), $this->databasePath)) {
            throw new RefresherException("Failed to restore SQLite snapshot to [{$this->databasePath}].");
        }
    }

    private function isInMemory(): bool
    {
        return in_array($this->databasePath, [':memory:', ''], true);
    }

    private function guardAgainstInMemory(): void
    {
        if ($this->isInMemory()) {
            throw UnsupportedDriverException::forInMemorySqlite();
        }
    }
}
