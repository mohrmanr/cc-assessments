<?php

/**
 * Shorten instrument display versions: ACE-10 -> ACE, GSE-10 -> GSE.
 * Also renames legacy slug gse-10 -> gse when present.
 *
 * Usage (from assessment-portal root):
 *   php scripts/update_instrument_short_labels.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Instrument;

$legacyGse = Instrument::query()->where('slug', 'gse-10')->first();
if ($legacyGse) {
    $existing = Instrument::query()->where('slug', 'gse')->first();
    if ($existing) {
        fwrite(STDERR, "Both gse-10 and gse exist; leaving slug alone and only updating version.\n");
        $legacyGse->forceFill(['version' => 'GSE'])->save();
        echo "gse-10: version -> 'GSE' (slug unchanged; gse already exists)\n";
    } else {
        $before = $legacyGse->version;
        $legacyGse->forceFill([
            'slug' => 'gse',
            'version' => 'GSE',
        ])->save();
        echo "gse-10: slug -> 'gse', version '{$before}' -> 'GSE'\n";
    }
}

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
