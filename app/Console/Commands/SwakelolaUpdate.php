<?php

namespace App\Console\Commands;

use App\Support\LegacyIsbImportDisabled;
use Illuminate\Console\Command;

class SwakelolaUpdate extends Command
{
    protected $signature = 'update:swakelola';
    protected $description = 'Dinonaktifkan: impor legacy ISB Swakelola.';

    public function handle(): int
    {
        return LegacyIsbImportDisabled::forCommand($this);
    }
}
