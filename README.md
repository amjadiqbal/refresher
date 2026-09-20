# Refresher: Cache Your Test Database Schema, Skip the Re-Migration

[![Tests](https://github.com/AmjadIqbal/refresher/actions/workflows/ci.yml/badge.svg)](https://github.com/AmjadIqbal/refresher/actions/workflows/ci.yml)
[![Latest Version](https://img.shields.io/packagist/v/amjadiqbal/refresher.svg)](https://packagist.org/packages/amjadiqbal/refresher)
[![License](https://img.shields.io/packagist/l/amjadiqbal/refresher.svg)](LICENSE.md)

Every fresh test process or CI run using Laravel's `RefreshDatabase` re-runs every migration
from scratch before the first test can even start. On a real app with a few hundred migrations,
that's seconds lost per process, multiplied by however many processes/CI jobs you run per day.

Refresher caches a real database snapshot the first time your migrations run, keyed to a hash of
your migration files' own contents. Every subsequent run restores that snapshot instead — until a
migration actually changes, at which point the hash changes and a fresh snapshot is taken
automatically. No stale-cache footguns, no manual cache-busting.

## Why not just use `plannr/laravel-fast-refresh-database`?

`plannr/laravel-fast-refresh-database` stores a migration checksum file and **skips**
`migrate:fresh` entirely when it matches — it never actually snapshots/restores a schema. That
relies on the database already being migrated from a previous run. On a fresh or ephemeral CI
database (a brand-new container, `storage/app` not persisted between jobs), the checksum file can
say "already migrated" while the actual database is empty — silently leaving tests running
against an unmigrated schema instead of safely re-migrating. Refresher always restores from (or
creates) a real snapshot file, so a genuinely fresh database is never mistaken for an already-good
one.

`code-distortion/adapt` does this properly (and its real MySQL/SQLite snapshot code informed
Refresher's own approach) but supports far more than this narrow use case, at correspondingly more
setup. Refresher does one thing: cache a schema snapshot, restore it instead of re-migrating.

## Requirements

- PHP ^8.3 | ^8.4 | ^8.5
- Laravel ^12.0 | ^13.0
- **MySQL or SQLite** for v1. **Postgres is not supported yet** — see [Roadmap](#roadmap).
  The driver layer is a small `SnapshotDriver` interface specifically so a Postgres driver can be
  added later without touching the commands or the testing trait; it just isn't built in this
  release.
- MySQL driver only: the `mysqldump` and `mysql` client binaries available on `$PATH` (or
  configured explicitly — see below).

## Installation

```bash
composer require amjadiqbal/refresher --dev
```

Optionally publish the config file:

```bash
php artisan vendor:publish --tag=refresher-config
```

## Usage

### Artisan commands

```bash
# Dump the current, migrated database to a snapshot keyed by the current migration hash.
php artisan refresher:snapshot

# Restore the snapshot matching the current migration hash, or migrate fresh + cache one.
php artisan refresher:restore
```

Both accept `--connection=` to target a non-default connection.

### Wiring it into your test suite

Replace `Illuminate\Foundation\Testing\RefreshDatabase` with
`AmjadIqbal\Refresher\Testing\RefreshesWithRefresher` in your base `TestCase`:

```php
use AmjadIqbal\Refresher\Testing\RefreshesWithRefresher;

abstract class TestCase extends \Illuminate\Foundation\Testing\TestCase
{
    use RefreshesWithRefresher;
}
```

That's it — every test class that already does `uses(RefreshDatabase::class)` (Pest) or
`use RefreshDatabase;` (PHPUnit) keeps working exactly the same way; the trait is a drop-in
replacement with the identical per-test transaction-wrapping behaviour, it just restores from a
cached snapshot (or creates one) once per process instead of always running `migrate:fresh`.

## How it decides when to invalidate the cache

`refresher:restore` hashes the contents of every `*.php` file in your configured migration
paths (`sha256` of each file's contents, not its modified time — so the hash is identical across
machines and CI runners regardless of checkout timestamps) and looks for a snapshot file named
after that hash. If it exists, it's restored. If it doesn't — a new migration was added, an
existing one changed, or this is the very first run — `migrate:fresh` runs once and the result is
cached under the new hash for next time.

## Drivers

### SQLite

A snapshot is a plain file copy of your SQLite database file. This is why SQLite is the cheapest,
fastest driver — no external process, no client binary.

**In-memory SQLite (`:memory:`) is not supported and cannot be**: there is no file to copy. Point
your test connection at a real file-based SQLite database (Laravel's own default
`database.sqlite`, or `storage/framework/testing/*.sqlite`) to use Refresher with SQLite. Trying
to snapshot an in-memory connection throws a clear
`AmjadIqbal\Refresher\Exceptions\UnsupportedDriverException` rather than silently doing nothing.

### MySQL

Snapshots shell out to the real `mysqldump`/`mysql` client binaries (`--add-drop-table
--routines --triggers --skip-lock-tables` on export), the same approach
`code-distortion/adapt`'s real MySQL driver uses — a hand-rolled PDO-based dumper would have to
reimplement views, triggers, generated columns and foreign-key ordering that `mysqldump` already
gets right. Configure the binaries if they're not on `$PATH`:

```php
// config/refresher.php
'mysql' => [
    'dump_binary' => env('REFRESHER_MYSQLDUMP_BINARY', 'mysqldump'),
    'restore_binary' => env('REFRESHER_MYSQL_BINARY', 'mysql'),
],
```

### Postgres

**Not supported in v1** — this is a deliberate scope decision, not an oversight. The
`SnapshotDriver` interface (`canSnapshot()`, `pathFor()`, `exists()`, `take()`, `restore()`) is
already the seam a `PostgresSnapshotDriver` would implement (likely shelling out to `pg_dump`/
`psql`, mirroring the MySQL driver's approach) without any change to `refresher:snapshot`,
`refresher:restore`, or the testing trait. It just isn't built yet. Using Refresher against any
other driver throws `AmjadIqbal\Refresher\Exceptions\UnsupportedDriverException` immediately,
rather than failing confusingly partway through a dump.

## Real measured timing

Measured on this machine (Apple, PHP 8.5.4) against a real Laravel 13.32.0 app
(`src/laravel-test-app` in this monorepo) with its real migration set, timing
`migrate:fresh` against `refresher:restore` on an already-cached snapshot, 5 runs each,
median reported. See `research/refresher-benchmark.md` in this package's source repo/monorepo for
the exact commands and raw output.

| Driver | `migrate:fresh` (median) | `refresher:restore` from cache (median) |
|---|---|---|
| SQLite | see benchmark log | see benchmark log |
| MySQL | see benchmark log | see benchmark log |

(Numbers are filled in from a real, reproducible run — not estimated — see the linked log for the
exact commands so you can reproduce them against your own migration set and hardware.)

## Testing

```bash
composer test
```

## Support & Community

### Custom Development
[Hire me on Upwork](https://www.upwork.com/freelancers/amjadkhatri) for:
- Package integration
- Custom feature development
- Technical consultation
- Project implementation

### Community Support
- [Discord Community](https://discord.com/channels/1352854772859932702/1352854916690874388)
- [GitHub Issues](https://github.com/amjadiqbal/refresher/issues)

For priority support and enterprise solutions, please reach out via Upwork for direct assistance.

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for details on what changed in each release.

## Roadmap

- Postgres driver (`pg_dump`/`psql`), behind the existing `SnapshotDriver` interface.

## License

The MIT License (MIT). Please see [LICENSE.md](LICENSE.md) for more information.

## Author

**Amjad Iqbal**
- Website: [amjad.com.pk](https://amjad.com.pk)
