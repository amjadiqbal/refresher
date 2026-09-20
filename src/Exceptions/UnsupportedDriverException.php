<?php

namespace AmjadIqbal\Refresher\Exceptions;

class UnsupportedDriverException extends RefresherException
{
    public static function forDriver(string $driver): self
    {
        return new self(
            "Refresher does not support the [{$driver}] database driver in this release. ".
            'Only mysql and sqlite are supported (v1) — see the README for the Postgres roadmap note.'
        );
    }

    public static function forInMemorySqlite(): self
    {
        return new self(
            'Refresher cannot snapshot an in-memory SQLite database (":memory:"). '.
            'Use a file-based SQLite database for tests (e.g. database.sqlite or a path under '.
            'storage/framework/testing) so a snapshot file has something real to copy.'
        );
    }
}
