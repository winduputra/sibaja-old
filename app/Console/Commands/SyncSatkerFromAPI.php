<?php

namespace App\Console\Commands;

use App\Support\LegacyIsbImportDisabled;
use Illuminate\Console\Command;

class SyncSatkerFromAPI extends Command
{
    protected $signature = 'sync:satker-api';
    protected $description = 'Dinonaktifkan: impor legacy ISB satker.';

    public function handle(): int
    {
        return LegacyIsbImportDisabled::forCommand($this);
    }
}
