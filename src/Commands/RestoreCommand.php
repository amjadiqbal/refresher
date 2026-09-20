<?php

namespace AmjadIqbal\Refresher\Commands;

use AmjadIqbal\Refresher\Snapshots\SnapshotDriverFactory;
use AmjadIqbal\Refresher\Support\MigrationHasher;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;

class RestoreCommand extends Command
{
    protected $signature = 'refresher:restore
                            {--connection= : The database connection to restore}
                            {--fresh-options= : Extra options passed through to migrate:fresh as a JSON-encoded array, when a fresh migration is needed}';

    protected $description = 'Restore a cached database snapshot matching the current migration hash, or migrate fresh and cache it if none exists';

    public function handle(SnapshotDriverFactory $factory, Kernel $kernel): int
    {
        $connection = $this->option('connection');
        $driver = $factory->make($connection);
        $hash = MigrationHasher::hash(config('refresher.migration_paths'));

        if ($driver->canSnapshot() && $driver->exists($hash)) {
            $this->components->task("Restoring snapshot (hash {$hash})", function () use ($driver, $hash) {
                $driver->restore($hash);

                return true;
            });

            return self::SUCCESS;
        }

        $this->components->warn(
            $driver->canSnapshot()
                ? 'No matching snapshot found — running a fresh migration.'
                : 'This connection cannot be snapshotted — running a fresh migration every time.'
        );

        $freshOptions = $this->option('fresh-options')
            ? json_decode((string) $this->option('fresh-options'), true, flags: JSON_THROW_ON_ERROR)
            : [];

        if ($connection) {
            $freshOptions['--database'] = $connection;
        }

        $this->call('migrate:fresh', $freshOptions);
        $kernel->setArtisan(null);

        if ($driver->canSnapshot()) {
            $this->components->task("Caching new snapshot (hash {$hash})", function () use ($driver, $hash) {
                $driver->take($hash);

                return true;
            });
        }

        return self::SUCCESS;
    }
}
