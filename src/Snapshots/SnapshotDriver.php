<?php

namespace AmjadIqbal\Refresher\Snapshots;

interface SnapshotDriver
{
    /**
     * Whether this connection/database can be snapshotted at all
     * (e.g. false for an in-memory SQLite database).
     */
    public function canSnapshot(): bool;

    /**
     * Absolute path a snapshot for the given migration hash would live at.
     */
    public function pathFor(string $hash): string;

    /**
     * Whether a snapshot for the given migration hash already exists on disk.
     */
    public function exists(string $hash): bool;

    /**
     * Dump the current (migrated) database out to the snapshot file for this hash.
     */
    public function take(string $hash): void;

    /**
     * Restore the database from the snapshot file for this hash.
     */
    public function restore(string $hash): void;
}
