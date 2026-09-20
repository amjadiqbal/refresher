# Changelog

All notable changes to `amjadiqbal/refresher` are documented here.

## [Unreleased]

### Added
- `refresher:snapshot` — dumps the current, migrated database to a cache file
  keyed by a hash of the migration files' contents.
- `refresher:restore` — restores the matching cached snapshot instead of
  running `migrate:fresh`, or migrates fresh and caches a new snapshot when
  none exists (or the migration set has changed).
- `AmjadIqbal\Refresher\Testing\RefreshesWithRefresher` — drop-in trait
  replacement for `Illuminate\Foundation\Testing\RefreshDatabase` that wires
  `refresher:restore` into the usual once-per-process test-database refresh.
- MySQL driver (shells out to `mysqldump`/`mysql`) and SQLite driver (plain
  file copy) behind a shared `SnapshotDriver` interface.
- Postgres is a known, deliberate v1 limitation — see the README.

### Fixed
- MySQL snapshots now pass `--set-gtid-purged=OFF` to `mysqldump`. Without it, a server with
  GTID/binary logging enabled emits statements on export that require SUPER/
  SYSTEM_VARIABLES_ADMIN to run on restore — privileges a least-privilege test-database user
  won't have. Found and fixed via a real integration test against an isolated MySQL instance
  before this ever shipped.
