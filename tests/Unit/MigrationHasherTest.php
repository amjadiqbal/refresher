<?php

use AmjadIqbal\Refresher\Support\MigrationHasher;

it('produces the same hash for the same migration contents', function () {
    $dir = sys_get_temp_dir().'/refresher-hasher-'.uniqid();
    mkdir($dir);
    file_put_contents($dir.'/2024_01_01_000000_a.php', '<?php // a');

    $hash1 = MigrationHasher::hash([$dir]);
    $hash2 = MigrationHasher::hash([$dir]);

    expect($hash1)->toBe($hash2);

    exec('rm -rf '.escapeshellarg($dir));
});

it('changes when a migration file is added', function () {
    $dir = sys_get_temp_dir().'/refresher-hasher-'.uniqid();
    mkdir($dir);
    file_put_contents($dir.'/2024_01_01_000000_a.php', '<?php // a');

    $before = MigrationHasher::hash([$dir]);

    file_put_contents($dir.'/2024_01_02_000000_b.php', '<?php // b');

    $after = MigrationHasher::hash([$dir]);

    expect($after)->not->toBe($before);

    exec('rm -rf '.escapeshellarg($dir));
});

it('changes when a migration file content changes', function () {
    $dir = sys_get_temp_dir().'/refresher-hasher-'.uniqid();
    mkdir($dir);
    $file = $dir.'/2024_01_01_000000_a.php';
    file_put_contents($file, '<?php // a');

    $before = MigrationHasher::hash([$dir]);

    file_put_contents($file, '<?php // a v2');

    $after = MigrationHasher::hash([$dir]);

    expect($after)->not->toBe($before);

    exec('rm -rf '.escapeshellarg($dir));
});

it('ignores directories that do not exist', function () {
    expect(MigrationHasher::hash(['/path/does/not/exist']))->toBeString();
});
