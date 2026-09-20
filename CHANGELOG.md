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
