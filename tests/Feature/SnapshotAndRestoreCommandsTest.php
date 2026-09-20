<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    @unlink($this->sqliteDatabasePath());
    touch($this->sqliteDatabasePath());
    exec('rm -rf '.escapeshellarg(config('refresher.snapshot_path')));
});

it('runs a fresh migration and caches a snapshot when none exists yet', function () {
    expect(Schema::hasTable('widgets'))->toBeFalse();

    Artisan::call('refresher:restore');

    expect(Schema::hasTable('widgets'))->toBeTrue();
    expect(glob(config('refresher.snapshot_path').'/*'))->not->toBeEmpty();
});

it('restores from a cached snapshot instead of migrating again on the next run', function () {
    // First restore has no snapshot yet, so it migrates fresh and caches one
    // (schema-only, at migration time — same invariant RefreshDatabase itself
    // has: the snapshot is a migration baseline, not a data backup).
    Artisan::call('refresher:restore');

    DB::table('widgets')->insert(['name' => 'marker-row', 'created_at' => now(), 'updated_at' => now()]);
    expect(DB::table('widgets')->count())->toBe(1);

    // Explicitly cache this state (with the row) as the new snapshot, exactly
    // as a real test-suite bootstrap script would after seeding a baseline.
    Artisan::call('refresher:snapshot');

    // Simulate a brand-new process: wipe the live db file, but the snapshot on disk survives.
    DB::purge('sqlite_test');
    @unlink($this->sqliteDatabasePath());
    touch($this->sqliteDatabasePath());

    expect(Schema::hasTable('widgets'))->toBeFalse();

    Artisan::call('refresher:restore');

    expect(Schema::hasTable('widgets'))->toBeTrue();
    // The marker row proves this came from the cached snapshot, not a fresh migrate
    // (a fresh migrate would produce an empty widgets table with no rows at all).
    expect(DB::table('widgets')->where('name', 'marker-row')->exists())->toBeTrue();
});

it('invalidates the cache automatically when the migration set changes', function () {
    Artisan::call('refresher:restore');
    $firstSnapshotCount = count(glob(config('refresher.snapshot_path').'/*'));

    // A changed migration set produces a different hash → a different snapshot file,
    // proving the old cache is not blindly reused across schema changes.
    $extraMigration = __DIR__.'/../fixtures/migrations/2099_01_01_000000_temp_extra.php';
    file_put_contents($extraMigration, <<<'PHP'
        <?php
        use Illuminate\Database\Migrations\Migration;
        use Illuminate\Database\Schema\Blueprint;
        use Illuminate\Support\Facades\Schema;
        return new class extends Migration {
            public function up(): void {
                Schema::create('gadgets', function (Blueprint $table) {
                    $table->id();
                });
            }
        };
        PHP);

    DB::purge('sqlite_test');
    @unlink($this->sqliteDatabasePath());
    touch($this->sqliteDatabasePath());

    Artisan::call('refresher:restore');

    expect(Schema::hasTable('gadgets'))->toBeTrue();
    expect(count(glob(config('refresher.snapshot_path').'/*')))->toBeGreaterThan($firstSnapshotCount);

    unlink($extraMigration);
});

it('refresher:snapshot fails clearly when there is nothing migrated to snapshot from an in-memory db', function () {
    config(['database.connections.mem' => ['driver' => 'sqlite', 'database' => ':memory:']]);

    $exit = Artisan::call('refresher:snapshot', ['--connection' => 'mem']);

    expect($exit)->not->toBe(0);
});
