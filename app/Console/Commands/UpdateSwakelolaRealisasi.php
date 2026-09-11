<?php

namespace App\Console\Commands;

use App\Support\LegacyIsbImportDisabled;
use Illuminate\Console\Command;

class UpdateSwakelolaRealisasi extends Command
{
    protected $signature = 'update:swakelola-realisasi';
    protected $description = 'Dinonaktifkan: impor legacy ISB swakelola realisasi.';

    public function handle(): int
    {
        return LegacyIsbImportDisabled::forCommand($this);
    }
}
