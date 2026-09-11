<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Support\LegacyIsbImportDisabled;

class TenderUpdate extends Command
{
    protected $signature = 'tender:update {--year=} {--filter=}';

    protected $description = 'Dinonaktifkan: impor legacy ISB tender.';

    public function handle(): int
    {
        return LegacyIsbImportDisabled::forCommand($this);
    }
}
