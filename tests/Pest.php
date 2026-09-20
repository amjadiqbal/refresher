<?php

use AmjadIqbal\Refresher\Tests\RefresherTraitTestCase;
use AmjadIqbal\Refresher\Tests\TestCase;

// Pest only auto-loads a single tests/Pest.php — there is no per-directory
// Pest.php discovery (confirmed reading BootFiles.php's real source on the
// Kiln build, same channel) — so every directory-specific binding lives here.
// tests/Feature/RefresherTraitCases/ needs the RefreshesWithRefresher trait
// bound at the TestCase level; everything else uses the plain TestCase. Pest
// refuses to bind the same path twice, so the plain TestCase binding lists
// every other test location explicitly rather than the whole tests/ directory.
uses(RefresherTraitTestCase::class)->in(__DIR__.'/Feature/RefresherTraitCases');
uses(TestCase::class)->in(
    __DIR__.'/Unit',
    __DIR__.'/Feature/SnapshotAndRestoreCommandsTest.php',
);
