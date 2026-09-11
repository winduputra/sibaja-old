<?php

namespace App\Console\Commands;

use App\Support\LegacyIsbImportDisabled;
use Illuminate\Console\Command;

class EkatalogUpdate extends Command
{
    protected $signature = 'ekatalog:update';
    protected $description = 'Dinonaktifkan: impor legacy ISB e-Katalog.';

    public function handle(): int
    {
        return LegacyIsbImportDisabled::forCommand($this);
    }
}
