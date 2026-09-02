<?php

declare(strict_types=1);

// CLI: php seed.php [--replace]
require __DIR__ . '/app/bootstrap.php';

$replace = in_array('--replace', $argv ?? [], true);
$seed = __DIR__ . '/data/BookNest.json';

try {
    Schema::ensure();
    $result = ImportExportService::importSeedFile($seed, $replace);
    echo "OK: {$result['imported']} volumes added to the archive." . PHP_EOL;
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'Seed failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
