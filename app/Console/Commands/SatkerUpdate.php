<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Support\LegacyIsbImportDisabled;

class SatkerUpdate extends Command
{
    protected $signature = 'satker:update {--year=2024}';
    protected $description = 'Dinonaktifkan: impor legacy ISB satker.';

    public function handle(): int
    {
        return LegacyIsbImportDisabled::forCommand($this);
    }
}
