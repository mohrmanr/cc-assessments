<?php

/**
 * Shorten instrument display versions: ACE-10 -> ACE, GSE-10 -> GSE.
 *
 * Usage (from assessment-portal root):
 *   php scripts/update_instrument_short_labels.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Instrument;

$updates = [
    'ace' => 'ACE',
    'gse' => 'GSE',
];

foreach ($updates as $slug => $version) {
    $instrument = Instrument::query()->where('slug', $slug)->first();
    if (! $instrument) {
        fwrite(STDERR, "No instrument with slug {$slug} found.\n");
        continue;
    }

    $before = $instrument->version;
    $instrument->forceFill(['version' => $version])->save();
    echo "{$slug}: '{$before}' -> '{$version}'\n";
}

echo "Done.\n";
