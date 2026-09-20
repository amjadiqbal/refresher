<?php

namespace AmjadIqbal\Refresher\Commands;

use AmjadIqbal\Refresher\Snapshots\SnapshotDriverFactory;
use AmjadIqbal\Refresher\Support\MigrationHasher;
use Illuminate\Console\Command;

class SnapshotCommand extends Command
{
    protected $signature = 'refresher:snapshot {--connection= : The database connection to snapshot}';

    protected $description = 'Dump the current (migrated) database to a cache file keyed by the migration set hash';

    public function handle(SnapshotDriverFactory $factory): int
    {
        $driver = $factory->make($this->option('connection'));

        if (! $driver->canSnapshot()) {
            $this->components->error('This connection cannot be snapshotted (e.g. an in-memory SQLite database).');

            return self::FAILURE;
        }

        $hash = MigrationHasher::hash(config('refresher.migration_paths'));

        $this->components->task("Snapshotting database (hash {$hash})", function () use ($driver, $hash) {
            $driver->take($hash);

            return true;
        });

        $this->components->info("Snapshot saved: {$driver->pathFor($hash)}");

        return self::SUCCESS;
    }
}
