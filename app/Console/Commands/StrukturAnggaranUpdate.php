<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Support\LegacyIsbImportDisabled;

class StrukturAnggaranUpdate extends Command
{
    protected $signature = 'update:struktur-anggaran';
    protected $description = 'Dinonaktifkan: impor legacy ISB struktur anggaran.';

    public function handle(): int
    {
        return LegacyIsbImportDisabled::forCommand($this);
    }
}
