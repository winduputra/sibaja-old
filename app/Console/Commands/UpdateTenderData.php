<?php

namespace App\Console\Commands;

use App\Support\LegacyIsbImportDisabled;
use Illuminate\Console\Command;

class UpdateTenderData extends Command
{
    protected $signature = 'update:tender-data';
    protected $description = 'Dinonaktifkan: impor legacy ISB tender.';

    public function handle(): int
    {
        return LegacyIsbImportDisabled::forCommand($this);
    }
}
