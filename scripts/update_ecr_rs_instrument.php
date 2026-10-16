<?php

/**
 * Production-safe: update only the ecr-rs instrument to general / mother / father.
 *
 * Usage (from assessment-portal root):
 *   php scripts/update_ecr_rs_instrument.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Instrument;
use App\Support\AttachmentSurvey;

$items = AttachmentSurvey::expandForTargets(AttachmentSurvey::ecrRsBaseItems());

$instrument = Instrument::query()->where('slug', 'ecr-rs')->first();

if (! $instrument) {
    fwrite(STDERR, "No instrument with slug ecr-rs found.\n");
    exit(1);
}

$config = $instrument->scoring_config ?? [];
$config['threshold'] = 4.0;
$config['method'] = 'attachment_targets';
$config['direction'] = 'above';
$config['description'] = 'Rate how you experience close relationships in general, and with your mother and your father.';
$config['instructions'] = 'You will answer the same set of questions three times: once for close relationships in general, once for your mother, and once for your father. For each statement, select how much you agree or disagree.';
$config['repeat_for_targets'] = ['general', 'mother', 'father'];
$config['item_attributes'] = [
    ['key' => 'reverse_score', 'type' => 'boolean', 'label' => 'Reverse score', 'standard' => true],
    ['key' => 'target', 'type' => 'string', 'label' => 'Target'],
    ['key' => 'subscale', 'type' => 'string', 'label' => 'Subscale'],
];
$config['response_labels'] = $config['response_labels'] ?? [
    1 => 'Strongly disagree',
    2 => 'Disagree',
    3 => 'Slightly disagree',
    4 => 'Neutral',
    5 => 'Slightly agree',
    6 => 'Agree',
    7 => 'Strongly agree',
];

$instrument->update([
    'items' => $items,
    'scoring_config' => $config,
]);

$fresh = $instrument->fresh();
echo 'Updated ecr-rs: '.count($fresh->items ?? []).' items, method='.($fresh->scoring_config['method'] ?? 'n/a').PHP_EOL;
