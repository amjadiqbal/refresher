<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default connection
    |--------------------------------------------------------------------------
    |
    | The database connection Refresher snapshots and restores by default.
    | Leave null to use the application's default connection (config('database.default')).
    | Every command accepts a --connection= option to override this per invocation.
    |
    */

    'connection' => env('REFRESHER_CONNECTION'),

    /*
    |--------------------------------------------------------------------------
    | Snapshot storage path
    |--------------------------------------------------------------------------
    |
    | Where snapshot files are written. Keep this outside of anything that gets
    | wiped between CI jobs if you want snapshots to survive across runs (e.g.
    | point it at a directory your CI config caches).
    |
    */

    'snapshot_path' => env('REFRESHER_SNAPSHOT_PATH', storage_path('framework/refresher')),

    /*
    |--------------------------------------------------------------------------
    | Migrations path(s)
    |--------------------------------------------------------------------------
    |
    | Directories scanned to compute the migration-set hash that keys each
    | snapshot. Add package/module migration directories here if your app
    | loads migrations from more than one place.
    |
    */

    'migration_paths' => [
        database_path('migrations'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported drivers
    |--------------------------------------------------------------------------
    |
    | Refresher v1 ships MySQL and SQLite only. Postgres is a known, deliberate
    | v1 limitation (see README) — the SnapshotDriver interface exists so a
    | Postgres driver can be added later without touching the command/trait
    | layer, but it is not built in this release. Using Refresher against any
    | other driver throws AmjadIqbal\Refresher\Exceptions\UnsupportedDriverException.
    |
    */

    'mysql' => [
        'dump_binary' => env('REFRESHER_MYSQLDUMP_BINARY', 'mysqldump'),
        'restore_binary' => env('REFRESHER_MYSQL_BINARY', 'mysql'),
    ],

];
