<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    exec('rm -rf '.escapeshellarg(config('refresher.snapshot_path')));
});

it('migrates the schema once per process via the trait, exactly like RefreshDatabase', function () {
    expect(Schema::hasTable('widgets'))->toBeTrue();
});

it('wraps each test in a transaction so writes do not leak between tests', function () {
    expect(DB::table('widgets')->count())->toBe(0);

    DB::table('widgets')->insert([
        'name' => 'leaky?',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(DB::table('widgets')->count())->toBe(1);
});

it('confirms the previous test row did not leak across the transaction boundary', function () {
    expect(DB::table('widgets')->count())->toBe(0);
});
