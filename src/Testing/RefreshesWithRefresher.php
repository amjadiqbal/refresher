<?php

namespace AmjadIqbal\Refresher\Testing;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;

/**
 * Drop-in replacement for Illuminate\Foundation\Testing\RefreshDatabase.
 *
 * Same shape as Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase
 * (confirmed by reading its real source): still uses RefreshDatabase's own
 * beginDatabaseTransaction()/RefreshDatabaseState::$migrated bookkeeping so
 * per-test transaction wrapping behaves identically to core. The only change
 * is what happens once per process before the first test: instead of a bare
 * checksum-file skip (which can leave a fresh/empty CI database unmigrated,
 * see README), this always asks refresher:restore to either restore a real
 * snapshot or migrate fresh and cache one — so a fresh database is never
 * silently left unmigrated.
 */
trait RefreshesWithRefresher
{
    use RefreshDatabase;

    protected function refreshTestDatabase(): void
    {
        if (! RefreshDatabaseState::$migrated) {
            $this->artisan('refresher:restore', $this->refresherRestoreOptions());

            $this->app[Kernel::class]->setArtisan(null);

            RefreshDatabaseState::$migrated = true;
        }

        $this->beginDatabaseTransaction();
    }

    /**
     * Override in a TestCase to pass a --connection or --fresh-options through
     * to refresher:restore, e.g. to point at a non-default connection.
     *
     * @return array<string, mixed>
     */
    protected function refresherRestoreOptions(): array
    {
        return [];
    }
}
