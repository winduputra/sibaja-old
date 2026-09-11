<?php

namespace App\Console\Commands;

use App\Support\LegacyIsbImportDisabled;
use Illuminate\Console\Command;

class TokoDaringUpdate extends Command
{
    protected $signature = 'tokodaring:update';
    protected $description = 'Dinonaktifkan: impor legacy ISB Toko Daring.';

    public function handle(): int
    {
        return LegacyIsbImportDisabled::forCommand($this);
    }
}
