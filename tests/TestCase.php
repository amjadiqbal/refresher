<?php

namespace AmjadIqbal\Refresher\Tests;

use AmjadIqbal\Refresher\RefresherServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            RefresherServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite_test');
        $app['config']->set('database.connections.sqlite_test', [
            'driver' => 'sqlite',
            'database' => $this->sqliteDatabasePath(),
            'prefix' => '',
        ]);

        $app['config']->set('refresher.snapshot_path', sys_get_temp_dir().'/refresher-tests-snapshots');
        $app['config']->set('refresher.migration_paths', [__DIR__.'/fixtures/migrations']);

        // Register the fixture migrations path with the real migrator (plain
        // Illuminate\Support\ServiceProvider::loadMigrationsFrom() behaviour —
        // registers the path only, does not auto-run) so migrate/migrate:fresh
        // pick them up. Deliberately NOT using Testbench's own TestCase-level
        // loadMigrationsFrom() convenience, which also runs migrate once
        // automatically during setUp() — that auto-run would leave the schema
        // already migrated before Refresher's own commands are ever invoked,
        // defeating what these tests are actually verifying.
        $app->afterResolving('migrator', function ($migrator) {
            $migrator->path(__DIR__.'/fixtures/migrations');
        });
    }

    protected function sqliteDatabasePath(): string
    {
        return sys_get_temp_dir().'/refresher-tests.sqlite';
    }

    protected function setUp(): void
    {
        // The sqlite database is a real file shared across every test in this
        // process (not :memory:, deliberately — Refresher can't snapshot
        // :memory:), and migrations are registered but never auto-run here
        // (see defineEnvironment()) — only migrate:fresh/restore create the
        // file's schema, and migrate:fresh drops any existing tables first,
        // so a stale file from a previous run is not a problem. Tests that
        // need a genuinely fresh file (to simulate a brand-new process/CI
        // container) reset it explicitly in their own beforeEach().
        if (! is_file($this->sqliteDatabasePath())) {
            touch($this->sqliteDatabasePath());
        }

        parent::setUp();
    }
}
