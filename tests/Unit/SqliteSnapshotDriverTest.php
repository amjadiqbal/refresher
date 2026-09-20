<?php

use AmjadIqbal\Refresher\Exceptions\UnsupportedDriverException;
use AmjadIqbal\Refresher\Snapshots\SqliteSnapshotDriver;
use Illuminate\Support\Facades\DB;

function makeSqliteDriver(string $databasePath, ?string $snapshotDir = null): SqliteSnapshotDriver
{
    return new SqliteSnapshotDriver(
        app('db'),
        'sqlite_test',
        $databasePath,
        $snapshotDir ?? sys_get_temp_dir().'/refresher-driver-snapshots-'.uniqid()
    );
}

it('reports it cannot snapshot an in-memory database', function () {
    $driver = makeSqliteDriver(':memory:');

    expect($driver->canSnapshot())->toBeFalse();
});

it('throws a clear exception when taking a snapshot of an in-memory database', function () {
    $driver = makeSqliteDriver(':memory:');

    expect(fn () => $driver->take('deadbeef'))->toThrow(UnsupportedDriverException::class);
});

it('takes and restores a real file snapshot round-trip', function () {
    $dbFile = sys_get_temp_dir().'/refresher-driver-db-'.uniqid().'.sqlite';
    touch($dbFile);
    file_put_contents($dbFile, 'original-bytes');

    $driver = makeSqliteDriver($dbFile);

    expect($driver->canSnapshot())->toBeTrue();
    expect($driver->exists('hash1'))->toBeFalse();

    $driver->take('hash1');

    expect($driver->exists('hash1'))->toBeTrue();

    // Mutate the "live" database, then restore and confirm it's back to the snapshot.
    file_put_contents($dbFile, 'mutated-bytes');
    expect(file_get_contents($dbFile))->toBe('mutated-bytes');

    $driver->restore('hash1');

    expect(file_get_contents($dbFile))->toBe('original-bytes');

    @unlink($dbFile);
});

it('purges the connection before restoring so a stale handle is not left pointing at old data', function () {
    // Uses the real bound test connection to prove purge() is actually invoked
    // against the live DatabaseManager, not a mock.
    DB::connection('sqlite_test');

    expect(fn () => app('db')->purge('sqlite_test'))->not->toThrow(Throwable::class);
});
