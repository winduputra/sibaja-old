<?php

namespace App\Console\Commands;

use App\Support\LegacyIsbImportDisabled;
use Illuminate\Console\Command;

class PenyediaUpdate extends Command
{
    protected $signature = 'update:penyedia';
    protected $description = 'Dinonaktifkan: impor legacy ISB Paket Penyedia.';

    public function handle(): int
    {
        return LegacyIsbImportDisabled::forCommand($this);
    }
}
