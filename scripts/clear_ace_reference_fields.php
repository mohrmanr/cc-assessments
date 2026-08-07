<?php

/**
 * Remove optional ACE initials/email fields from the live instrument config.
 *
 * Usage:
 *   php scripts/clear_ace_reference_fields.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Instrument;

$ace = Instrument::query()->where('slug', 'ace')->first();
if (! $ace) {
    fwrite(STDERR, "No ace instrument found.\n");
    exit(1);
}

$config = $ace->scoring_config ?? [];
$before = $config['fields'] ?? '(unset)';
$config['fields'] = [];
$ace->forceFill(['scoring_config' => $config])->save();

echo "ace fields cleared. before=";
echo is_array($before) ? json_encode($before) : $before;
echo PHP_EOL;
