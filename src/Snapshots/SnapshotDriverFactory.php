<?php

namespace AmjadIqbal\Refresher\Snapshots;

use AmjadIqbal\Refresher\Exceptions\UnsupportedDriverException;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Config;

class SnapshotDriverFactory
{
    public function __construct(private readonly DatabaseManager $db) {}

    public function make(?string $connectionName = null): SnapshotDriver
    {
        $connectionName ??= config('refresher.connection') ?? config('database.default');

        $connectionConfig = Config::get("database.connections.{$connectionName}", []);
        $driver = $connectionConfig['driver'] ?? null;

        $snapshotDirectory = config('refresher.snapshot_path');

        return match ($driver) {
            'sqlite' => new SqliteSnapshotDriver(
                $this->db,
                $connectionName,
                (string) ($connectionConfig['database'] ?? ''),
                $snapshotDirectory,
            ),
            'mysql' => new MySqlSnapshotDriver(
                $connectionConfig,
                $snapshotDirectory,
                config('refresher.mysql.dump_binary', 'mysqldump'),
                config('refresher.mysql.restore_binary', 'mysql'),
            ),
            default => throw UnsupportedDriverException::forDriver((string) $driver),
        };
    }
}
