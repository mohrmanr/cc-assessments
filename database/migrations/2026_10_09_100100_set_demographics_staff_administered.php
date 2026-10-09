<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->setAdministeredBy('staff');
    }

    public function down(): void
    {
        $this->setAdministeredBy('participant');
    }

    private function setAdministeredBy(string $value): void
    {
        $instrument = DB::table('instruments')->where('slug', 'demographics')->first(['id', 'scoring_config']);
        if ($instrument === null) {
            return;
        }

        $config = json_decode((string) $instrument->scoring_config, true) ?: [];
        $config['administered_by'] = $value;

        DB::table('instruments')
            ->where('id', $instrument->id)
            ->update(['scoring_config' => json_encode($config)]);
    }
};